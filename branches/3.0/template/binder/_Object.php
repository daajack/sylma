<?php

namespace sylma\template\binder;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\parser\languages\common;

class _Object extends Basic implements common\arrayable {

  const JS_LOAD_CONTEXT = 'js-load';
  const PARENT_PATH = 'sylma.ui.tmp';

  protected $name;
  protected $id;

  protected $class;
  protected $var;
  protected $sJSClass;

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->loadParentName();
    $this->setID($this->loadUnique());
  }

  public function setClass(_Class $class) {

    $this->class = $class;
  }

  protected function loadName() {

    if (!$mName = $this->readx('@js:name', false)) {

      $mName = $this->loadUnique();
    }

    $this->setName($mName);
    return $this->getName();
  }

  protected function setName($mName) {

    $this->name = $mName;
  }

  protected function getName() {

    return $this->name;
  }

  protected function getClass() {

    return $this->class;
  }

  protected function getJSClass() {

    return $this->sJSClass;
  }

  protected function setJSClass($sName) {

    $this->sJSClass = $sName;
  }

  public function setSubObject($sName, $val) {

    //$this->getObject()->setProperty('objects.' . $sName, $val);
    //$this->aObjects[$sName] = $obj;
  }

  protected function isRoot() {

    return $this->getClass()->isRoot();
  }

  protected function setVar(common\_var $obj) {

    $this->var = $obj;
  }

  protected function getVar() {

    if (!$this->var) {

      $this->launchException('No var defined');
    }

    return $this->var;
  }

  protected function loadParentName() {

    if (!$sParent = $this->readx('@js:parent')) {

      $sParent = self::PARENT_PATH;
    }

    $this->setParentName($sParent);
    return $this->getParentName();
  }

  protected function getParentName() {

    return $this->sParent;
  }

  protected function setParentName($sParent) {

    $this->sParent = $sParent;
  }

  protected function addToWindow() {

    $window = $this->getParser()->getPHPWindow();
    $parent = $this->getParser()->getObjects();
    $context = $window->getVariable('contexts')->call('get', array(self::JS_LOAD_CONTEXT), '\sylma\core\argument', true);

    $aContent = array('sylma.ui.load(', array($this->getParentName(), ', ', $parent->call('asJSON', array(), 'php-string'), ')'));
    $window->add($context->call('add', array($window->createString($aContent))));
  }

  protected function buildObject() {

    $name = $this->loadName();

    if ($this->isRoot()) {

      $var = $this->getParser()->getObjects();
      $mPath = $name;
    }
    else {

      $parent = $this->getParser()->getObject();

      $var = $parent->getVar();
      $mPath = $this->getParser()->getPHPWindow()->toString(array('objects.', $name));
    }

    $arg = $var->call('set', array(
      $mPath,
      array(
        'extend' => $this->getClass()->getExtend(),
        'binder' => $this->getClass()->getID(),
        'id' => $this->getID(),
      ),
      true,
    ), '\sylma\core\argument', true);

    $this->setVar($arg);
  }

  public function setOption($sName, $val) {

    $this->getVar()->call('set', array('options.' . $sName, $val), 'php-boolean', true);
    //$this->aProperties[$sName] = $val;
  }

  protected function loadUnique() {

    $window = $this->getParser()->getPHPWindow();
    $result = $window->addVar($window->callFunction('uniqid', 'php-string', array('sylma-')));

    return $result;
  }

  protected function setID($mID) {

    $this->id = $mID;
  }

  protected function getID() {

    return $this->id;
  }

  public function asArray() {

    $this->buildObject();
    $this->getParser()->startObject($this);
    $this->startLog("Object [{$this->getClass()->getExtend()}]");

    $element = $this->getClass()->getElement();
    $element->setAttribute('id', $this->getID());

    $aElement = $this->getParser()->getPHPWindow()->parseArrayables(array($element));

    if ($this->isRoot()) {

      $this->addToWindow();
    }

    $this->getParser()->stopObject();
    $this->stopLog();

    return array(
      $aElement,
    );
  }

}

