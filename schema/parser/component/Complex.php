<?php

namespace sylma\schema\parser\component;
use sylma\core, sylma\dom, sylma\schema\parser, sylma\parser\reflector;

class Complex extends Type implements parser\type, parser\type\complex {

  public $particle = null;

  public function isComplex() {

    return true;
  }

  public function isSimple() {

    return false;
  }

  protected function setParticle(parser\particle $particle) {

    $this->particle = $particle;
  }

  protected function addElement(schema\parser\element $element) {

    $sNamespace = $element->getNamespace();

    if (!isset($this->elements[$sNamespace])) {

      $this->elements[$sNamespace] = array();
    }

    $this->elements[$sNamespace][$element->getName()] = $element;

    return $element;
  }

  public function getElement($sName, $sNamespace) {

    return isset($this->elements[$sNamespace][$sName]) ? $this->elements[$sNamespace][$sName] : null;
  }

  public function __clone() {

    $this->particle = clone $this->particle;
  }
}

