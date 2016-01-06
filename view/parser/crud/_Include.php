<?php

namespace sylma\view\parser\crud;
use sylma\core, sylma\dom, sylma\parser\languages\common, sylma\template, sylma\view\parser\builder;

class _Include extends template\parser\component\Child implements template\parser\component, common\arrayable {

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
  }

  protected function build(builder\Router $root) {

    $view = $root->getPath($this->readx('@path', false));
    
/*
    if ($path == $this->getRoot()->getView()) {

      $this->launchException('Recursive call detected', get_defined_vars());
    }
*/
    $file = $root->getPathFile($view);

    $resourceFile = $root->getResourceFile($view->getAlias());
    $root->addResourceCall($resourceFile);

    return $root->callScript($file, $this->getWindow(), '\sylma\dom\handler', false);
  }

  public function asArray() {

    return array($this->build($this->getRoot()));
  }
}

