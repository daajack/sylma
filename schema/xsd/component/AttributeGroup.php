<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\schema;

class AttributeGroup extends Group implements core\arrayable {

  public function asArray() {

    return array(
      'element' => 'attributeGroup',
      'namespace' => $this->getNamespace(),
      'name' => $this->name,
      'ref' => $this->ref,
      'content' => $this->children,
      'source' => $this->getNode()->asToken(),
    );
  }
}

