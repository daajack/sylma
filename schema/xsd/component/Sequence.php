<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\schema\parser, sylma\parser\reflector;

class Sequence extends Particle {

  public function asArray() {

    return array(
      'element' => 'sequence',
      'content' => $this->children,
    );
  }
}
