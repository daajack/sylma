<?php

namespace sylma\storage\xml\test\samples;
use sylma\core;

class Cached02 extends core\module\Domed {

  public function parse($sResult) {

    return $this->createDocument('<div class="result" xmlns="http://2014.sylma.org/html">' . $sResult . '</div>');
  }
}

