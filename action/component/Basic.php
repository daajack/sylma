<?php

namespace sylma\action\component;
use sylma\core, sylma\dom, sylma\parser\reflector;

class Basic extends reflector\component\Foreigner {

  const PREFIX = 'action';
  const NS = 'http://2013.sylma.org/action';

  /**
   * @return parser\reflector\handler\Elemented
   */
  protected function getHandler() {

    return $this->getParser();
  }
}
