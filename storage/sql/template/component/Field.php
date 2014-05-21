<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\storage\sql, sylma\parser\languages\common;

abstract class Field extends sql\schema\component\Field implements sql\template\pathable {

  protected $parent;
  protected $query;
  protected $var;

  protected function isSub() {

    return $this->getParent()->isSub();
  }

  public function getQuery() {

    return $this->getParent()->getQuery();
  }

  protected function getSource() {

    return $this->getParent()->getSource();
  }

  public function reflectRead() {

    //$this->launchException('Should not be used');
    return null;
  }

  public function reflectApply($sMode = '', array $aArguments = array()) {

    return $this->reflectApplySelf($sMode, $aArguments);
  }

  protected function lookupTemplate($sMode) {

    return $this->getParser()->lookupTemplate($this, 'element', $sMode);
  }

  protected function reflectApplySelf($sMode = '', array $aArguments = array()) {

    if ($result = $this->lookupTemplate($sMode)) {

      $result->setTree($this);
      $result->sendArguments($aArguments);
    }

    return $result;
  }

  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {

    switch ($sName) {

      case 'value' : $result = $this->reflectRead(); break;
      case 'is-optional' : $result = $this->isOptional(); break;
      case 'this' : $result = $aPath ? $this->getParser()->parsePathToken($this, $aPath, $sMode) : $this->reflectApply($sMode); break;
      case 'name' : $result = $this->getName(); break;
      case 'alias' : $result = $this->reflectFunctionAlias($sMode, $bRead, $sArguments); break;
      case 'apply' : $result = $this->reflectApply($sMode); break;
      case 'title' : $result = $this->getTitle(); break;
      case 'parent' :

        $result = $this->getParser()->parsePathToken($this->getParent(), $aPath, $sMode, $bRead, $aArguments);

        break;

      default :

        $this->launchException(sprintf('Uknown function "%s()"', $sName), get_defined_vars());
    }

    return $result;
  }

  public function reflectApplyAll($sMode, array $aArguments = array()) {

    $this->launchException('Cannot reflect all on field');
  }

  protected function reflectSelf($bHTML = false) {

    $aArguments = array($this->getAlias(), false);

    if (!$bHTML) {

      $bHTML = $this->getType()->doExtends($this->getParser()->getType('html', $this->getNamespace('sql')));
    }

    if ($bHTML) {

      $aArguments[] = true;
    }

    return $this->getWindow()->createCall($this->getSource(), 'read', 'php-string', $aArguments);
  }

  public function reflectApplyDefault($sPath, array $aPath, $sMode, $bRead = false, array $aArguments = array()) {

    $result = $this->getParser()->reflectApplyDefault($this, $sPath, $aPath, $sMode, $bRead, $aArguments);

    return $result;
  }
}

