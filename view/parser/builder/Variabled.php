<?php

namespace sylma\view\parser\builder;
use sylma\core, sylma\parser\reflector, sylma\parser\languages\common;

abstract class Variabled extends reflector\builder\Documented {

  const ARGUMENTS_NAME = 'arguments';

  protected function prepareFormed(common\_window $window) {

    $this->prepareArgumented($window);
    $this->checkVariable($window, 'post', get_class($this->create('argument')));

    return $window;
  }

  protected function prepareArgumented(common\_window $window) {

    $window->createVariable('aSylmaArguments', 'php-array');
    $this->checkVariable($window, self::ARGUMENTS_NAME, '\\' . get_class($this->create('argument')));
    $this->checkVariable($window, 'contexts', '\\' . get_class($this->create('argument')));

    return $window;
  }

  protected function checkVariable(common\_window $window, $sName, $sToken) {

    if (!$var = $window->getVariable($sName, false)) {

      $var = $window->createVariable($sName, $sToken);
    }

    return $var;
  }

}

