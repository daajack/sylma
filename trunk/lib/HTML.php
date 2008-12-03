<?php
/**
 * Classes de génération de code HTML
 * Auteur : Rodolphe Gerber
 */

/**
 * Tag
 */
class HTML_Tag {
  
  protected $sName;
  protected $bForceClosure = false;
  
  protected $aAttributes = array();
  protected $aChildren = array();
  protected $aBlocs = array();
  
  public function __construct($sName = '', $mChildren = '', $aAttributes = array(), $bForceClosure = false) {
    
    $this->setName($sName);
    
    if (is_array($mChildren)) $this->setChildren($mChildren); 
    else $this->addChild($mChildren);
    
    $this->setAttributes($aAttributes);
    $this->forceClosure($bForceClosure);
  }
  
  public function getAttribute($sKey) {
    
    return (isset($this->aAttributes[$sKey])) ? $this->aAttributes[$sKey] : null;
  }
  
  // TODO: alias de setAttribute()
  
  public function addAttribute($sKey, $sValue) {
    
    $this->setAttribute($sKey, $sValue);
  }
  
  public function setAttribute($sKey, $sValue = '') {
    
    $this->aAttributes[$sKey] = new HTML_Attribute($sKey, $sValue);
  }
  
  // Compense la class HTML_Attribute en permettant de récupérer un tableau de type (sKey => sContent)
  
  public function setAttributes($aAttributes) {
    
    foreach ($aAttributes  as $sKey => $sValue) $this->setAttribute($sKey, $sValue);
  }
  
  public function getAttributes() {
    
    return $this->aAttributes;
  }
  
  public function hasAttribute($sKey) {
    
    return isset($this->aAttribute[$sKey]);
  }
  
  public function hasAttributes() {
    
    return count($this->getAttributes());
  }
  
  public function addChild($oValue) {
    
    // TODO : 
    if ($this->isUsable($oValue)) {
      
      $this->aChildren[] = $oValue;
      
    } else if (Controler::isAdmin()) {
      
      // if (!is_string($sKey)) Controler::addMessage(t('Valeur de Tag non valide ! : '.gettype($oValue)));
      // if (!is_null(Controler::getMessages())) Controler::addMessage($sMessage);
    }
  }
  
  public function addChildren($aChildren = array()) {
    
    if (is_array($aChildren)) foreach ($aChildren as $oChild) $this->addChild($oChild);
  }

  public function addBloc($sKey = '') {
    
    if ($this->isBloc($sKey)) $this->addChild($this->getBloc($sKey));
  }
  
  public function addBlocs() {
    
    foreach ($this->getBlocs() as $sBloc => $oBloc) $this->addBloc($sBloc);
  }
  
  public function addBlocChild($sKey = '', $oValue = '') {
    
    $this->getBloc($sKey)->addChild($oValue);
  }
  
  public function setBloc($sKey = '', $oValue = '') {
    
    if (!is_string($sKey)) Controler::addMessage(t('Clé de bloc invalide !'));
    
    if ($this->isBloc($sKey)) $this->getBloc($sKey)->addChild($oValue);
    else $this->aBlocs[$sKey] = $oValue;
    
    return $this->getBloc($sKey);
  }
  
  public function getBloc($sKey = '') {
    
    if (!$this->isBloc($sKey)) $this->aBlocs[$sKey] = new HTML_Tag();
    
    return $this->aBlocs[$sKey];
  }
  
  public function getBlocs() {
    
    return $this->aBlocs;
  }
  
  public function isBloc($sKey = '') {
    
    return isset($this->aBlocs[$sKey]);
  }
  //xString
  public function hasBlocs() {
    
    return count($this->getBlocs());
  }
  
  public function setChildren($aChildren = array()) {
    
    if (is_array($aChildren)) $this->aChildren = $aChildren;
  }
  
  public function getChildren() {
    
    return $this->aChildren;
  }
  
  public function clearChildren($oChildren = '') {
    
    if ($oChildren) $this->aChildren = array($oChildren);
    else $this->aChildren = array();
  }
  
  public function hasChildren() {
    
    return count($this->getChildren());
  }
  
  public function forceClosure($bValue = true) {
    
    $this->bForceClosure = $bValue;
  }
  
  public function isReal() {
    
    return $this->getName();
  }
  
  public function getName() {
    
    return $this->sName;
  }
  
  public function setName($sName = '') {
    
    $this->sName = (string) $sName;
  }
  
  public function isUsable($mValue) {
    
    if (
         (is_string($mValue) && $mValue)
      || is_numeric($mValue)
      || (is_object($mValue) && method_exists($mValue, '__toString'))) return true;//&& !is_boolean($oValeur)
    else return false;
  }
  
  public function isEmpty() {
    
    return (!$this->hasAttributes() && !$this->hasChildren() && !$this->hasBlocs());
  }
  
  public function addClass($sValue) {
    
    $this->getBloc('_class')->addChild($sValue);
  }
  
  public function addClasses($aClasses) {
    
    if (is_array($aClasses)) foreach ($aClasses as $sClass) $this->addClass($sClass);
    else $this->addClass($aClasses);
  }

  public function addStyle($sKey = '', $sValue = '') {
    
    $this->getBloc('_style')->addChild(new HTML__Style($sKey, $sValue));
  }

  public function addStyles($aStyles = array()) {
    
    if (is_array($aStyles)) foreach ($aStyles as $sKey => $sValue) $this->addStyle($sKey, $sValue);
    else return false;
  }
  
  public function implodeChildren($sSep = "\n") {
    
    return implode($sSep, $this->getChildren());
  }
  
  public function __toString() {
    
    // Attributs
    
    // Si le tag est vide ne retourne rien
    if ($this->isEmpty()) return '';
    
    // Classes et styles
    
    if ($sClass = $this->getAttribute('class')) $this->addBlocChild('_class', $sClass->getValue());
    if ($sStyle = $this->getAttribute('style')) $this->addBlocChild('_style', $sStyle->getValue());
    
    if ($this->isBloc('_class')) $this->setAttribute('class', $this->getBloc('_class')->implodeChildren(' '));
    if ($this->isBloc('_style')) $this->setAttribute('style', $this->getBloc('_style')->implodeChildren(' '));
    
    if ($this->hasChildren()) {
      
      if (count($this->getChildren()) > 1) $sContent = "\n";
      $sContent = implode("\n", $this->getChildren());
      
    } else $sContent = '';
    
    if ($this->isReal()) {
      
      if (count($this->aAttributes)) $sAttributes = ' '.implode(' ', $this->aAttributes);
      else $sAttributes = '';
      
      $sResult = '<'.$this->getName().$sAttributes;
      
      // Content
      
      if ($this->isUsable($sContent) || $this->bForceClosure) $sResult .= '>'.$sContent.'</'.$this->getName().'>';
      else $sResult .= ' />';
      
    } else $sResult = $sContent;
    
    return $sResult;
  }
}

/**
 * Attribut
 */
class HTML_Attribute {
  
  var $sName;
  var $oValue;
  
  public function __construct($sName, $oValue = '') {
    
    $this->sName = (string) $sName;
    $this->oValue = $oValue;
  }
  
  public function setValue($sValue) {
    
    $this->oValue = $sValue;
  }
  
  public function getValue() {
    
    return $this->oValue;
  }
  
  public function __toString() {
    
    if ($this->sName) {
      
      $sValue = htmlentities((string) $this->oValue, ENT_COMPAT, 'UTF-8');
      return $this->sName.'="'.$sValue.'"';
    }
  }
}

/**
 * Attribut style
 */
class HTML__Style extends HTML_Tag {
  
  var $sName;
  var $content;
  
  public function __construct($sName, $content) {
    
    $this->name = $sName;
    $this->content = $content;
  }

  public function __toString() {
    
    return $this->name.' : '.$this->content.';';
  }
}

/**
 * HTML
 */

class HTML_Template extends HTML_Tag {
  
  private $sPath = '';
  private $aArguments = array();
  
  public function __construct($sPath, $aArguments = array(), $sName = '') {
    
    parent::__construct($sName);
    
    $this->setTemplate($sPath);
    $this->setArguments($aArguments);
  }
  
  public function setArgument($sKey, $mValue) {
    
    $this->aArguments[$sKey] = $mValue;
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
  
  /*
   * Retourne le chemin du template
   **/
  
  public function getTemplate() {
    
    return $this->sPath;
  }
  
  public function setTemplate($sPath = '') {
    
    if (!$sPath) return false;
    else if ($sPath[0] == '/') $sPath = substr($sPath, 1);
    
    $sPath .= '.tpl.php';
    
    if (file_exists(Controler::getDirectory().$sPath)) $this->sPath = $sPath;
    else if (Controler::isAdmin()) Controler::addMessage(sprintf(t('Le template "%s" semble ne pas exister !'), new HTML_Strong($sPath)), 'error');
  }
  
  public function __toString() {
    
    if (!DEBUG) {
      
      ob_start();
      include($this->getTemplate());
      $this->addChild(ob_get_clean());
      
    } else {
      
      require($this->getTemplate());
    }
    
    return parent::__toString();
  }
}

class HTML extends HTML_Template {
  
  public function __construct($sTemplatePath) {
    
    parent::__construct($sTemplatePath);
    
    $this->addChild('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
    $this->setBloc('header', new HTML_Tag());
  }
  
  public function addJS($sHref) {
    
    $this->getBloc('header')->addChild(new HTML_Script($sHref));
  }
  
  public function addCSS($sHref = '') {
    
    $this->getBloc('header')->addChild(new HTML_Style($sHref));
  }
  
  public function addIECSS($sHref = '', $sVersion = '') {
    
    $this->getBloc('header')->addChild(new HTML_IEComment(new HTML_Style($sHref), $sVersion));
  }
}

class HTML_Script extends HTML_Tag {
  
  public function __construct($sSrc = '', $oChild = null) {
    
    parent::__construct('script');
    $this->setAttribute('type', 'text/javascript');
    $this->forceClosure();
    
    if ($sSrc) $this->setAttribute('src', $sSrc);
    else $this->addChild($oChild);
  }
}

class HTML_Style extends HTML_Tag {
  
  public function __construct($sHref = '', $oChild = null) {
    
    $this->setAttribute('type', 'text/css');
    
    if ($sHref) {
      
      parent::__construct('link');
      $this->setAttribute('rel', 'stylesheet');
      $this->setAttribute('href', $sHref);
      $this->setAttribute('media', 'screen, projection');
      
    } else {
      
      parent::__construct('style');
      $this->addChild($oChild);
    }
  }
}

class HTML_Comment {
  
  private $oValue = '';
  
  public function __construct($oValue = '') {
    
    $this->oValue = $oValue;
  }
  
  public function __toString() {
    
    return '<!--'.$this->oValue.'-->';
  }
}

class HTML_IEComment extends HTML_Comment {
  
  public function __construct($oValue, $sVersion = '') {
    
    parent::__construct("[if IE$sVersion]>$oValue<![endif]");
  }
}

class HTML_A extends HTML_Tag {
  
  public function __construct($sHref = '', $oChild = '', $aAttributes = array()) {
    
    parent::__construct('a', $oChild, $aAttributes);
    $this->setAttribute('href', $sHref);
  }
}

/*
 * Crée une image cliquable en attribuant 'title'
 **/
class HTML_Icone extends HTML_A {
  
  public function __construct($sHref = '', $sSrc = '', $sTitle = '', $aAttributes = array()) {
    
    parent::__construct($sHref, '', $aAttributes);
    $this->addChild(new HTML_Img($sSrc, $sTitle, array('title' => $sTitle)));
  }
}

class HTML_Strong extends HTML_Tag {
  
  public function __construct($oChild = '', $aAttributes = array()) {
    
    parent::__construct('strong', $oChild, $aAttributes);
  }
}

class HTML_Img extends HTML_Tag {
  
  public function __construct($sSrc, $sAlt = '', $aAttributes = array()) {
    
    parent::__construct('img', null, $aAttributes);
    $this->setAttribute('src', $sSrc);
    $this->setAttribute('alt', $sAlt);
  }
}

class HTML_Div extends HTML_Tag {
  
  public function __construct($mChildren = '', $aAttributes = array()) {
    
    parent::__construct('div', $mChildren, $aAttributes, true);
  }
}

/*
 * Divers
 **/

class HTML_Table extends HTML_Tag {


  function __construct() {
    
    parent::__construct('table');
  }

  function addRow() {
    
    $row = new HTML_TableRow();
    $this->addChild($row); 
    
    return $row;
  }
}

class HTML_TableRow extends HTML_Tag {


  function __construct() {
    
    parent::__construct('tr');
  }

  function addCell($content, $attributes = NULL) {
    
    $cell = new HTML_Tag('td');
    $this->addChild($cell);
    
    $cell->addChild($content);
    
    if (is_array($attributes)) foreach ($attributes as $key => $val) $cell->setAttribute($key, $val);
    
    return $cell;
  }

  function addMultiCell($cells) {
    
    if (is_array($cells)) {
    
      foreach($cells as $key => $val) {
        
        if (is_array($val)) $this->addCell($key, $val);
        else $this->addCell($val);
      }
    }
  }
}

class HTML_List extends HTML_Tag {
  
  function __construct($oContent = '', $aArguments = array()) {
    
    parent::__construct('ul', $oContent, $aArguments);
  }
  
  function addItem($sContent, $aAttributes = array()) {
    
    $oItem = new HTML_Tag('li', $sContent, $aAttributes);
    
    $this->addChild($oItem);
    
    return $oItem;
  }
}
