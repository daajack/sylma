<?php

namespace sylma\storage\sql\locale;
use sylma\core, sylma\storage\sql;

class TableAlter extends sql\alter\component\Table {
  
  protected function createChild(sql\alter\alterable $element) {
    
    $result = parent::createChild($element);
    $locale = $this->getManager('locale');

    if ($element instanceof sql\alter\component\Field && $element->isTranslated()) {
      
      $default = $locale->read('default');

      foreach ($locale->getTranslations() as $key => $translation) {
        
        if ($key !== $default) {
          
          $clone = clone $element;
          $clone->setName($clone->getName() . '_' . $key);

          $result .= ', ' . parent::createChild($clone);
        }
      }
    }
    
    return $result;
  }
  
  protected function updateChild(sql\alter\alterable $element) {
    
    $result = parent::updateChild($element);
    $locale = $this->getManager('locale');

    if ($element instanceof sql\alter\component\Field && $element->isTranslated()) {
      
      $default = $locale->read('default');

      foreach ($locale->getTranslations() as $key => $translation) {
        
        if ($key !== $default) {
          
          $clone = clone $element;
          $clone->setName($clone->getName() . '_' . $key);

          $result .= ', ' . parent::updateChild($clone);
        }
      }
    }
    
    return $result;
  }
}

