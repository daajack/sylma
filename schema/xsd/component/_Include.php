<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\storage\fs, sylma\schema;

class _Include extends schema\parser\component\Basic {

  public function parseRoot(dom\element $el) {

    $this->setNode($el);

    $path = $this->readx('@schemaLocation');
    $file = $this->getSourceFile($path);

    return $this->parseFile($file);
  }

  public function parseFile(fs\file $file) {

    return $this->getParser()->addSchema($file);
  }
}

