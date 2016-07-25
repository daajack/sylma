<?php

namespace sylma\storage\sql\locale;
use sylma\core, sylma\storage\sql;

class Field extends sql\schema\component\Field {

  protected $language = '';
  
  protected function getLanguage() {
    
    return $this->language;
  }
  
  public function isTranslated() {

    return $this->readx('@locale');
  }
}

