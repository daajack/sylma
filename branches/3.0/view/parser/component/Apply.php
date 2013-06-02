<?php

namespace sylma\view\parser\component;
use sylma\core, sylma\dom, sylma\template\parser, sylma\parser\languages\common;

class Apply extends parser\component\Apply implements common\arrayable {

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
  }

  public function build() {

    $sSelect = $this->readx('@select');
    $sMode = $this->readx('@mode');

    if ($sImport = $this->readx('@import')) {

        $tree = $this->getParser()->importTree($sImport);
        $result = $this->getParser()->applyPathTo($tree, $sSelect, $sMode);
    }
    else {

      $this->startLog("Apply ({$sSelect})");

      if (!$sSelect) {

        if ($sConstant = $this->readx('@use')) {

          $sSelect = $this->getParser()->getConstant($sConstant);
        }
      }

      $aArguments = $this->getTemplate()->parseArguments($this->getNode()->getChildren());

      $result = $this->getTemplate()->applyPath($sSelect, $sMode, $aArguments);
    }

    $aResult = $this->getWindow()->parseArrayables(array($result));

    $this->stopLog();

    return $aResult;
  }

  public function _onAdd() {

    $window = $this->getWindow();
    $window->add($window->toString($this->build()));
  }

  public function asArray() {

    return $this->build();
  }
}
