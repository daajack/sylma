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
  
  public function __construct($sName = '', $oContent = '', $aAttributes = array()) {
    
    parent::__construct($sName, $oContent, $aAttributes, NS_XHTML);
  }
  
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
      
    } // else ERROR Liste invalide
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
    
    // if ($this->bForceClosure && !$this->hasChildren()) $this->add(' ');
    // if ($this->bForceClosure && !$this->read()) $this->add(' ');
    //echo 'hello : '.$this->getName().' / ';
  }
  
  public function __toString() {
    
    $this->parse();
    return parent::__toString(true);
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
  
  public function __construct($sPath, $aArguments = array(), $sName = null) {
    // echo $sName;
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
    else if ($sPath[0] != '/') $sPath = '/'.$sPath;
    
    $sPath .= '.tpl.php';
    
    if (file_exists(MAIN_DIRECTORY.$sPath)) $this->sPath = MAIN_DIRECTORY.$sPath;
    // ERROR else if (Controler::isAdmin()) Controler::addMessage(sprintf(t('Le template "%s" semble ne pas exister !'), new HTML_Strong($sPath)), 'error');
  }
  
  public function parse() {
    
    if (!DEBUG) {
      
      ob_start();
      include($this->getTemplate());
      $text = ob_get_clean();
      
      $oDocument = new XML_Document();
      $oDocument->loadText($text);
      $this->add($oDocument);
      
    } else {
      
      require($this->getTemplate());
    }
    
    return parent::parse();
  }
}

class HTML_Document extends XML_Helper {
  
  public function __construct($sPath = '') {
    
    // $imp = new DomImplementation;
    // $dtd = $imp->createDocumentType('html', '-//W3C//DTD XHTML 1.0 Transitional//EN', 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd');
    
    parent::__construct($sPath);
    //'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
  }
  
  public function addJS($sHref) {
    
    $this->get('/ns:html/ns:head')->add(new HTML_Script($sHref));
  }
  
  public function addCSS($sHref = '') {
    
    $this->get('/ns:html/ns:head')->add(new HTML_Style($sHref));
  }
  
  public function addIECSS($sHref = '', $sVersion = '') {
    
    // $this->getBloc('header')->add(new HTML_IEComment(new HTML_Style($sHref), $sVersion));
  }
  
  public function __toString() {
    
    // $imp = new DomImplementation;
    // $dtd = $imp->createDocumentType('html', '-//W3C//DTD XHTML 1.0 Transitional//EN', 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd');
    // $doc = $imp->createDocument('', '', $dtd);
    // $doc->loadXML(parent::__toString());
    
    // return $doc->saveHTML();
    
    // $oRoot = $doc->importNode($this->getRoot(), true);
    // $this->appendChild($oRoot);
    // echo $oRoot;
    // return $doc->saveXML();
    
    $sDocType = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    $this->formatOutput = true;
    // return $this->saveHTML();
    
    return $sDocType."\n".parent::__toString(true);
  }
}

class HTML_Script extends HTML_Tag {
  
  public function __construct($sSrc = '', $oChild = null) {
    
    $this->forceClosure();
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
    
    $this->forceClosure();
    
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

class HTML_Br extends HTML_Tag {
  
  public function __construct() {
    
    parent::__construct('br');
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
    $this->add(new HTML_Img($sSrc, $sTitle, array('title' => $sTitle)));
  }
}

class HTML_Span extends HTML_Tag {
  
  public function __construct($oChild = '', $aAttributes = array()) {
    
    parent::__construct('span', $oChild, $aAttributes);
  }
}

class HTML_Strong extends HTML_Tag {
  
  public function __construct($oChild = '', $aAttributes = array()) {
    
    $this->forceClosure();
    parent::__construct('strong', $oChild, $aAttributes);
  }
}

class HTML_Em extends HTML_Tag {
  
  public function __construct($oChild = '', $aAttributes = array()) {
    
    parent::__construct('em', $oChild, $aAttributes);
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
    $this->add($row); 
    
    return $row;
  }
}

class HTML_TableRow extends HTML_Tag {


  function __construct() {
    
    parent::__construct('tr');
  }

  function addCell($content, $attributes = NULL) {
    
    $cell = new HTML_Tag('td');
    $this->add($cell);
    
    $cell->add($content);
    
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
    
    $this->add($oItem);
    
    return $oItem;
  }
}

class HTML_Ul extends HTML_Tag {
  
  function __construct($mChildren = '', $aAttributes = array(), $aChildAttributes = array()) {
    
    parent::__construct('ul', $aAttributes);
    $this->forceClosure();
    if (is_array($mChildren)) foreach ($mChildren as $oChild) $this->addItem($oChild, $aChildAttributes);
    else if ($mChildren) $this->addItem($mChildren, $aChildAttributes);
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
    
    $this->add($oKey);
    $this->add($oValue);
    
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

