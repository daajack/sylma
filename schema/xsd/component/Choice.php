<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\schema\parser, sylma\parser\reflector;

class Choice extends Particle {

  public function asArray() {

    return array(
      'element' => 'choice',
      'content' => $this->children,
    );
  }
}
