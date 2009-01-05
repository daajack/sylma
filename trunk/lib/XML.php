<?php

class XML_Document extends XML_Tag {
  
  public function __toString() {
    
    // $oDocument = new DOMDocument;
    // $this->loadDocument($oDocument->loadXML($sContent));
    // if ($this->isIndented()) $sSeparator = "\n";
    // else $sSeparator = '';
    $sSeparator = "\n";
    $sPrefix = '<?xml version="1.0" encoding="utf-8"?>'.$sSeparator;
    
    return $sPrefix.parent::__toString();
  }
}

class XML_Tag {
  
  protected $sName = '';
  protected $sNamespace = '';
  protected $bForceClosure = false;
  protected $bIndented = false;
  
  protected $aAttributes = array();
  protected $aChildren = array();
  protected $aBlocs = array();
  
  public function __construct($sName = '', $mChildren = '', $aAttributes = array(), $bForceClosure = false, $sNamespace = '') {
    
    $this->setNamespace($sNamespace);
    $this->setName($sName);
    
    if (is_array($mChildren)) $this->setChildren($mChildren); 
    else $this->addChild($mChildren);
    
    $this->addAttributes($aAttributes);
    $this->forceClosure($bForceClosure);
  }
  
  public function setNamespace($sValue = '') {
    
    $this->sNamespace = $sValue;
  }
  
  public function getNamespace() {
    
    return $this->sNamespace;
  }
  
  public function getAttribute($sKey) {
    
    return (isset($this->aAttributes[$sKey])) ? $this->aAttributes[$sKey] : null;
  }
  
  // TODO: alias de setAttribute()
  
  public function addAttribute($sKey, $sValue) {
    
    $this->setAttribute($sKey, $sValue);
  }
  
  public function addAttributes($aAttributes) {
    
    if ($aAttributes) $this->setAttributes($aAttributes);
  }
  
  public function setAttribute($sKey, $sValue = '') {
    
    $this->aAttributes[$sKey] = new XML_Attribute($sKey, $sValue);
  }
  
  // Compense la class XML_Attribute en permettant de récupérer un tableau de type (sKey => sContent)
  
  public function setAttributes($aAttributes = array()) {
    
    if (is_array($aAttributes)) {
      
      if (!$aAttributes) $this->aAttributes = array();
      else foreach ($aAttributes  as $sKey => $sValue) $this->setAttribute($sKey, $sValue);
      
    } else if (Controler::isAdmin()) {
      
      Controler::addMessage(t('Liste d\'attributs invalide'), 'error', array('show_array' => $aAttributes));
    }
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
  
  public function loadXMLFile($sPath) {
    
    $oDocument = new DOMDocument('1.0', 'utf-8');
    $oDocument->preserveWhiteSpace = false;
    $oDocument->load(Controler::getDirectory().$sPath);
    
    $this->loadDocument($oDocument);
  }
  
  public function loadXML($sContent) {
    
    $oDocument = new DOMDocument('1.0', 'utf-8');
    $oDocument->preserveWhiteSpace = false;
    $oDocument->loadXML($sContent);
    
    $this->loadDocument($oDocument);
  }
  
  public function loadDocument($oDocument) {
    
    foreach ($oDocument->childNodes as $oChild) $this->loadNode($oChild);
  }
  
  public function loadNode($oElement) {
    
    $this->setName($oElement->nodeName);
    
    // Attributes
    
    foreach($oElement->attributes as $oAttribute) $this->setAttribute($oAttribute->name, $oAttribute->value);
    
    // Children
    
    foreach($oElement->childNodes as $oChild) {
      
      switch ($oChild->nodeType) {
        
        case 1 : // Node
          
          $oTag = new XML_Tag;
          $oTag->loadNode($oChild);
          $this->addChild($oTag);
          
        break;
        case 3 : // Text
          
          $this->addChild($oChild->nodeValue);
          
        break;
      }
    }
  }
  
  public function parse($sPath = '') {
    
    $oXml = new DOMDocument('1.0', 'utf-8');
    $sContent = $this->__toString();
    
    $this->setChildren();
    $this->setAttributes();
    
    if ($oXml->loadXML($sContent)) {
      
      // Chargement du fichier XSL
      $oXsl = new DOMDocument();
      
      if ($oXsl->load(Controler::getDirectory().$sPath)) {
        
        // Nouvelle instance & import de la feuille XSL
        $oXslt = new XSLTProcessor();
        $oXslt->importStylesheet($oXsl);
        
        // Transformation et affichage du résultat
        
        $oResult = $oXslt->transformToDoc($oXml);
        // dsp(htmlentities($oResult->saveXML()));
        $this->loadDocument($oResult);
        
      } else Controler::addMessage(t('Impossible de charger le fichier template !'), 'error');
      
    } else Controler::addMessage(t('Impossible de charger le fichier source !'), 'error');
    // dsp($oResult); exit;
    return $this;
  }
  
  public function isIndented($bIs = null) {
    
    if ($bIs !== null) $this->bIndented = $bIs;
    return $this->bIndented;
  }
  
  public function __toString() {
    
    // Attributs
    
    // Si le tag est vide ne retourne rien
    if ($this->isEmpty() && get_class($this) == 'HTML_Tag') return '';
    
    // Classes et styles
    
    if ($sClass = $this->getAttribute('class')) $this->addBlocChild('_class', $sClass->getValue());
    if ($sStyle = $this->getAttribute('style')) $this->addBlocChild('_style', $sStyle->getValue());
    
    if ($this->isBloc('_class')) $this->setAttribute('class', $this->getBloc('_class')->implodeChildren(' '));
    if ($this->isBloc('_style')) $this->setAttribute('style', $this->getBloc('_style')->implodeChildren(' '));
    
    if ($this->isIndented()) $sSeparator = "\n";
    else $sSeparator = '';
    
    if ($this->hasChildren()) {
      
      if (count($this->getChildren()) > 1) $sContent = $sSeparator;
      $sContent = implode($sSeparator, $this->getChildren());
      
    } else $sContent = '';
    
    if ($this->isReal()) {
      
      if (count($this->aAttributes)) $sAttributes = ' '.implode(' ', $this->aAttributes);
      else $sAttributes = '';
      
      if ($this->getNamespace()) $sNamespace = $this->getNamespace().':';
      else $sNamespace = '';
      
      $sResult = '<'.$sNamespace.$this->getName().$sAttributes;
      
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
class XML_Attribute {
  
  var $sNamespace = '';
  var $sName = '';
  var $oValue = '';
  
  public function __construct($sName = '', $oValue = '', $sNamespace = '') {
    
    $this->sName = (string) $sName;
    $this->oValue = $oValue;
    $this->setNamespace($sNamespace);
  }
  
  public function setNamespace($sValue = '') {
    
    $this->sNamespace = $sValue;
  }
  
  public function getNamespace() {
    
    return $this->sNamespace;
  }
  
  public function setValue($sValue = '') {
    
    $this->oValue = $sValue;
  }
  
  public function getValue() {
    
    return $this->oValue;
  }
  
  public function __toString() {
    
    if ($this->sName) {
      
      $sValue = $this->oValue; // htmlentities((string) $this->oValue, ENT_COMPAT, 'UTF-8')
      
      if ($this->getNamespace()) $sName = $this->getNamespace().':'.$this->sName;
      else $sName = $this->sName;
      
      return $sName.'="'.$sValue.'"';
    }
  }
}

class XSL_Document extends XML_Tag {
  
  public function __construct() {
    
    $this->addChild(new XML_Tag('output', array('method' => 'xml', 'encoding' => 'utf-8'), true, 'xsl'));
    $this->setNamespace('xsl');
    
    $aAttributes = array(
      'version'     => '1.0',
      'xmlns:xsl'   => 'http://www.w3.org/1999/XSL/Transform',
      'xmlns:fo'    => 'http://www.w3.org/1999/XSL/Format',
      'xmlns:axsl'  => 'http://www.w3.org/1999/XSL/TransformAlias',
    );
    
    parent::__construct('stylesheet', '', $aAttributes);
  }
}
