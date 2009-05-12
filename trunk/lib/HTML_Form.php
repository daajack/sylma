<?php
/*
 * Fichier des classes Field...
 **/

class HTML_Form extends Temp_Action {
  
  private $bDisplayTop = false;
  private $bDisplayMark = true;
  
  public function __construct($sAction = '', $oValue = '', $aAttributes = array()) {
    
    parent::__construct();
    
    $oForm = new HTML_Tag('form', $oValue, $aAttributes, $this);
    $this->setBloc('form', $oForm);
    
    $this->setBloc('form-content', new HTML_Div('', array('class' => 'form-content clear-block')));
    
    $oForm->setAttribute('action', $sAction);
    $oForm->setAttribute('method', 'post');
    
    // Notice concernant les champs obligatoires
    
    $oMark = new HTML_Div(t('Les champs marqués en gras sont obligatoires.'));
    $oMark->addClasses('clear-block', 'form-required');
    
    $this->setBloc('mark', $oMark);
    
    // Boutons d'action
    
    $oAction = new HTML_Div();
    $oAction->addClasses('form-action', 'clear-block');
    
    $this->setBloc('action', $oAction);
  }
  
  /*
   * Construit les éléments du formulaires
   **/
  public function build($aSchema, $aValues, $oMessages = null, $bAutoAdd = true) {
    
    $aForm = array();
    $aMessages = array();
    
    // Récupération des références dans les messages
    
    if ($oMessages) {
      
      foreach ($oMessages->getMessages() as $oMessage) {
        
        if ($aFields = $oMessage->query('arguments/field')) {
          foreach ($aFields as $oField) $aMessages[$oField->read()] = $oMessage;
        }
      }
    }
    
    // Tri des champs selon 'weight'
    
    $aWeight = array();
    $iIndex = 1;
    
    foreach ($aSchema as $sField => $aField) {
      
      $aWeights[$sField] = isset($aField['weight']) ? $aField['weight'] : $iIndex;
      $iIndex++;
    }
    
    asort($aWeights);
    
    // Si il y'a eu des erreurs, récupération des données de $_POST
    
    foreach ($aWeights as $sField => $iWeight) {
      
      $aField = $aSchema[$sField];
      
      if (array_val('disable', $aField) && $aField['disable']) continue; // Pas de rendu
      
      if (array_val('type', $aField) != 'display') {
        
        $bMark = isset($aMessages[$sField]);
        $sValue = array_val($sField, $aValues, array_val('value', $aField, ''));
        
        $aField['value'] = $sValue;
        $aField['name'] = array_val('name', $aField, $sField);
        
        // Création de l'élément
        
        // case 'key' :      $oInput = new Field_Key($aField); break;
        // case 'date' :     $oInput = new Field_Date($aField); break;
        // case 'float' :    $oInput = new Field_Float($aField); break;
        // case 'email' :    $oInput = new Field_Email($aField); break;
        // case 'integer' :  $oInput = new Field_Integer($aField);  break;
        // case 'blob' :     $oInput = new Field_Blob($aField); break;
        // case 'bool' :     $oInput = new Field_Bool($aField); break;
        // case 'password' : $oInput = new Field_Password($aField) break;
        // case 'hidden' :   $oInput = new Field_Hidden($aField); break;
        // default :         $oInput = new Field_Text($aField); break;
        
        $oField = new HTML_Field($aField, $bMark);
        
      } else {
        
        // Contenu simple
        
        $oField = $aField['content'];
      }
      
      if ($bAutoAdd) $this->getBloc('fields')->add($oField);
      $aForm[$sField] = $oField;
    }
    
    return $aForm;
  }
  
  public function addAction($sValue, $sType = 'submit', $aAttributes = array()) {
    
    $oAction = new HTML_Input($sType);
    $oAction->setValue($sValue);
    
    $oAction->setAttributes($aAttributes);
    
    $this->getBloc('action')->add($oAction);
  }
  
  public function displayTop($bValue = true) {
    
    $this->bDisplayTop = $bValue;
  }
  
  public function displayMark($bValue = true) {
    
    $this->bDisplayMark = $bValue;
  }
  
  public function parse() {
    
    $this->set();
    $this->addBloc('form');
    
    if ($this->bDisplayTop) $this->addBloc('action');
    $this->getBloc('form-content')->add($this->getBloc('fields')->query('*'));
    $this->addBloc('form-content');
    
    if ($this->bDisplayMark) $this->addBloc('mark');
    $this->getBloc('action')->addClass('form-action-bottom');
    $this->addBloc('action');
    
    return parent::parse();
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

class HTML_Input extends HTML_Tag implements HTML_FormElement {
  
  public function __construct($sType = 'text', $oValue = '', $aAttributes = array()) {
    
    parent::__construct('input', '', $aAttributes);
    
    $this->addAttribute('type', $sType);
    $this->addAttribute('value', $oValue);
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
    
    if ($bValue) $this->addAttribute('checked', 'checked');
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

