<?php

class XML extends XML_Tag {
  
  public function __construct($sContent) {
    
    // Chargement du fichier XML
    
    $oDocument = new DOMDocument;
    $this->loadDocument($oDocument->loadXML($sContent));
    
    // parent::__construct('1.0', 'utf-8'); // iso-8859-1
  }
}

class XML_Tag {
  
  protected $sName;
  protected $bForceClosure = false;
  
  protected $aAttributes = array();
  protected $aChildren = array();
  protected $aBlocs = array();
  
  public function __construct($sName = '', $mChildren = '', $aAttributes = array(), $bForceClosure = false) {
    
    $this->setName($sName);
    
    if (is_array($mChildren)) $this->setChildren($mChildren); 
    else $this->addChild($mChildren);
    
    $this->addAttributes($aAttributes);
    $this->forceClosure($bForceClosure);
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
    
    if (!$aAttributes) $this->aAttributes = array();
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
  
  public function loadXMLFile($sPath) {
    
    $oDocument = new DOMDocument();
    $oDocument->preserveWhiteSpace = false;
    $oDocument->load(Controler::getDirectory().$sPath);
    
    $this->loadDocument($oDocument);
  }
  
  public function loadXML($sContent) {
    
    $oDocument = new DOMDocument();
    $oDocument->preserveWhiteSpace = false;
    $oDocument->loadXML($sContent);
    
    $this->loadDocument($oDocument);
  }
  
  public function loadDocument($oDocument) {
    
    foreach ($oDocument->childNodes as $oChild) $this->loadNode($oChild);
  }
  
  public function loadNode($oElement) {
    
    $this->setChildren();
    $this->setAttributes();
    
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
    
    $oXml = new DOMDocument();
    $oXml->loadXML($this->__toString());
    
    // Chargement du fichier XSL
    $oXsl = new DOMDocument();
    $oXsl->load(Controler::getDirectory().$sPath);
    
    // Nouvelle instance & import de la feuille XSL
    $oXslt = new XSLTProcessor();
    $oXslt->importStylesheet($oXsl);
    
    // Transformation et affichage du résultat
    
    $oResult = $oXslt->transformToDoc($oXml);
    $this->loadDocument($oResult);
    
    return $this;
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
class XML_Attribute {
  
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


class XSLT extends XML_Tag {
  
  
}
