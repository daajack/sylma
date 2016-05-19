<?php

namespace sylma\schema\parser;
use sylma\core, sylma\dom, sylma\schema\parser, sylma\parser\reflector;

class Handler extends reflector\handler\Elemented {

  const TARGET_PREFIX = 'target';
  const TYPE_PREFIX = 'type';

  protected $sDefaultNamespace = '';

  protected $elements = array();
  protected $types = array();

  public function getTargetNamespace() {

    return $this->getNamespace('target');
  }

  protected function setDefaultNamespace($sNamespace) {

    $this->sDefaultNamespace = $sNamespace;
  }

  protected function getDefaultNamespace() {

    return $this->sDefaultNamespace;
  }

  protected function addElement(parser\element $element) {

    $sNamespace = $element->getNamespace();

    if (!isset($this->elements[$sNamespace])) {

      $this->elements[$sNamespace] = array();
    }

    $this->elements[$sNamespace][$element->getName()] = $element;

    return $element;
  }

  protected function loadBaseTypes($aTypes) {

    $aResult = array();

    foreach ($aTypes as $sType => $sNamespace) {

      $type = $this->loadSimpleComponent('component/baseType', $this);

      $type->setName($sType);
      $type->setNamespace($sNamespace, self::TYPE_PREFIX);
      $type->setNamespaces($this->getNS());

      $this->children[] = $type;
      $this->addType($type);
    }
  }

  protected function addType(parser\type $type) {

    $sNamespace = $type->getNamespace();

    if (!array_key_exists($sNamespace, $this->types)) {

      $this->types[$sNamespace] = array();
    }

    $this->types[$sNamespace][$type->getName()] = $type;

    return $type;
  }
}

