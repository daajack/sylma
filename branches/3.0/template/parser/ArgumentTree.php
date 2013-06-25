<?php

namespace sylma\template\parser;
use sylma\core, sylma\template as tpl, sylma\parser\reflector;

class ArgumentTree extends reflector\component\Foreigner implements tpl\parser\tree {

  protected $handler;
  protected $options;

  protected $sName;
  protected $bRoot = false;

  public function setOptions(core\argument $arg) {

    $this->setNamespace($arg->getNamespace());
    $this->setName($arg->getRoot());

    $this->options = $arg;
  }

  protected function getOptions() {

    return $this->options;
  }

  public function isRoot($bValue = null) {

    if (is_bool($bValue)) $this->bRoot = $bValue;

    return $this->bRoot;
  }

  protected function setName($sName) {

    $this->sName = $sName;
  }

  protected function getName() {

    return $this->sName;
  }

  public function reflectRead(array $aArguments = array()) {

    return $this->getParser()->trimString($this->getOptions()->read());
  }

  public function reflectApply($sMode = '', array $aArguments = array()) {

    if ($result = $this->getParser()->lookupTemplate($this->getName(), $this->getNamespace(), $sMode, $this->isRoot())) {

      $result->setTree($this);
      $result->sendArguments($aArguments);
    }
    else {

      if (!$sMode) {

        $this->launchException('Cannot apply tree without mode');
      }
    }

    return $result;
  }

  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {

    $aResult = array();

    switch ($sName) {

      case 'name' :

        $aResult[] = $this->getOptions()->getRoot();
        break;

      default :

        $this->launchException("Function '$sName' unknown in argument tree");
    }

    return $aResult;
  }

  public function reflectApplyAll($sMode, array $aArguments = array()) {

    $aResult = array();

    foreach ($this->getOptions() as $child) {

      $aResult[] = $this->loadChild($child)->reflectApply($sMode, $aArguments);
    }

    return $aResult;
  }

  protected function loadChild(core\argument $content) {

    $result = new static($this->getParser());
    $result->setOptions($content);

    return $result;
  }

  public function reflectApplyDefault($sPath, array $aPath = array(), $sMode = '', $bRead = false) {

    $args = $this->getOptions();

    if ($aPath) {

      $this->launchException('Not ready, should avoid path parsing');
    }

    if ($bRead) {

      $result = $args->read($sPath);
    }
    else {

      $el = $args->get($sPath, false, false);

      if ($el->getType() == $el::ELEMENT && $el->isComplex()) {

        $tree = $this->loadChild($args->get($sPath));
        $result = $this->getParser()->applyArrayTo($tree, $aPath, $sMode);
      }
      else {

        $result = $this->reflectApplyDefault($sPath, $aPath, $sMode, true);
      }
    }

    return $result;
  }

  public function asToken() {

    return "[Tree]" . $this->getNamespace() . ':' . $this->getName();
  }
}

