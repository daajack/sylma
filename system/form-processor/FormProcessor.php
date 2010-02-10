<?php

class FormProcessor extends XML_Processor  {
  
  private $oForm;
  
  public function onElement($oElement) {
    
    switch ($oElement->getName()) {
      
      case 'form' :
        
        $oForm = $this->oForm = new HTML_Form();
        $oForm->cloneAttribute($oElement);
        
        if ($oElement->hasChildren()) $this->runChildren($oForm, $oElement);
        
        return $oForm;
        
      break;
      
      case 'field' :
        
        if (!$oForm = $this->getForm()) dspm(array(t('Aucun formulaire n\'a été instancié !'), $oElement->messageParse()), 'action/error');
        else return $oForm->buildField($oElement);
        
      break;
    }
  }
  
  public function getForm() {
    
    return $this->oForm;
  }
}