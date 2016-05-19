<?php

namespace sylma\storage\sql\alter\component;
use sylma\core, sylma\storage\sql, sylma\storage\fs;

class Reference extends sql\schema\component\Reference implements sql\alter\alterable {

  protected $bBuilded = false;

  public function asUpdate() {

    return '';
  }

  protected function loadElementRef(fs\file $file = null) {

    if (!$this->bBuilded && !$this->getMaxOccurs(true)) {

      $file = $this->getElementRefFile();
      $this->getParent()->buildSchema($file);

      $this->bBuilded = true;
    }

    return parent::loadElementRef($file);
  }

  public function asCreate() {

    return '';
  }

  public function asString() {

    $this->getElementRef();

    return '';
  }
}

