<?php

namespace sylma\parser\reflector\basic;
use \sylma\core, sylma\parser\languages\common, sylma\dom, sylma\parser\reflector;

/**
 * This class offers 2 main methods to load external parsers on dom element :
 * /@method loadElementForeign() and @method loadElementUnknown()
 * /@method parseElementForeign() and @method parseElementUknown() must be overrided to use them
 */
abstract class Foreigner extends Domed {

  /**
   * Handler for element creation with NS bug fixes
   */
  protected $foreignElements;
  protected $lastElement;

  protected $aAttributeParsers = array();

  /**
   * Sub parsers
   * @var array
   */
  protected $aParsers = array();

  public function parseRoot(dom\element $el) {

    return $this->parseElementSelf($el);
  }

  protected function lookupParserForeign($sNamespace) {

    return $this->getRoot()->lookupParser($sNamespace);
  }

  /**
   * Exception free parser loader
   *
   * @param string $sNamespace
   * @return parser\domed
   */
  protected function loadParser($sNamespace) {

    if (array_key_exists($sNamespace, $this->aParsers)) {

      $result = $this->aParsers[$sNamespace];
    }
    else {

      $result = $this->createParser($sNamespace);

      if ($result) {

        $this->addParser($result, $result->getUsedNamespaces());
      }
    }

    //if ($result) $result->setParent($this);

    return $result;
  }

  protected function createParser($sNamespace) {

    $manager = $this->getManager('parser');
    return $manager->getParser($sNamespace, $this->getRoot(), $this, false);
  }

  /**
   * Set local parsers, with associated namespaces
   * @param parser\reflector\domed $parser
   * @param array $aNS
   */
  protected function addParser(reflector\domed $parser, array $aNS) {

    $aResult = array();

    foreach ($aNS as $sNamespace) {

      $aResult[$sNamespace] = $parser;
    }

    $this->aParsers = array_merge($this->aParsers, $aResult);
  }

  protected function validateParser($sNamespace, $sParser = 'element') {

    $result = $this->lookupParserForeign($sNamespace);

    if ($result) {

      $bValid = false;

      switch ($sParser) {

        case 'element' : $bValid = $result instanceof reflector\elemented; break;
        case 'attribute' : $bValid = $result instanceof reflector\attributed; break;
      }

      if (!$bValid) {

        $this->throwException(sprintf('Cannot use parser %s in %s context', get_class($result), $sParser));
      }
    }

    return $result;
  }

  protected function parse(dom\node $node) {

    return $this->parseNode($node);
  }

  protected function createElement($sName, $mContent = null, array $aAttributes = array(), $sNamespace = '') {

    if (!$sNamespace) {

      $this->throwException('Element without namespace vorbidden');
    }

    if (!$this->foreignElements) {

      $this->foreignElements = $this->getControler('dom')->createDocument();
      $this->foreignElements->addElement('root');
    }

    $el = $this->foreignElements->createElement($sName, $mContent, $aAttributes, $sNamespace);

    return $el;
  }

  protected function parseElementForeign(dom\element $el) {

    $result = null;

    if ($this->allowForeign()) {

      $result = $this->loadElementForeign($el);
    }
    else {

      $result = parent::parseElementForeign($el);
    }

    return $result;
  }

  protected function loadElementForeign(dom\element $el) {

    $sNamespace = $el->getNamespace();

    if ($parser = $this->lookupParserForeign($sNamespace)) {

      $mResult = $parser->parseFromChild($el);
    }
    else if ($parser = $this->loadParser($sNamespace)) {

      $mResult = $parser->parseRoot($el);
    }
    else {

      $mResult = $this->parseElementUnknown($el);
    }

    return $mResult;
  }

  protected function parseElementUnknown(dom\element $el) {

    if ($this->allowUnknown()) {

      $result = $this->loadElementUnknown($el);
    }
    else {

      $result = parent::parseElementUnknown($el);
    }

    return $result;
  }

  /**
   * Build a new element from the source element, cannot copy cause of following steps
   *
   * - Check for foreign attributes.
   *   They define result if exists with @method parseAttributesForeign([new element]) (1st pass)
   * - Add parsed children to new element.
   * - Inform foreign attributes parser that element has been parsed with @method $parser->onClose
   *   This allow parsers to edit new element (2nd pass)
   *
   * These steps give the ability to attribute parsers to return element into new container
   *
   * @param dom\element $el
   * @return dom\element|mixed The new element or, if foreign attributes exists, result of parsing, so if result
   *         is changed on 1st pass and changes happened to new element on 2nd pass, they will we be ignored.
   */
  protected function loadElementUnknown(dom\element $el) {

    $newElement = $this->createElement($el->getName(), null, array(), $el->getNamespace());

    if ($this->useForeignAttributes($el)) {

      $mResult = $this->parseAttributesForeign($el, $newElement);
    }
    else {

      foreach ($el->getAttributes() as $attr) {

        $newElement->add($this->parseAttribute($attr));
      }

      $mResult = $newElement;
    }

    $aParsers = $this->getAttributeParsers();
    $this->setAttributeParsers();

    $this->setLastElement($newElement);

    if ($aChildren = $this->parseChildren($el->getChildren())) {

      $newElement->add($aChildren);
    }

    foreach ($aParsers as $parser) {

      $parser->onClose($el, $newElement);
    }

    return $mResult;
  }

  /**
   *
   * @param dom\element $el
   * @return dom\element|common\_scope
   */
  protected function parseAttributesForeign(dom\element $el, dom\element $newElement) {

    $aForeigns = array();

    foreach ($el->getAttributes() as $attr) {

      $sNamespace = $attr->getNamespace();

      if (!$sNamespace || $sNamespace == $this->getNamespace()) {

        $newElement->add($this->parseAttribute($attr));
      }
      else {

        $aForeigns[$sNamespace] = true;
      }
    }

    $mResult = $newElement;

    $aParsers = array();

    foreach ($aForeigns as $sNamespace => $bVal) {

      $parser = $this->validateParser($sNamespace, 'attribute');

      if ($parser) {

        $aParsers[] = $parser;
        $mResult = $parser->parseAttributes($el, $newElement, $mResult);
      }
      else {

        $this->throwException(sprintf('Unknown attribute with @namespace %s in %s', $sNamespace, $el->asToken()));
      }
    }

    $this->setAttributeParsers($aParsers);

    return $mResult;
  }

  protected function getAttributeParsers() {

    return $this->aAttributeParsers;
  }

  protected function setAttributeParsers(array $aParsers = array()) {

    $this->aAttributeParsers = $aParsers;
  }

  protected function getLastElement() {

    return $this->lastElement;
  }

  protected function setLastElement($lastElement) {

    $this->lastElement = $lastElement;
  }
}


/*
  protected function createComponent(dom\element $el) {

    $aClass = explode('\\', get_class($this));

    \Sylma::load('/core/functions/Text.php');
    $sName = text\toggleCamel($el->getName());

    if ($dir = $this->getComponentsDirectory($el->getNamespace())) {

      $sClass = str_replace('/', '\\', (string) $dir) . $sName;
    }
    else {

      $sClass = '\\' . implode('\\', array_slice($aClass, 0, -1)) . '\\' . $sName;
    }

    return new $sClass($this, $el);
  }

  protected function getComponentsDirectory($sNamespace) {

    return $this->componentsDirs[$sNamespace];
  }

  protected function setComponentsDirectory(fs\directory $dir, $sNamespace = '') {

    if (!$sNamespace) $sNamespace = $this->getNamespace();

    $this->componentsDirs[$sNamespace] = $dir;
  }
*/