<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\storage\fs, sylma\schema;

class Annotation extends schema\parser\component\Basic implements core\arrayable {

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
  }

  public function asArray() {

    return array(
      'element' => 'annotation',
      'content' => $this->readx(),
    );
  }
}

