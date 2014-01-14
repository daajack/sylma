<?php

namespace sylma\parser\reflector\basic;
use \sylma\core;

abstract class Namespaced extends core\module\Domed {

  protected $aManagedNS = array();

  protected function setUsedNamespace($sNamespace) {

    $this->aManagedNS[] = $sNamespace;
  }

  public function getUsedNamespaces() {

    return $this->aManagedNS;
  }

  protected function setUsedNamespaces(array $aNamespaces) {

    //$this->setNamespaces($aNamespaces);
    $this->aManagedNS = $aNamespaces;
  }

  protected function useNamespace($sNamespace) {

    return in_array($sNamespace, $this->aManagedNS);
  }

  protected function setNamespace($sNamespace, $sPrefix = null, $bDefault = true) {

    if ($bDefault) $this->setUsedNamespace($sNamespace);

    return parent::setNamespace($sNamespace, $sPrefix, $bDefault);
  }

  protected function launchException($sMessage, array $aVars = array(), array $mSender = array()) {

    if ($this instanceof core\tokenable) $mSender[] = $this->asToken();

    return parent::launchException($sMessage, $aVars, $mSender);
  }
}
