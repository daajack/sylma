<?php

namespace sylma\template\binder\component;
use sylma\core, sylma\dom, sylma\parser\languages\common, sylma\template\binder, sylma\parser\languages\js;

class Method extends Basic implements common\arrayable {

  protected $sName;
  protected $sValue;

  public function parseRoot(dom\element $el) {

    $this->setNode($el);

    $window = $this->getWindow();

    $this->loadName();
    $this->loadValue($el);

    $function = $window->createFunction($this->loadArguments(), $this->getValue());

    $this->addContentToClass($this->getParser()->getObject(), $function);
  }

  protected function loadArguments() {

    if ($arguments = $this->readx('@arguments')) {

      $result = explode(',', $arguments);
    }
    else {

      $result = array();
    }

    return $result;
  }

  protected function addContentToClass(binder\_class $class, js\basic\instance\_Function $function) {

    $class->setMethod($this->getName(), $function);
  }

  protected function loadName() {

    $this->setName($this->readx('@name'));
  }

  protected function loadValue(dom\element $el) {

    $this->sValue = $el->read();
  }

  protected function getValue() {

    return $this->sValue;
  }

  protected function setName($sName) {

    $this->sName = $sName;
  }

  public function getName() {

    return $this->sName;
  }

  public function asArray() {

    return array();
  }
}

