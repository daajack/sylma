<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\storage\sql, sylma\template, sylma\schema\xsd, sylma\schema\parser;

class Simple extends xsd\component\SimpleType implements sql\template\pathable {

  protected $elementRef;

  public function setElementRef(parser\element $element) {

    $this->elementRef = $element;
  }

  protected function getElementRef() {

    return $this->elementRef;
  }

  protected function getQuery() {

    return $this->getParent()->getQuery();
  }

  protected function getVar() {

    return $this->getParent()->getVar();
  }

  public function reflectApplyPath(array $aPath, $sMode = '') {

    //$this->launchException('Should not be called');

    if (!$aPath) {

      $result = $this->reflectApplySelf($sMode);
    }
    else {

      $result = $this->parsePathToken($aPath, $sMode);
    }

    return $result;
  }

  public function reflectApplyDefault($sPath, array $aPath, $sMode, $bRead) {

    $this->launchException('Not ready');
  }

  public function reflectApply($sMode = '') {

    return $this->reflectRead();;
  }

  public function reflectApplyFunction($sName, array $aPath, $sMode) {

    switch ($sName) {

      case 'name' : $result = $this->getName(); break;

      default :

        $this->launchException('Unknown function', get_defined_vars());
    }

    return $result;
  }

  protected function parsePathToken($aPath, $sMode) {

    return $this->getParser()->parsePathToken($this, $aPath, $sMode);
  }

  protected function lookupTemplate($sMode) {

    return $this->getParser()->lookupTemplate($this->getElementRef(), 'type', $sMode);
  }

  protected function reflectApplySelf($sMode = '') {

    if ($result = $this->lookupTemplate($sMode)) {

      $result->setTree($this);
    }
    else {

      $result = $this->reflectRead();
    }

    return $result;
  }

  public function reflectRead() {

    $this->launchException('Cannot simply read type');
  }
}

