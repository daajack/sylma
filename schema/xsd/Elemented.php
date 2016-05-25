<?php

namespace sylma\schema\xsd;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\schema, sylma\storage\fs;

class Elemented extends schema\parser\Handler implements reflector\elemented, core\arrayable {

  const NS = 'http://www.w3.org/2001/XMLSchema';
  const PREFIX = 'xs';

  const SSD_NS = 'http://2013.sylma.org/schema/ssd';
  const SSD_PREFIX = 'ssd';
  const SSD_TYPES = '/#sylma/schema/ssd/simple.xsd';

  /**
   * List of stringed files
   */
  protected $files = array();
  protected $documents = array();
  protected $element;
  protected $children = array();

  public function parseRoot(dom\element $el) {

    //$this->setNode($el);

    if ($el->getName() != 'schema') {

      $this->throwException(sprintf('Bad root %s', $el->asToken()));
    }

    $this->loadCatalog();
    $this->loadBaseTypes(array(
      'string' => self::NS,
      'integer' => self::NS,
      'float' => self::NS,
      'boolean' => self::NS,
      'NMTOKEN' => self::NS,
      'ID' => self::NS,
    ));

    $this->setNamespace(self::SSD_NS, self::SSD_PREFIX, false);

    $doc = $this->setDocument($el->getHandler());
/*    $file = $this->setFile($doc->getFile());

    $this->parseFile($file);
*/
    if ($file = $doc->getFile()) {

      $this->parseFile($file);
    }
    else {

      $this->parseDocument($doc);
    }
  }

  protected function loadCatalog() {

    $this->setNamespace('urn:oasis:names:tc:entity:xmlns:xml:catalog', 'cat');
    $this->setDirectory(__FILE__);

    $doc = $this->getDocument($this->read('catalog'));
    $children = $doc->getRoot()->queryx('//cat:group/*');

    foreach ($children as $child) {

      $schemas[$child->readx('@name')] = '/#sylma/' . $child->readx('@uri');
    }

    $this->schemas = $schemas;
  }

  protected function parseFile(fs\file $file) {

    $doc = $file->asDocument();

    $this->setDirectory(__FILE__);
    $this->setFile($file);

    return $this->parseDocument($doc);
  }

  protected function parseDocument(dom\document $doc) {

    $this->setDocument($doc);
    //$this->setDocument($this->createDocument($doc));
    //$this->getDocument()->getRoot()->set();

    $this->loadTargetNamespace($doc);
    $this->loadDefaultNamespace($doc);
    $this->loadQualifications($doc);
//dsp($doc);
//dsp($this->getTargetNamespace());
    $result = $this->browseSchemaChild($doc->getRoot());
    //$this->children = array_merge($this->children, $children);
//dsp($this->getFile('', false), $this->getParent(false));
//dsp($this->children);
    //end($this->children);
//dsp($doc);
//dsp($result);
    return end($result);

  }

  protected function loadTargetNamespace(dom\document $doc) {

    $this->setNamespace($this->parseTargetNamespace($doc), self::TARGET_PREFIX);
  }

  protected function loadQualifications(dom\document $doc) {

    $useForm = $doc->readx('@elementFormDefault', array(), false);
    $this->useElementForm = !$useForm || $useForm === 'qualified';

    $useForm = $doc->readx('@attributeFormDefault', array(), false);
    $this->useAttributeForm = $useForm && $useForm === 'qualified';
  }

  protected function parseTargetNamespace(dom\document $doc) {

    return $doc->readx('@targetNamespace');
  }

  protected function lookupNamespace($sPrefix, dom\element $context = null) {

    $sNamespace = '';

    if ($context) {

      if (!$sNamespace = $context->lookupNamespace($sPrefix)) {

        $sNamespace = $context->getHandler()->getRoot()->lookupNamespace($sPrefix);
      }
    }

    if (!$sNamespace) {

      $sNamespace = $this->getDocument()->getRoot()->lookupNamespace($sPrefix);
    }

    return $sNamespace ? $sNamespace : $this->getNamespace($sPrefix);
  }

  /**
   * @param string $sName ([prefix]:)?[name]
   * @param $source
   * @param $context
   * @return type
   */
  public function parseName($sName, schema\parser\namespaced $source = null, dom\element $context = null) {

    $iPrefix = strpos($sName, ':');

    if ($iPrefix !== false) {

      $sPrefix = substr($sName, 0, $iPrefix);
      $sNamespace = $this->lookupNamespace($sPrefix, $context);

      $sName = substr($sName, $iPrefix + 1);
    }
    else {

      $sNamespace = $source && $source->getNamespace() ? $source->getNamespace() : $this->getTargetNamespace();
    }

    return array($sNamespace, $sName);
  }

  /**
   *
   * @param string $name
   * @param boolean $bDebug
   * @return type\Basic
   */
  protected function loadDefaultNamespace(dom\document $doc) {

    $this->setDefaultNamespace($doc->getRoot()->lookupNamespace());
  }

  protected function addSchemaChild(dom\node $node) {

    $child = null;

    if ($node->getType() !== $node::COMMENT) {

      if ($node->getName() === 'notation') {

        return null;
      }

      $child = $this->parseComponent($node);

      switch ($node->getName()) {

        case 'complexType' :
        case 'simpleType' : $this->addType($child); break;
        case 'element' : $this->addElement($child); break;
        case 'attribute' :
        case 'attributeGroup' :
        case 'annotation' :
        case 'group' : break;
        case 'import' :
        case 'include' :

          //$this->getDocument()->add($node);
          $child = null;
          break;

        default: $this->launchException('Uknown element : ' . $node->asToken(), get_defined_vars());
      }

      if ($child) {

        $this->children[] = $child;
      }
    }

    return $child;
  }

  protected function browseSchemaChild(dom\element $parent) {

    $result = array();

    foreach ($parent->getChildren() as $el) {

      $result[] = $this->addSchemaChild($el);
    }

    return $result;
  }

  public function getElement($name = '', $namespace = '', $debug = true) {

    if (!$name) {

      $result = current($this->getElements());
    }
    else {

      if (!$namespace) {

        $namespace = $this->getTargetNamespace();
      }

      if (!isset($this->elements[$namespace]) || !isset($this->elements[$namespace][$name])) {

        if ($debug) {

          $this->launchException('Cannot find element : ' . $namespace . ':' . $name);
        }
        else {

          $result = null;
        }
      }
      else {

        $result = $this->elements[$namespace][$name];
      }
    }

    return $result;
  }

  public function getElements() {

    $result = array();

    foreach ($this->elements as $namespace) {

      foreach ($namespace as $element) {

        $result[] = $element;
      }
    }

    return $result;
  }

  public function getType($name, $namespace, $debug = true) {

    if (!isset($this->types[$namespace][$name])) {

      if ($debug) {

        $this->launchException("Cannot find type $namespace:$name");
      }
      else {

        $result = null;
      }
    }
    else {

      $result = $this->types[$namespace][$name];
    }

    return $result;
  }

  public function getTypes() {

    $result = array();

    foreach ($this->types as $namespace) {

      foreach ($namespace as $type) {

        $result[] = $type;
      }
    }

    return $result;
  }

  public function importSchema($namespace) {

    if (!isset($this->schemas[$namespace])) {

      $this->launchException('Schema not found', get_defined_vars());
    }

    $file = $this->getFile($this->schemas[$namespace]);
    $this->addSchema($file);
  }

  public function addSchema(fs\file $file, $force = false) {
//dsp($file);
    $result = null;
/*
if (0 && (string) $file === '/sylma/modules/users/group.xql') {
  dsp($this->getArguments());
  $this->launchException ('test');
}
*/
    if ($force || !in_array((string) $file, $this->files)) {

      $this->log($file->asToken());
      $this->files[] = (string) $file;

      if (!$parent = $this->getParent(false)) {

        $parent = $this;
      }

      $doc = new static($this->getRoot(), $parent, $this->getSettings());
      $result = $doc->parseFile($file);

      $this->retrieveDocument($doc);
    }

    return $result;
  }

  protected function retrieveDocument(Document $doc) {

    $this->children = array_merge($this->children, $doc->children);

    foreach ($doc->elements as $ns) {

      foreach ($ns as $element) {

        $this->addElement($element);
      }
    }

    foreach ($doc->types as $ns) {

      foreach ($ns as $type) {

        $this->addType($type);
      }
    }
  }

  public function addSchemaDocument(dom\document $doc) {

    if (!$parent = $this->getParent(false)) {

      $parent = $this;
    }

    $schema = new static($this->getRoot(), $parent, $this->getSettings());

    $result = $schema->parseDocument($doc);
    $this->retrieveDocument($schema);

    return $result;
  }

  public function asArray() {
//dsp(count($this->children));
//dsp($this->children);
    return array(
      'namespace' => $this->getNamespace(),
      'root' => $this->children,
    );
  }
}

