<?php

namespace sylma\storage\sql\schema\component;
use sylma\core, sylma\schema, sylma\template;

class Particle extends schema\xsd\component\Particle {

  protected $elements = array();

  public function buildChildren() {

    $handler = $this->getHandler();
    $sNamespace = $handler->getTargetNamespace();

    $h = $this->getHandler();
    $this->setNamespace($h::NS, $h::PREFIX);

    $this->bBuilded = true;
    $iPosition = 0;

    foreach ($this->queryx("self:*") as $el) {

      $element = $this->getParser()->parseComponent($el);
      $element->loadNamespace($sNamespace);

      $this->elements[] = $element;

      $iPosition++;
    }
  }

  public function getElements() {

    return $this->elements;
  }

  public function asArray() {

    $this->launchException('Not ready');
  }

  public function __clone() {

    $elements = array();

    foreach ($this->elements as $element) {

      $clone = clone $element;
      $clone->setParticle($this);

      $elements[] = $clone;
    }

    $this->elements = $elements;
  }
}

