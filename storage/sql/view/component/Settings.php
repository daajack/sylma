<?php

namespace sylma\storage\sql\view\component;
use sylma\core, sylma\dom, sylma\parser\languages\common, sylma\storage\sql;

class Settings extends Basic implements common\argumentable {

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $el->remove();
  }

  protected function build() {

    $file = $this->getSourceFile($this->readx());
    $args = $this->getScriptFile($file);

    $this->getRoot()->addDependency($file);

    $this->log("Load settings : " . $file->asToken());

    return $args;
  }

  public function asArgument() {

    return $this->build();
  }
}

