<?php
/**
 * Classes de génération de code HTML
 * Auteur : Rodolphe Gerber
 */

/*
 * Tag
 **/
class HTML_Tag extends XML_Element {
  
  private $aStyles = array();
  private $aClasses = array();
  private $bForceClosure = false;
  
  public function addClass($sValue) {
    
    if ($sValue) $this->aClasses[$sValue] = true;
    return $sValue;
  }
  
  public function addClasses() {
    
    foreach (func_get_args() as $sClassName) $this->addClass($sClassName);
    
    return func_get_args();
  }
  
  public function addStyle($sKey = '', $sValue = '') {
    
    if ($sKey && $sValue) $this->aStyles[$sKey] = new HTML__Style($sKey, $sValue);
    return ($sKey && $sValue);
  }
  
  public function addStyles($aStyles = array()) {
    
    if (is_array($aStyles) && $aStyles) foreach ($aStyles as $sKey => $sValue) $this->addStyle($sKey, $sValue);
    return $aStyles;
  }
  
  /// TEMPORAIRE ///
  public function addAttribute($sName, $sValue) {
    
    $this->setAttribute($sName, $sValue);
  }
  
  public function forceClosure($bValue = true) {
    
    $this->bForceClosure = $bValue;
  }
  
  public function setAttributes($aAttributes = array()) {
    
    if (is_array($aAttributes)) {
      
      if (!$aAttributes) $this->aAttributes = array();
      else foreach ($aAttributes  as $sKey => $sValue) $this->setAttribute($sKey, $sValue);
      
    } else if (Controler::isAdmin()) {
      
      Controler::addMessage(t('Liste d\'attributs invalide'), 'error', array('show_array' => $aAttributes));
    }
  }
  
  public function parse() {
    
    if ($this->aClasses) {
      
      $sClasses = implode(' ', array_keys($this->aClasses));
      if ($oClass = $this->getAttribute('class')) $sClasses = $oClass->value . ' ' . $sClasses;
      $this->setAttribute('class', $sClasses);
    }
    
    if ($this->aStyles) {
      
      $sStyles = implode(';', $this->aStyles);
      if ($oStyle = $this->getAttribute('style')) $sStyles = $oStyle->value . ';' . $sStyles;
      $this->setAttribute('style', $sStyles);
    }
    
    if ($this->bForceClosure && !$this->childNodes->length) $this->appendChild(new DOMText(' '));
  }
  
  public function __toString() {
    
    $this->parse();
    return parent::__toString();
  }
}

class Old_HTML_Tag {
  
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
    
    $this->aAttributes[$sKey] = new HTML_Attribute($sKey, $sValue);
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
      // if (is_object($oValue) && is_subclass_of($oValue, 'DOMNode')) $this->;
      // $this->getXml()->appendChild($oValue)
      
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
class HTML_Attribute extends DOMAttr {
  
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

/*
 * Attribut style
 **/
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

/*
 * HTML
 **/

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

class HTML_Document extends XML_Action {
  
  public function __construct($sPath = '', $oRedirect = null, $sSource = '') {
    
    parent::__construct($sPath, $oRedirect, $sSource);
    
    // $this->addChild('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
    // $this->setBloc('header', new HTML_Tag());
  }
  
  public function addJS($sHref) {
    
    $this->get('/html/head')->add(new HTML_Script($sHref));
  }
  
  public function addCSS($sHref = '') {
    
    $this->get('/html/head')->add(new HTML_Style($sHref));
  }
  
  public function addIECSS($sHref = '', $sVersion = '') {
    
    // $this->getBloc('header')->addChild(new HTML_IEComment(new HTML_Style($sHref), $sVersion));
  }
}

class HTML_Script extends HTML_Tag {
  
  public function __construct($sSrc = '', $oChild = null) {
    
    parent::__construct('script');
    
    $this->setAttribute('type', 'text/javascript');
    
    if ($sSrc) {
      
      $this->setAttribute('src', $sSrc);
      $this->forceClosure();
      
    } else $this->add($oChild);
  }
}

class HTML_Style extends HTML_Tag {
  
  public function __construct($sHref = '', $oChild = null) {
    
    if ($sHref) {
      
      parent::__construct('link');
      
      $this->setAttribute('rel', 'stylesheet');
      $this->setAttribute('href', $sHref);
      $this->setAttribute('media', 'screen, projection');
      
    } else {
      
      parent::__construct('style', $oChild);
    }
    
    $this->setAttribute('type', 'text/css');
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
    
    parent::__construct('div', $mChildren, $aAttributes);
    $this->forceClosure();
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

/* TODO : Sera supprimer dans le prochain commit (si j'y pense) */

class HTML_List extends HTML_Tag {
  
  function __construct($oContent = '', $aAttributes = array()) {
    
    parent::__construct('ul', $oContent, $aAttributes);
  }
  
  function addItem($sContent, $aAttributes = array()) {
    
    $oItem = new HTML_Tag('li', $sContent, $aAttributes);
    
    $this->addChild($oItem);
    
    return $oItem;
  }
}

class HTML_Ul extends HTML_Tag {
  
  function __construct($mChildren = '', $aAttributes = array(), $aChildAttributes = array()) {
    
    parent::__construct('ul', $aAttributes);
    
    if (is_array($mChildren)) foreach ($mChildren as $oChild) $this->addItem($oChild, $aChildAttributes);
    else $this->addItem($mChildren, $aChildAttributes);
  }
  
  function addItem($sContent, $aAttributes = array()) {
    
    $oItem = $this->add(new HTML_Tag('li', $sContent, $aAttributes));
    return $oItem;
  }
}

class HTML_Dl extends HTML_Tag {
  
  function __construct($mChildren = '', $aAttributes = array(), $aChildKeyAttributes = array(), $aChildValueAttributes = array()) {
    
    parent::__construct('dl', $aAttributes);
    
    if (is_array($mChildren)) foreach ($mChildren as $sKey => $sValue) $this->addItem($sKey, $sValue, $aChildKeyAttributes, $aChildValueAttributes);
    else $this->addItem($mChildren, '', $aChildKeyAttributes);
  }
  
  function addItem($sKey, $sValue, $aKeyAttributes = array(), $aValueAttributes = array()) {
    
    $oKey = new HTML_Tag('dt', $sKey, $aKeyAttributes);
    $oValue = new HTML_Tag('dd', $sValue, $aValueAttributes);
    
    $this->addChild($oKey);
    $this->addChild($oValue);
    
    return array($oKey, $oValue);
  }
}


class HTML_Embed extends HTML_Tag {
  
  function __construct($sUrl = '', $iWidth = 0, $iHeight = 0, $sBgColor = '#ffffff', $aAttributes = array()) {
    
    parent::__construct('embed', $aAttributes);
    
    $this->setAttributes(array(
      
      'src'         => $sUrl,
      'quality'     => 'high',
      'bgcolor'     => $sBgColor,
      'width'       => $iWidth,
      'height'      => $iHeight,
      'type'        => 'application/x-shockwave-flash',
      'pluginspage' => 'http://www.macromedia.com/go/getflashplayer',
    ));
  }
}

