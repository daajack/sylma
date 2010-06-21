<?php
/*
 * Fichier des classes Field...
 **/

class HTML_Form extends HTML_Tag {
  
  private $oSchema = null;
  private $oValues = null;
  private $aMessages = array();
  private $sId = null; // id of the current object
  
  private $aNS = array('fs' => SYLMA_NS_FORM_SCHEMA);
  
  public function __construct() {
    
    parent::__construct('form');
    
    Controler::getWindow()->addCSS(Controler::getSettings('stylesheet/@path').'/form.css');
    $this->setAttribute('method', 'post');
    
    $this->addNode('div', null, array('class' => 'form-content clear-block'), NS_XHTML);
    $this->addNode('div', null, array('class' => 'form-action clear-block form-action-bottom'), NS_XHTML);
    
    
    if ($aMessages = Controler::getMessages()->getMessages('form/warning')) {
      
      foreach ($aMessages as $oMessage) if ($sKey = $oMessage->read('arguments/field')) $this->aMessages[$sKey] = $oMessage;
    }
  }
  
  public function getValue($sPath, $bHTML = false) {
    
    $mValue = null;
    
    if ($this->oValues) {
      
      if ($oElement = $this->oValues->getByName($sPath)) {
        
        if ($bHTML) $mValue = (string) $oElement->getChildren();
        else $mValue = $oElement->read();
      }
    }
    
    return $mValue;
  }
  
  public function getId() {
    
    return $this->sId;
  }
  
  public function setValues() {
    
    $this->oValues = new XML_Document('record');
    
    foreach (func_get_args() as $oDocument) {
      
      if (!$oDocument->isEmpty()) {
        
        if ($sId = $oDocument->getAttribute('id')) $this->sId = $sId;
        $this->oValues->add($oDocument->getRoot()->getChildren());
      }
    }
  }
  
  public function setSchemas() {
    
    $this->oSchema = new XML_Document('schema');
    
    foreach (func_get_args() as $oDocument) {
      
      if (!$oDocument->isEmpty()) $this->oSchema->add($oDocument->getRoot()->getChildren());
    }
  }
  
  public function setContent() {
    
    $this->getChildren()->item(0)->add(func_get_args());
  }
  
  public function buildAllFields() {
    
    $oResult = new XML_Document('root');
    
    foreach ($this->oSchema->getChildren() as $oElement) {
      
      
      $oResult->add($this->buildField(new XML_Element('field', null, array('id' => $oElement->getId()))));
    }
    
    return $oResult;
  }
  
  public function buildField($oElement, $bOptionsKey = false) {
    
    if ($this->oSchema) {
      
      $sField = $oElement->getAttribute('id');
      
      $bExist = ($oElement->testAttribute('real', true));
      $oElement->setAttribute('real');
      
      if ($bExist && (!$oField = $this->oSchema->get("fs:field[@id='$sField']", $this->aNS))) {
        
        Controler::addMessage(xt('Le champs "%s" n\'existe pas dans le schéma associé !', new HTML_Strong($sField)), 'action/warning');
        
      } else {
        
        $sExtField = SYLMA_FIELD_PREFIX . $sField;
        
        $bMark = array_key_exists($sField, $this->aMessages);
        
        if ($bExist) {
          
          $oResult = $oElement->merge($oField, true);
          
          $aField = array();
          //if ($oArguments = $oResult->get('fs:arguments', $this->aNS)) {
          if ($oArguments = $oResult->getByName('arguments')) {
            
            $aField['arguments'] = $oArguments->getChildren()->toArray();
            $oArguments->remove();
          }
          
          if ($oOptions = $oResult->getByName('options')) {
            
            if ($oOptions->isTextElement()) $aField['options'] = explode(',', $oOptions->read());
            else if ($oOptions->hasElementChildren()) $aField['options'] = $oOptions->getChildren()->toArray('value');
            
            if ($bOptionsKey) $aField['options'] = array_combine($aField['options'], $aField['options']);
            
            $oOptions->remove();
          }
          
          $aField = array_merge($aField, $oResult->getChildren()->toArray());
          //dspf($aField);
        } else $aField = $oElement->getChildren()->toArray();
        
        $aField['id'] = $sExtField;
        $aField['name'] = $sField;
        
        $mValue = $this->getValue($sField, ($oResult->readByName('type') == 'html'));
        
        if ($mValue !== null) $aField['value'] = $mValue;
        
        $oField = new HTML_Field($aField, $bMark);
        
        return $oField;
      }
    }
    
    return null;
  }
  
  public function addCancel() {
    
    $this->addAction(new HTML_Button(t('Annuler'), 'history.go(-1);'));
  }
  
  public function addAction() {
    
    foreach (func_get_args() as $mArgument) {
      
      if (is_string($mArgument)) {
        
        $oAction = new HTML_Input('submit');
        $oAction->setValue($mArgument);
        
      } else {
        
        $oAction = $mArgument;
      }
      
      $this->getChildren()->item(1)->add($oAction);
    }
  }
  
  public function parse() {
    
    $oMark = new HTML_Div(t('Les champs marqués d\'un astérisque sont obligatoires.'));
    $oMark->addClasses('clear-block', 'form-required');
    
    if ($this->getId()) $this->add(new HTML_Input('hidden', $this->getId(), array('name' => 'id')));
    
    if ($oContent = $this->get('*[contains(@class,"form-content")]')) $oContent->insertAfter($oMark);
    else $oMark->insertBefore($this->getLast());
  }
}

class HTML_JSRedirect extends HTML_Form {
  
  public function __construct($oJs) {
    
    $sJs = $oJs.'window.close();';
    
    Controler::getWindow()->addBlocChild('content-title', t('Redirection en cours...'));
    Controler::getWindow()->addCSS('/web/form.css');
    Controler::getWindow()->setBloc('body_attributes', new HTML_Attribute('onload', $sJs));
    
    parent::__construct();
    $this->displayMark(false);
    
    $this->add(new HTML_Tag('p', t('Si cette fenêtre ne se ferme pas, cliquez sur le bouton.')));
    $this->addBlocChild('action', new HTML_Button('Fermer', 'window.close();'));
  }
}

class HTML_AJAX_Form extends HTML_Form {
  
  public function __construct($sId = '', $oChild = '', $aAttributes = array()) {
    
    parent::__construct('', $oChild, $aAttributes);
    
    $oForm = $this->getBloc('form');
    
    $oForm->addStyle('display', 'none');
    $oForm->addClass('ajax-container');
    $oForm->setAttribute('id' , $sId.'-container');
    $oForm->setAttribute('name' , $sId.'-container');
    $oForm->setAttribute('style', '');
    $oForm->setAttribute('onsubmit', "return window.getAJAX('$sId').submit();");
  }
}

/* *** */

interface HTML_FormElement {
  
  public function setValue($sValue) ;
}

class HTML_FormBlock extends HTML_Tag implements HTML_FormElement {
  
  public function __construct($mValue = null, $aAttributes = array()) {
    
    parent::__construct('span', $mValue, $aAttributes);
  }
  
  public function setValue($mValue) {
    
    $this->set($mValue);
  }
}

class HTML_Input extends HTML_Tag implements HTML_FormElement {
  
  public function __construct($sType = 'text', $oValue = '', $aAttributes = array()) {
    
    parent::__construct('input', '', $aAttributes);
    
    if (!array_key_exists('type', $aAttributes)) $this->addAttribute('type', $sType);
    if (!array_key_exists('value', $aAttributes)) $this->addAttribute('value', $oValue);
  }
  
  public function setValue($sValue) {
    
    $this->addAttribute('value', $sValue);
  }
}

class HTML_Button extends HTML_Input {
  
  public function __construct($sValue, $sOnClick = null, $aAttributes = array()) {
    
    parent::__construct('button', '', $aAttributes);
    
    $this->setValue($sValue);
    if ($sOnClick) $this->addAttribute('onclick', $sOnClick);
  }
}

class HTML_Submit extends HTML_Input {
  
  public function __construct($sValue) {
    
    parent::__construct('submit');
    
    $this->setValue($sValue);
  }
}

class HTML_Select extends HTML_Tag implements HTML_FormElement {

  public function __construct() {
    
    parent::__construct('select');
    $this->forceClosure();
  }

  public function setValue($iValue) {
    
  }

  public function setOptions($aOptions = array(), $iSelected = false) {
    
    foreach ($aOptions as $sKey => $sValue) {
      
      $bSelected = ($iSelected !== false && $iSelected == $sKey);
      $this->addOption($sKey, $sValue, $bSelected);
    }
  }

  public function addOption($sKey = '', $sValue = '', $bSelected = false) {
    
    $oOption = new HTML_Tag('option');
    $oOption->addAttribute('value', $sKey);
    $oOption->add($sValue);
    
    if ($bSelected) $oOption->addAttribute('selected', 'selected');
    
    $this->add($oOption);
  }
}

class HTML_Textarea extends HTML_Tag implements HTML_FormElement {


  public function __construct($sContent = '', $aAttributes = array()) {
    
    parent::__construct('textarea', $sContent, $aAttributes);
    $this->forceClosure();
  }

  public function setValue($sValue) {
    
    $this->add($sValue);
  }
}

class HTML_Checkbox extends HTML_Tag implements HTML_FormElement {

  public function __construct() {
    
    parent::__construct('input');
    
    $this->addAttribute('type', 'checkbox');
  }

  public function setValue($bValue) {
    
    if ($bValue) $this->setAttribute('checked', 'checked');
    else $this->setAttribute('checked');
    
    $this->addAttribute('value', 1);
  }
}

class HTML_Radio extends HTML_Tag implements HTML_FormElement {
  
  public function __construct() {
    
    parent::__construct('input');
    
    $this->addAttribute('type', 'radio');
  }

  public function setValue($bValue) {
    
    if ($bValue) $this->addAttribute('checked', 'checked');
    $this->addAttribute('value', 1);
  }
}

