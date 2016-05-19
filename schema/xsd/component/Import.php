<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\storage\fs, sylma\schema;

class Import extends schema\parser\component\Basic {

  public function parseRoot(dom\element $el) {

    $this->setNode($el);

    if (!$path = $this->readx('@schemaLocation', false)) {

      $namespace = $this->readx('@namespace');
      $this->getHandler()->importSchema($namespace);
    }
    else {

      $file = $this->getSourceFile($path);
      $this->parseFile($file);
    }
  }

  public function parseFile(fs\file $file) {

    $this->getParser()->addSchema($file);
  }
}

