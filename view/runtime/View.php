<?php

namespace sylma\view\runtime;
use sylma\core;

class View extends core\module\Domed
{
  protected $content = '';
  
  public function __construct($scripts)
  {
    $this->setDirectory(__FILE__);
    $this->scripts = $scripts;
  }
  
  public function add($content)
  {
    $this->content .= $content;
  }
  
  public function call($name)
  {
    $file = $this->scripts[$name];
    
    return include($file);
  }
  
  public function render()
  {
    return $this->content;
  }
}