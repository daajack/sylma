<?php

class FormProcessor extends XML_Processor  {
  
  private $oForm;
  private $bOptionsKey = false;
  
  public function onElement($oElement, XML_Action $oAction) {
    
    switch ($oElement->getName()) {
      
      case 'form' :
        
        if ($sOptionsKey = $oElement->getAttribute('options-key')) {
          
          $this->bOptionsKey = strtobool($sOptionsKey);
          $oElement->setAttribute('options-key');
        }
        
        $oForm = $this->oForm = new HTML_Form();
        $oForm->cloneAllAttributes($oElement);
        
        if ($oElement->hasChildren()) $this->runChildren($oForm, $oElement);
        
        return $oForm;
        
      break;
      
      case 'list-fields' :
        
        $oResult = null;
        
        if (!$oForm = $this->getForm()) {
          
          dspm(array(t('Aucun formulaire n\'a été instancié !'), $oElement->messageParse()), 'action/error');
          
        } else {
          
          if (!$sFields = $oElement->getValue()) dspm(xt('Aucune valeur dans l\'élément %s du formulaire', $oElement));
          else {
            
            $oResult = new XML_Document('root');
            $aField = explode(',', $sFields);
            
            foreach ($aField as &$sField) {
              
              $oResult->add($oForm->buildField(new XML_Element('field', null, array('id' => trim($sField))), $this->bOptionsKey));
            }
          }
        }
        
        return $oResult;
        
      break;
      
      case 'all-fields' :
        
        if (!$oForm = $this->getForm()) dspm(array(t('Aucun formulaire n\'a été instancié !'), $oElement->messageParse()), 'action/error');
        else return $oForm->buildAllFields($this->bOptionsKey);
        
      break;
      
      case 'field' :
        
        if (!$oForm = $this->getForm()) dspm(array(t('Aucun formulaire n\'a été instancié !'), $oElement->messageParse()), 'action/error');
        else return $oForm->buildField($oElement, $this->bOptionsKey);
        
      break;
    }
  }
  
  public function getForm() {
    
    return $this->oForm;
  }
}
