<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\schema;

class AnyAttribute extends AttributeGroup implements core\arrayable {

  public function asArray() {

    return array(
      'element' => 'anyAttribute',
      'namespace' => $this->readx('@namespace'),
    );
  }
}

