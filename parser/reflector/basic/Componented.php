<?php

namespace sylma\parser\reflector\basic;
use \sylma\core, sylma\dom, sylma\storage\fs, sylma\parser\reflector;

abstract class Componented extends Namespaced {

  protected $allowComponent = false;

  protected function createClass($sAlias = 'cached') {

    $sClass = $this->lookupClassName($sAlias);
    $window = $this->getWindow();

    $result = $window->createClass($sClass);

    return $result;
  }

  protected function lookupClassName($sName) {

    if ($sName{0} == '\\') {

      $sResult = $sName;
    }
    else {

      $sResult = $this->getFactory()->findClass($sName)->read('name');
    }

    return $sResult;
  }

  protected function createDummy($sAlias = 'dummy', array $aArguments = array(), $window = null, $bVar = false, $bStatic = false) {

    $sClass = $this->lookupClassName($sAlias);
    if (!$window) $window = $this->getWindow();

    if ($bStatic) {

      $result = $window->createClass($sClass);
    }
    else {

      $result = $window->createInstanciate($window->tokenToInstance($sClass), $aArguments);
    }


    return $bVar ? $window->createVar($result) : $result;
  }

  /**
   * @deprecated use self::createDummy() instead
   */
  protected function createObject($sAlias = 'cached', array $aArguments = array(), $window = null, $bVar = true) {

    return $this->createDummy($sAlias, $aArguments, $window, $bVar);
  }

  protected function allowComponent($mValue = null) {

    if (!is_null($mValue)) $this->allowComponent = $mValue;
    return $this->allowComponent;
  }

  protected function parseComponent(dom\element $el) {

    if (!$this->allowComponent()) {

      $this->throwException(sprintf('Component building not allowed with %s', $el->asToken()));
    }

    return $this->loadComponent('component/' . $el->getName(), $el, $this);
  }

  protected function loadComponent($sName, dom\element $el, $manager) {

    $result = $this->createComponent($sName, $manager);
    $result->parseRoot($el);

    return $result;
  }

  protected function loadSimpleComponent($sName, $manager) {

    $result = $this->createComponent($sName, $manager);

    return $result;
  }

  protected function createComponent($sAlias, $manager) {

    if (!$class = $this->getFactory()->findClass($sAlias, '', false)) {

      //dsp($this->getArguments());
      $this->launchException("Class not found for component '$sAlias'", get_defined_vars());
    }

    $result = $this->create($sAlias, array($manager, $class, $this->getUsedNamespaces()));

    return $result;
  }
}

/*
  protected function createComponent(dom\element $el) {

    $aClass = explode('\\', get_class($this));

    \Sylma::load('/core/functions/Text.php');
    $sName = text\toggleCamel($el->getName());

    if ($dir = $this->getComponentsDirectory($el->getNamespace())) {

      $sClass = str_replace('/', '\\', (string) $dir) . $sName;
    }
    else {

      $sClass = '\\' . implode('\\', array_slice($aClass, 0, -1)) . '\\' . $sName;
    }

    return new $sClass($this, $el);
  }

  protected function getComponentsDirectory($sNamespace) {

    return $this->componentsDirs[$sNamespace];
  }

  protected function setComponentsDirectory(fs\directory $dir, $sNamespace = '') {

    if (!$sNamespace) $sNamespace = $this->getNamespace();

    $this->componentsDirs[$sNamespace] = $dir;
  }
*/