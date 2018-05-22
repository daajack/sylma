<?php

namespace sylma\samples;
use sylma\core;

class Dummy extends core\module\Domed
{
  public function __construct($content) {
    
    return implode(', ', $content); // return 'hello, world'
  }
  
  public function getClass($position)
  {
    return 'item-' . $position;
  }
}

