<?php

namespace sylma\parser\languages\php\basic;
use sylma\core, sylma\parser\languages\common;

class Instanciate extends Controled implements common\_object, common\_instance, common\argumentable {

  protected $instance;

  public function __construct(common\_window $controler, common\_instance $instance, array $aArguments = array()) {

    $this->setControler($controler);

    $this->instance = $instance;
    $this->aArguments = $aArguments;
  }

  public function getInterface() {

    return $this->instance->getInterface();
  }

  protected function getArguments() {

    return $this->aArguments;
  }

  public function addContent($mVar) {

    return $this->getControler()->add($mVar);
  }

  public function asArgument() {

    return $this->getControler()->createArgument(array(
      'instanciate' => array(
        '@class' => $this->getInterface()->getName(),
        '#argument' => $this->getArguments(),
    )));
  }
}