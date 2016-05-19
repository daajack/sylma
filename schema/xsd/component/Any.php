<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\schema;

class Any extends schema\parser\component\Basic implements core\arrayable {

  public function asArray() {

    return array(
      'element' => 'any',
      'namespace' => $this->readx('@namespace'),
    );
  }
}

