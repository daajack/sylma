<?php

namespace sylma\parser\js\binder;
use sylma\core, sylma\parser, sylma\dom, sylma\parser\languages\common, sylma\parser\languages\js;

class Reflector extends parser\reflector\basic\Domed implements parser\reflector\elemented, parser\reflector\attributed {

  const NS = 'http://www.sylma.org/parser/js/binder';

  const PARENT_METHOD = 'getParentParser';
  const PARENT_RETURN = '\sylma\parser\action\cached';
  const PARSER_METHOD = 'loadParser';

  const CACHED_NS = Cached::NS;

  const JS_TEMPLATES_PATH = 'sylma.binder.classes';
  const JS_OBJECTS_PATH = 'sylma.ui.tmp';

  const CONTEXT_ALIAS = Cached::CONTEXT_ALIAS;

  protected $window;
  protected $sPath = '';
  protected $aObjects = array();
  protected $bRootElement = true;
  protected $context;

  protected $iDepth = 0;

  public function __construct(parser\reflector\domed $parent) {

    $this->setDirectory(__file__);
    $this->setArguments('settings.yml');

    $this->setNamespace(self::NS, 'self');
    $this->setNamespace(self::CACHED_NS, 'cached', false);

    $this->addParser($parent->getWindow());

    $this->setParent($parent);

    $this->initWindow();
    $this->prepareParent();
  }
/*
  public function getPath() {

    if (!$this->sPath) {

      $this->throwException('No path defined');
    }

    return $this->sPath;
  }

  public function setPath($path) {

    $this->sPath = $path;
  }
*/
  protected function addParser(common\_window $window) {

    $parent = $window->createCall($window->getSelf(), self::PARENT_METHOD, self::PARENT_RETURN, array(true));
    $call = $window->createCall($parent, self::PARSER_METHOD, 'php-boolean', array($this->getNamespace('cached')));

    $window->add($call);
  }

  protected function getDepth() {

    return $this->iDepth;
  }

  public function parseRoot(dom\element $el) {

    $result = $this->parseElementSelf($el);

    return $result;
  }

  /**
   * @return common\_var
   */
  public function getContext() {

    return $this->context;
  }

  public function setContext(common\_var $context) {

    $this->context = $context;
  }

  protected function prepareParent() {

    $window = $this->getParent()->getWindow();
    $manager = $window->addControler('parser');

    $call = $window->createCall($manager, 'getContext', '\parser\context', array(self::CONTEXT_ALIAS));
    $this->setContext($call->getVar());
  }

  protected function initWindow() {

    $window = $this->create('window', array($this, $this->getArgument('classes/js')));
    $this->setWindow($window);

    $root = $window->createObject();
    //$window->assignProperty(self::JS_TEMPLATES_PATH, $root);
    $this->startObject($root);

    return $root;

    //echo $this->show($window->getContexts(), false);
/*

    $window->insert("sylma.binder.classes = {
      test1 : {
        properties : {
          value : 'hello'
        },
        events : {
          click : {
            callback : function() {
              $(this).retrieve('sylma-object').test();
            }
            //target : '.test1-1234'
          }
        }
      }
    }");

    $window->stopContext();
 */
  }

  public function getObject() {

    return end($this->aObjects);
  }

  protected function startObject(common\_object $object) {

    $this->aObjects[] = $object;
  }

  protected function stopObject() {

    return array_pop($this->aObjects);
  }

  /**
   *
   * @return js\window
   */
  protected function getWindow() {

    return $this->window;
  }

  protected function setWindow(common\_window $window) {

    $this->window = $window;
  }

  protected function parseElementSelf(dom\element $el) {

    $result = null;

    switch ($el->getName()) {

      case 'event' : $result = $this->reflectEvent($el); break;
      case 'property' :

      break;

      default : $this->throwException(sprintf('Unknown element %s', $el->asToken()));
    }

    return $result;
  }

  public function parseAttributes(dom\node $el, dom\element $resultElement, $result) {

    if ($el->readx('@self:class', $this->getNS())) {

      $result = $this->reflectObject($el, $resultElement);
    }

    $this->iDepth++;

    return $result;
  }

  public function onClose() {

    if (!$this->getDepth()) {

      $sContent = $this->getWindow()->objAsString($this->getObject());
      $this->getContext()->call('add', array($sContent), '\parser\context', false);
    }

    $this->iDepth--;
    $this->stopObject();
  }

  protected function reflectEvent(dom\element $el) {

    $window = $this->getWindow();

    $function = $window->createFunction(array('e'), $this->parseEventContent($el->read()));
    $sName = $el->readAttribute('name');

    $this->getObject()->setProperty("events.$sName.callback", $function);
  }

  protected function parseEventContent($sContent) {

    $aReplaces = array(
      '/%([\w-_]+)%/' => '\$(this).retrieve(\'sylma-$1\')',
      '/%([\w-_]+)\s*,\s*([^%]+)%/' => '\$(this).store(\'sylma-$1\', $2)');

    $sResult = preg_replace(array_keys($aReplaces), $aReplaces, $sContent);

    return $sResult;
  }

  protected function reflectObject(dom\element $el, dom\element $resultElement) {

    $result = $this->buildElement($el, $resultElement);
    $obj = $this->getWindow()->createObject();

    $this->getObject()->setProperty($result->readAttribute('binder'), $obj);
    $this->startObject($obj);

    return $result;
  }

  protected function buildElement(dom\element $el, dom\element $resultElement) {

    $sClass = $el->readx('@self:class', $this->getNS());
    $sName = $el->readx('@self:name', $this->getNS(), false);
    $sParent = $el->readx('@self:parent', $this->getNS(), false);

    if (!$this->getDepth() && !$sParent) {

      $sParent = self::JS_OBJECTS_PATH;
    }

    if ($this->getDepth() && $sParent) {

      $this->throwException(sprintf('@attribute parent must only appears on root element %s', $el->asToken()));
    }

    $aAttributes = array(
      'class' => $sClass,
      'name' => $sName,
      'id' => $resultElement->readAttribute('id', null, false),
      'binder' => uniqid('sylma'),
      'parent' => $sParent,
    );

    $result = $resultElement->createElement('object', $resultElement, $aAttributes, $this->getNamespace('cached'));

    return $result;
  }
}

?>
