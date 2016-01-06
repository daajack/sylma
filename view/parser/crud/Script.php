<?php

namespace sylma\view\parser\crud;
use sylma\core, sylma\dom, sylma\action, sylma\template, sylma\storage\fs;

class Script extends action\component\Script implements template\parser\component {

  /**
   * template\parser\template
   */
  protected $template;

  public function parseRoot(dom\element $el) {

    parent::parseRoot($el);
    $this->setUsedNamespace($this->getNamespace(), self::PREFIX, false);
  }

  protected function rebuild(fs\file $file) {


  }

  protected function addDependency(fs\file $file) {

  }

  public function setTemplate(template\parser\template $template) {

    $this->template = $template;
  }

  protected function parseElementSelf(dom\element $el) {

    if (!$this->template) {

      $this->launchException('No template defined');
    }

    return $this->template->parseElementSelf($el);
  }

  protected function loadPath($sPath) {

    $root = $this->getRoot();
    $path = $root->getPath($sPath);

    return parent::loadPath($path->asPath());
  }
}

