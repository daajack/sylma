<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\schema\parser, sylma\parser\reflector;

class All extends Particle {

  public function asArray() {

    return array(
      'element' => 'all',
      'content' => $this->children,
    );
  }
}
