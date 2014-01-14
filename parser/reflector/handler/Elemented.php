<?php

namespace sylma\parser\reflector\handler;
use \sylma\core, sylma\dom, sylma\parser\reflector;

class Elemented extends Logger implements reflector\elemented {

  const ARGUMENTS = '';
  const PREFIX = 'self';
  const NS = '';

  protected static $sFactoryFile = '/core/factory/Cached.php';
  protected static $sFactoryClass = '\sylma\core\factory\Cached';

  protected $allowComponent = true;
  protected $root;

  public function __construct(reflector\documented $root, reflector\elemented $parent = null, core\argument $arg = null) {

    $this->setRoot($root);
    if ($parent) $this->setParent($parent);

    $this->loadNamespace();
    if ($arg) $this->loadDirectory($arg);
    $this->loadArguments($arg);

    if ($arg) {

      $this->setArguments($arg);
      $this->setSettings($arg);
    }
  }

  protected function setRoot(reflector\documented $root) {

    $this->root = $root;
  }

  public function getRoot() {

    return $this->root;
  }

  protected function loadNamespace() {

    if (!$this->getNamespace()) {

      if ($sNamespace = static::NS) $this->setNamespace($sNamespace, static::PREFIX);
    }
  }

  public function parseComponent(dom\element $el) {

    return parent::parseComponent($el);
  }

  public function loadComponent($sName, dom\element $el, $manager = null) {

    if (!$manager) $manager = $this;

    return parent::loadComponent($sName, $el, $manager);
  }

  public function loadSimpleComponent($sName, $manager = null) {

    if (!$manager) $manager = $this;

    return parent::loadSimpleComponent($sName, $manager);
  }

  public function importComponent(reflector\component $component) {

    $component->setParser($this);

    return $component;
  }

  protected function loadDirectory(core\argument $arg) {

    if ($arg and $sDirectory = $arg->read('directory', null, false)) {

      $dir = $this->getManager(self::FILE_MANAGER)->getDirectory($sDirectory);
      $this->setDirectory($dir);
    }
  }

  protected function loadArguments(core\argument $arg = null) {

    if ($sArguments = static::ARGUMENTS) {

      if ($this->getDirectory('', false)) {

        $manager = $this->getManager(static::ARGUMENT_MANAGER);
        $this->setArguments($manager->createArguments($this->getFile($sArguments)));
      }
    }
    else if ($arg and $sArguments = $arg->read('arguments', null, false)) {

       $this->setArguments($sArguments);
       $this->setSettings($this->getArguments(false)); // TODO : settings will replace arguments
    }
  }

  protected function lookupSourceDirectory($sPath) {

    return $this->getRoot()->getSourceDirectory($sPath);
  }

  protected function lookupSourceFile($sPath) {

    return $this->getRoot()->getSourceFile($sPath);
  }

  public function getWindow() {

    return $this->getRoot()->getWindow();
  }

  public function getNamespace($sPrefix = null) {

    return parent::getNamespace($sPrefix);
  }

  protected function launchException($sMessage, array $aVars = array(), array $mSender = array()) {

    $aVars['reflector/element'] = $this->getNode(false);
    $mSender[] = 'Parser : ' . $this->getNamespace();

    return parent::launchException($sMessage, $aVars, $mSender);
  }

  protected function throwException($sMessage, $mSender = array(), $iOffset = 2) {

    $mSender = (array) $mSender;

    $mSender[] = 'Parser : ' . $this->getNamespace();
    return parent::throwException($sMessage, $mSender, $iOffset);
  }
}
