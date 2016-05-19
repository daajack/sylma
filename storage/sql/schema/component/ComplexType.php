<?php

namespace sylma\storage\sql\schema\component;
use sylma\core, sylma\dom, sylma\schema;

class ComplexType extends schema\xsd\component\ComplexType {

  const NS = 'http://2013.sylma.org/storage/sql';
  const NAME = 'table';
  protected $elements = array();

  public function prepare() {

    $this->setName(self::NAME);
    $this->setNamespace(self::NS);

    $this->buildElements();
  }

  public function setParticle(schema\parser\particle $particle) {

    return parent::setParticle($particle);
  }

  public function buildElements() {

    $elements = $this->getElements();

    foreach ($elements as $el) {

      $ns = $el->getNamespace();

      if (!isset($this->elements[$ns])) {

        $this->elements[$ns] = array();
      }

      $this->elements[$ns][$el->getName()] = $el;
    }
  }

  public function getElements() {

    return $this->particle->getElements();
  }

  public function getElement($name, $ns, $debug = true) {

    $result = null;

    if (!isset($this->elements[$ns]) || !isset($this->elements[$ns][$name])) {

      if ($debug) {

        $this->launchException('Cannot find element : ' . $ns . ':' . $name, get_defined_vars());
      }
    }
    else {

      $result = $this->elements[$ns][$name];
    }

    return $result;
  }

  public function __clone() {

    parent::__clone();
    $this->buildElements();
  }
}

