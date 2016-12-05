<?php

namespace sylma\storage\sql\locale;
use sylma\core, sylma\storage\sql;

class FieldTemplate extends Field {

  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {
    
    switch ($sName) {
      
      case 'is-translated' : $result = $this->isTranslated(); break;
      case 'translations' : $result = $this->reflectApplyTranslations($sMode, $aArguments); break;
      case 'language' : $result = $this->language; break;
      case 'default-language' : $result = $this->default_language ? 1 : 0; break;

      default :

        $result = $this->getHandler()->getCurrentTemplate()->reflectApplyFunction($sName, $aPath, $sMode, $bRead, $sArguments, $aArguments);
    }

    return $result;
  }
  
  protected function reflectApplyTranslations($sMode, array $aArguments = array()) {
    
    $locale = $this->getManager('locale');
    $default = $locale->getDefault();
    
    $this->language = $default;
    $this->default_language = true;
    
    $result[] = $this->reflectApplySelf($sMode, $aArguments);
    
    $name = $this->getName();
    
    foreach ($locale->getTranslations() as $key => $translation) {
      
      if ($key !== $default) {
       
        $field = clone $this;
        $field->language = $key;
        $field->default_language = false;
        $field->setName("{$name}_$key");
        
        $result[] = $field->reflectApplySelf($sMode, $aArguments);
      }
    }
    
    return array_filter($result);
  }
  
  protected function reflectApplySelf($sMode = '', array $aArguments = array()) {
    
    $this->launchException('Should not be called');
  }
}

