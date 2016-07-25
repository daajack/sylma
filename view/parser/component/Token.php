<?php

namespace sylma\view\parser\component;
use sylma\core, sylma\template, sylma\parser\languages\common;

class Token extends template\parser\component\Token implements common\arrayable {

  public function asArray() {

    return array();
  }
}

