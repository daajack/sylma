<?php

namespace sylma\action\component;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\parser\languages\common;

class Path extends reflector\component\Foreigner implements common\arrayable {

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->loadDefaultArguments();
  }

  public function asArray() {
    
    if ($this->readx('@path', false))
    {
      $this->launchException('Path must be set into element content when using le:path');
    }
    
    if ($sValue = $this->readx()) {

      $request = $this->create('request', array($sValue, $this->getSourceDirectory()));
    }
    else {

      $request = $this->create('request');
      $request->setFile($this->getRoot()->getFile());
    }

    $sResult = '';

    try {

      $sResult = $request->asString();

    } catch (core\exception $e) {

      $this->launchException($e->getMessage());
    }
    
    return array($sResult);
  }
}

