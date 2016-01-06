<?php

namespace sylma\template\binder\component;
use sylma\core, sylma\dom, sylma\parser\languages\common, sylma\template;

class Script extends Basic implements common\arrayable {

  public function parseRoot(dom\element $el) {

    $this->setNode($el, false);

    $this->allowForeign(true);
    $this->allowUnknown(true);
  }

  public function setElement(template\element $newElement) {

    //$newElement->parseRoot($this->cleanAttributes($this->getNode()));
    $this->build($newElement);
  }

  protected function build(template\element $newElement) {

    $el = $this->getNode();

    $aAttributes = array(
      'js:script' => null,
    );

    if (!$this->readx('@js:class')) {

      $aAttributes['js:class'] = 'sylma.ui.Container';
    }

    $sPath = $this->readx('@js:script');
    $path = $this->create('path', array($sPath, $this->getSourceDirectory()));
    $path->parse();

    $file = $path->asFile(true);
    $this->getRoot()->addDependency($file, true);

    $builder = $this->getManager(self::PARSER_MANAGER)->loadBuilder($file);
    $resourceFile = $builder->findResourceFile($path);
    $this->getRoot()->addResourceCall($resourceFile);

    $el->addElement('js:option', $path->asString(), array(
      'name' => 'path',
    ), $this->getNamespace('js'));

    $el->setAttributes($aAttributes);

    $content = $this->getHandler()->parseAttributes($this->getNode(), $newElement);
    $this->content = $content;
  }

  public function asArray() {

    return $this->content;
  }
}

