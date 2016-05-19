<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\schema;

class BaseType extends schema\parser\component\Simple implements core\arrayable {

  public function asArray() {

   return array(
     'element' => 'baseType',
     'namespace' => $this->getNamespace(),
     'name' => $this->getName(),
     //'content' => $this->getx(),
   );
  }
}
