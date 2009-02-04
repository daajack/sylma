<?php
/*
 * Fichier des classes Field...
 **/

   /**
 * Génération d'un tag de saisie en fonction du schéma donné en argument :
 * 
 * Les schémas permettent de générer facilement des formulaires html, et de les contrôler à l'envoi.
 * Les paramètre suivants sont utilisés : id, name, type, input, title, required, arguments.
 * 'id' et 'name', indiqueront les attributs correspondants dans la balise, sans 'id' donné, celui-ci prendra la valeur de 'name'.
 * 'type' indique le type de champs dans la base, 'input' le type de balise à utiliser, sans 'input' donné, celui-ci sera déduit d'après 'type'.
 * 'required' indique que le champs est obligatoire (utilisé dans la vérification du checkRequest).
 * 'title' sera utilisé dans le label correspondant, 'arguments' est une liste libre d'attributs à ajouter dans la balise.
 * 'options' est une liste de valeur pour les select ou les radios au format value => display
 * 'class' et 'style' doivent être des tableaux qui rempliront les attributs correspondants
 * 'display' fixera le comportement du conteneur (float, inline ou default)
 */
class HTML_Field extends XML_Action {
  
  private $aArguments = array();
  
  public function __construct($aNode = array(), $bMark = false) {
    
    parent::__construct();
    
    if (array_val('disable', $aNode) && $aNode['disable']) return ''; // Pas de rendu
    
    $aClasses = array();
    
    $sName = array_val('name', $aNode, '');
    $sId = array_val('id', $aNode, $sName);
    $sValue = array_val('value', $aNode, '');
    $sType = array_val('type', $aNode, 'varchar');
    $sInput = array_val('input', $aNode, $sType);
    $sDisplay = array_val('display', $aNode, 'float');
    $bRequired = array_val('required', $aNode, false);
    $iMinSize = array_val('min-size', $aNode, 0);
    
    // Titre du label
    
    $sTitle = t(array_val('title', $aNode, '-défaut-'));
    
    if ($sType != 'bool') $sTitle .= ' :';
    
    // Input
    
    switch ($sInput) {
      
      case 'key' :      $sInput = 'select'; $oInput = new HTML_Select; break;
      case 'date' :
      case 'varchar' :
      case 'float' :
      case 'email' :
      case 'integer' :  $oInput = new HTML_Input; $aClasses[] = 'field-text';  break;
      case 'blob' :     $oInput = new HTML_Textarea; $aClasses[] = 'field-textarea'; break;
      case 'bool' :     $sInput = 'checkbox';
      case 'checkbox' : $oInput = new HTML_Checkbox; $aClasses[] = 'field-bool'; break;
      case 'radio' :    $oInput = new HTML_Radio; $aClasses[] = 'field-bool'; break;
      case 'password' : $oInput = new HTML_Input('password'); $aClasses[] = 'field-text'; break;
      case 'hidden' :   $oInput = new HTML_Input('hidden'); break;
      default :         $oInput = new HTML_Input; break;
    }
    
    $aClasses[] = 'field-'.$sInput;
    
    if ($iMinSize && !isset($aNode['suffixe'])) {
      
      $oSuffixe = new HTML_Tag('em');
      $oSuffixe->addChild(sprintf(t('min. %s caractères'), $iMinSize));
      
      $aNode['suffixe'] = $oSuffixe;
    }
    
    // Liste d'options
    
    if (array_key_exists('options', $aNode)) $oInput->setOptions($aNode['options'], $sValue);
    else $oInput->setValue($sValue);
    
    if ($sId) $oInput->setAttribute('id', $sId);
    if ($sName) $oInput->setAttribute('name', $sName);
    
    // Attribut Class
    
    foreach ($aClasses as $sClass) $oInput->addClass($sClass);
    
    // Tous les autres attributs
    
    if (array_key_exists('arguments', $aNode))
      foreach($aNode['arguments'] as $sKey => $sVal) $oInput->setAttribute($sKey, $sVal);
    
    if ($sInput == 'hidden') $this->add($oInput); // Pas de label pour les hidden
    else {
      
      // Label
      
      $oLabel = new HTML_Tag('label');
      $oLabel->setAttribute('for', $sId);
      if ($bMark) $oInput->setAttribute('onfocus', "this.style.color = 'black'");
      
      foreach ($aClasses as $sClass) $oLabel->addClass($sClass);
      
      if ($bRequired) {
        
        $mTitle = new HTML_Tag('strong');
        $mTitle->add($sTitle);
        
      } else $mTitle = $sTitle;
      
      $oLabel->addChild($mTitle);
      
      // Container
      
      $oContainer = new HTML_Tag('div');
      $oContainer->addClass('field-container');
      // $oContainer->setAttribute('id', $sId.'_container');
      $oContainer->addClass($sDisplay.'-block');
      
      // Script
      
      if ($sInput == 'date') {
        
        $aOptions = array(
          
          "'firstDay'"        => 1,
          "'changeFirstDay'"  => 'false',
          "'highlightWeek'"   => 'true',
        );
        
        if ($sValue) $iDate = strtotime($sValue);
        else $iDate = time();
        
        $aDateArguments = array(date('Y', $iDate), date('n', $iDate) - 1, date('j', $iDate));
        $aOptions["'defaultDate'"] = 'new Date('.implode(', ', $aDateArguments).')';
        
        if ($aOptions) $sOptions = '{'.implode(', ', fusion(' : ', $aOptions)).'}';
        else $sOptions = '';
        
        $oScript = new HTML_Script();
        $oScript->add("$(document).ready(function(){ $('#$sId').datepicker($sOptions);});");
        $oContainer->add($oScript);
      }
      
      // Marquage
      
      if ($bMark) $oContainer->addClass('field-mark');
      
      foreach ($aClasses as $sClass) $oContainer->addClass($sClass);
      
      if (array_key_exists('class', $aNode))
        foreach($aNode['class'] as $sVal) $oContainer->addClass($sVal);
      
      if (array_key_exists('style', $aNode))
        foreach($aNode['style'] as $sKey => $sVal) $oContainer->addStyle($sKey, $sVal);
        
      if (in_array($sInput, array('checkbox', 'radio'))) {
        
        $oContainer->add($oInput);
        $oContainer->add($oLabel);
        
      } else {
        
        $oContainer->add($oLabel);
        $oContainer->add($oInput);
      }
      
      // Ajout d'un suffixe
      
      if (array_key_exists('suffixe', $aNode)) {
        
        $oSpan = new HTML_Tag('span');
        $oSpan->add($aNode['suffixe']);
        $oSpan->addClass('field-suffixe');
        
        $oContainer->add($oSpan);
      }
      
      $this->add($oContainer);
    }
  }
  
  public function setTitle($sTitle) {
    
    if ($sType != 'bool') $sTitle .= ' :';
  }
  
  public function setClass($sClass) {
    
    $this->addClass('field-'.$sClass);
  }
  
  public function setSuffixe($oValue) {
    
    $this->setBloc('suffixe', $oValue);
  }
  
  public function x__construct($aField) {
    
    $this->setArgument('name', array_val('name', $aField, ''));
    $this->setArgument('id', array_val('id', $aField, $sName));
    $this->setArgument('value', array_val('value', $aField, ''));
    $this->setArgument('display', array_val('display', $aField, 'float'));
    $this->setArgument('required', array_val('required', $aField, false));
    $this->setArgument('min-size', array_val('min-size', $aField, 0));
    $this->setArgument('suffixe', array_val('min-size', $aField));
    $this->setArgument('title', array_val('title', $aField));
    
    $this->setArgument('options', array_val('options', $aField), array());
    $this->setArgument('arguments', array_val('arguments', $aField, array()));
    $this->setArgument('class', array_val('class', $aField, array()));
    $this->setArgument('style', array_val('style', $aField, array()));
    
    $sTitle = t(array_val('', $aField, '-défaut-'));
    
    $this->setValue($sValue);
    
    if ($sId = $this->getArgument('id')) $this->setAttribute('id', $sId);
    if ($sName = $this->getArgument('name')) $this->setAttribute('name', $sName);
    
    // Titre du label
    
    // Input
    
    if ($sSuffixe = $this->getArgument('suffixe'))
      $this->setSuffixe(new HTML_Tag('span', $sSuffixe, array('class' => 'field-suffixe')));
    else if ($this->getArgument('min-size'))
      $this->setSuffixe(new HTML_Tag('em', sprintf(t('min. %s caractères'), $iMinSize)));
    
    // Tous les autres attributs
    
    foreach($this->getArgument('arguments') as $sKey => $sVal) $oInput->setAttribute($sKey, $sVal);
  }
  
  public function setArgument($sKey, $sValue) {
    
    $this->aArgument[$sKey] = $sValue;
  }
  
  public function setArguments($aArguments = array()) {
    
    if (is_array($aArguments)) $this->aArguments = $aArguments;
  }
  
  public function getArgument($sKey) {
    
    return isset($this->aArguments[$sKey]) ? $this->aArguments[$sKey] : null;
  }
  
  public function getArguments() {
    
    return $this->aArguments;
  }
  
  public function x__toString() {
    
    // Label
    
    $oLabel = new HTML_Tag('label');
    $oLabel->setAttribute('for', $sId);
    if ($bMark) $oInput->setAttribute('onfocus', "this.style.color = 'black'");
    
    foreach ($aClasses as $sClass) $oLabel->addClass($sClass);
    
    if ($this->getArgument('required')) {
      
      $mTitle = new HTML_Tag('strong');
      $mTitle->addChild($sTitle);
      
    } else $mTitle = $sTitle;
    
    $oLabel->addChild($mTitle);
    
    // Container
    
    $oContainer = new HTML_Tag('div');
    $oContainer->addClass('field-container');
    $oContainer->addClass($sDisplay.'-block');
    
    if ($bMark) $oContainer->addClass('field-mark');
    
    foreach ($aClasses as $sClass) $oContainer->addClass($sClass);
    
    if ($aClass = $this->getArgument('class')) foreach($aClass as $sVal) $oContainer->addClass($sVal);
    if ($aStyle = $this->getArgument('style')) foreach($aStyle as $sKey => $sVal) $oContainer->addStyle($sKey, $sVal);
    
    if (in_array($sInput, array('checkbox', 'radio'))) {
      
      $oContainer->addChild($oInput);
      $oContainer->addChild($oLabel);
      
    } else {
      
      $oContainer->addChild($oLabel);
      $oContainer->addChild($oInput);
    }
    
    // Ajout d'un suffixe
    $this->addBlock('suffixe');
    
    return $oContainer;
  }
}

/*** Non utilisé, à implémenter ***/

class Field_Key extends HTML_Field {
  
  public function __construct($aField) {
    
    parent::__construct('select');
    
    $sInput = array_val('input', $aNode, 'select');
    
    if ($this->getArgument('options')) $oInput->setOptions($aNode['options'], $sValue);
    
    switch ($sInput) {
      
      case 'select'   : $oInput = new HTML_Select; break;
      case 'radio'    : $oInput = new HTML_Radio; break;
      case 'checkbox' : $oInput = new HTML_Checkbox; break;
    }
    
    //$oInput = new HTML_Radio; $aClasses[] = 'field-bool';
  }
}

class Field_Blob extends HTML_Field {
  
  public function __construct($aField) {
    
    parent::__construct('textarea');
    
    $oInput = new HTML_Textarea;
  }
}

class Field_Float extends HTML_Field {
}
class Field_Integer extends HTML_Field {

  public function __construct($aField) {
    
    parent::__construct('text');
    
    $this->setField(new HTML_Input);
  }
}

class Field_Bool extends HTML_Field {

  public function __construct($aField) {
    
    parent::__construct('checkbox');
    
    $this->setField(new HTML_Input('checkbox'));
  }
}
class Field_Email extends HTML_Field {
}
class Field_Date extends HTML_Field {
}
class Field_Text extends HTML_Field {
  
  public function __construct() {
    
    
  }
}

class Field_Password extends HTML_Field {

  public function __construct($aField) {
    
    parent::__construct('text');
    
    $this->setField(new HTML_Input('password'));
  }
}

class Field_Hidden extends HTML_Field {

  public function __construct($aField) {
    
    parent::__construct('checkbox');
    
    $oInput = new HTML_Input;
  }
  
  public function __toString() {
    
    
  }
}
