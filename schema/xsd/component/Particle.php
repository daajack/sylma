<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\schema;

abstract class Particle extends schema\parser\component\Particle implements core\arrayable  {

  public function parseRoot(dom\element $el) {

    parent::parseRoot($el);
    $this->prepare();
  }

  public function prepare() {

    $this->buildChildren();
  }
  
  public function addChild($element) {
    
    $this->children[] = $element;
  }

  protected function buildChildren() {

    foreach ($this->getNode()->getChildren() as $el) {

      if ($el instanceof dom\comment) {

        if (preg_match('/auto-complete/', $el->getValue())) {

          break;
        }
      }
      else if ($el instanceof dom\element) {

        $child = $this->getParser()->parseComponent($el);
        $this->children[] = $child;
      }
    }
  }
}

