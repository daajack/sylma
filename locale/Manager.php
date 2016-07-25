<?php

namespace sylma\locale;
use sylma\core;

class Manager extends core\module\Domed
{
  protected $translations = array();
  
  public function __construct(core\argument $args) {
    
    $this->setSettings($args);
    $all = $this->get('all');
    
    foreach ($this->get('translate') as $key) {
      
      $this->translations[$key] = $all->get($key);
    }
  }
  
  public function getDefault() {
    
    return $this->read('default');
  }
  
  public function getTranslations() {
    
    return $this->translations;
  }
}
