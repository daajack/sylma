<?php

namespace sylma\schema\parser;
use sylma\core, sylma\dom, sylma\schema\parser, sylma\parser\reflector;

class Handler extends reflector\handler\Elemented {

  protected $sDefaultNamespace = '';

  protected $aElements = array();
  protected $aTypes = array();
  protected $aBaseTypes = array();

  public function parseRoot(dom\element $el) {

    $this->loadBaseTypes(array('string', 'integer'));
  }

  protected function setDefaultNamespace($sNamespace) {

    $this->sDefaultNamespace = $sNamespace;
  }

  protected function getDefaultNamespace() {

    return $this->sDefaultNamespace;
  }

  public function loadElement($sName, $sNamespace) {

    $result = null;

    if (isset($this->aElements[$sNamespace][$sName])) {

      $result = $this->aElements[$sNamespace][$sName];
    }

    return $result;
  }

  protected function addElement(parser\element $element) {

    $this->aElements[$element->getNamespace()][$element->getName()] = $element;

    return $element;
  }

  protected function loadBaseTypes($aTypes) {

    $aResult = array();

    foreach ($aTypes as $sType) {

      $aResult[$sType] = $type = $this->loadSimpleComponent('component/baseType', $this);
      $type->setName($sType);
      $type->setNamespace($this->getNamespace(self::PREFIX));
    }

    $this->aBaseTypes = $aResult;
  }

  protected function loadType($sName, $sNamespace) {

    $result = null;

    if ($sNamespace == $this->getNamespace(self::PREFIX)) {

      $result = $this->aBaseTypes[$sName];
    }

    if (isset($this->aTypes[$sNamespace][$sName])) {

      $result = $this->aTypes[$sNamespace][$sName];
    }

    return $result;
  }

  //abstract protected function lookupType($sName, $sNamespace = '');

  protected function addType(parser\type $type) {

    $sNamespace = $type->getNamespace();

    if (!array_key_exists($sNamespace, $this->aTypes)) {

      $this->aTypes[$sNamespace] = array();
    }

    $this->aTypes[$sNamespace][$type->getName()] = $type->getNamespace();

    return $type;
  }
}
