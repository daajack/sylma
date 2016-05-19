<?php

namespace sylma\schema\xsd;
use sylma\core, sylma\dom, sylma\schema, sylma\storage\fs;

class Document extends Elemented implements schema\parser\schema {

  protected $children = array();

  public $useElementForm = true;
  public $useAttributeForm = false;

  public function importSchema($namespace) {

    if ($this->getParent(false)) {

      $result = $this->getParent()->importSchema($namespace);
    }
    else {

      $result = parent::importSchema($namespace);
    }

    return $result;
  }

  public function addSchema(fs\file $file, $force = false) {

    if ($this->getParent(false)) {

      $result = $this->getParent()->addSchema($file, $force);
    }
    else {

      $result = parent::addSchema($file, $force);
    }

    return $result;
  }

  public function getElement($name = '', $namespace = '', $debug = true) {

    if ($this->getParent(false)) {

      $result = $this->getParent()->getElement($name, $namespace, $debug);
    }
    else {

      $result = parent::getElement($name, $namespace, $debug);
    }

    return $result;
  }

  public function getTypes() {

    if ($this->getParent(false)) {

      $result = $this->getParent()->getTypes();
    }
    else {

      $result = parent::getTypes();
    }

    return $result;
  }

  public function getElements() {

    if ($this->getParent(false)) {

      $result = $this->getParent()->getElements();
    }
    else {

      $result = parent::getElements();
    }

    return $result;
  }

  public function getType($name, $namespace, $debug = true) {

    if ($this->getParent(false)) {

      $result = $this->getParent()->getType($name, $namespace, $debug);
    }
    else {

      $result = parent::getType($name, $namespace, $debug);
    }

    return $result;
  }
}
