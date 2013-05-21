<?php

namespace sylma\schema\cached;
use sylma\core;

class Simple extends Basic {

  protected $sValue;

  public function __construct($sValue, array $aSettings = array()) {

    $this->setValue($sValue);
    $this->setSettings($aSettings);
  }

  public function setValue($sValue) {

    $this->sValue = $sValue;
  }

  public function getValue() {

    return $this->sValue;
  }

  public function validate() {

    if (!$this->getValue()) {

      $this->addMessage("This field must be filled", array('alias' => $this->read('alias')));
    }

    return is_string($this->getValue());
  }

  public function escape() {

    return "'".addslashes($this->getValue())."'";
  }
}

