<?php

class XML_Fragment extends DOMDocumentFragment { }

class XML_Document extends DOMDocument {
  
  public $isDocument = true;
  
  public function __construct($mChildren = '', $sSource = '') {
    
    parent::__construct();
    
    $this->preserveWhiteSpace = false;
    
    $this->registerNodeClass('DOMElement', 'XML_Element');
    $this->registerNodeClass('DOMText', 'XML_Text');
    $this->registerNodeClass('DOMAttr', 'XML_Attribute');
    $this->registerNodeClass('DOMCharacterData', 'XML_CData');
    $this->registerNodeClass('DOMDocumentFragment', 'XML_Fragment');
    
    // Chargement du document si path envoyé
    if ($mChildren) {
      
      if (is_object($mChildren)) $this->set($mChildren);
      else if (is_string($mChildren)) $this->loadDocument($mChildren, $sSource);
    }
  }
  
  public function loadDocument($sPath = '', $sSource = '') {
    
    $sContent = '';
    
    switch ($sSource) {
      
      case 'file' : 
        
        $this->loadFile($this->getPath());
        
      break;
      
      case 'db' : 
      default :
        
        $this->loadDatabase($sPath);
        
      break;
    }
    
    return $sContent;
  }
  
  public function isEmpty() {
  
    return !($this->getRoot());
  }
  
  public function loadDatabase($sPath = '') {
    
    $rContent = db::query("SELECT s_content FROM xml WHERE v_path = '$sPath'");
    
    if (mysql_num_rows($rContent)) list($sContent) = mysql_fetch_row($rContent);
    else $sContent = '';
    
    $this->loadText($sContent);
  }
  
  public function loadFile($sPath) {
    
    $this->load(Controler::getDirectory().$sPath);
  }
  
  public function loadText($sContent) {
    
    $this->loadXML($sContent);
  }
  
  public function getRoot() {
    
    try { $oRoot = $this->documentElement; }
    catch (Exception $e) { $oRoot = null; }
    
    return $oRoot;
  }
  
  /*
   * Return a String from the result of the sQuery
   **/
  
  public function read($sQuery, $sNamespace = '') {
    
    $xPath = new DOMXPath($this);
    return $this->queryString($xPath->evaluate($sQuery));
  }
  
  /*
   * Return an XML_Element from the result of the sQuery
   **/
  
  public function get($sQuery, $sNamespace = '') {
    
    return $this->queryOne($this->query($sQuery));
  }
  
  public function set() {
    
    if (func_num_args() > 1) {
      
      foreach (func_get_args() as $mValue) $this->set($mValue);
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
          
      if (is_object($mValue)) {
        
        if ($mValue instanceof XML_Document) {
          
          if ($mValue instanceof XML_Action) $mValue = $mValue->parse();
          
          // XML_Document
          
          if ($this->getRoot()) $this->removeChild($this->getRoot());
          
          $mValue = $this->importNode($mValue->getRoot(), true);
          $this->appendChild($mValue);
          
        } else if ($mValue instanceof XML_Element) {
          
          // XML_Element
          
          if ($this->getRoot()) $this->removeChildNode($this->getRoot());
          // echo '<br/>+'.get_class($mValue).'+<br/>';
          
          if ($mValue->getDocument() && $mValue->getDocument() !== $this) {
            $mValue = $this->importNode($mValue, true);
            //dsp(Controler::getBacktrace());
          }
          // echo '<br/>-'.get_class($mValue).'-<br/>';
          $this->appendChild($mValue);
          
          // Else passed to Root
        } else if ($this->getRoot()) $this->getRoot()->set($mValue);
        
        // If String load as XML String
        
      } else if (is_string($mValue)) $this->loadText($mValue);
      
      return $mValue;
    }
    
    return null;
  }
  
  public function add() {
    
    if (func_num_args() > 1) {
      
      foreach (func_get_args() as $mValue) $this->add($mValue);
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
      
      if ($this->getRoot()) return $this->getRoot()->add($mValue);
      else return $this->set($mValue);
    }
    
    return null;
  }
  
  public function importNode($oChild, $bDepth) {
    
    if ($oChild instanceof HTML_Tag) $oChild->parse();
    return parent::importNode($oChild, $bDepth);
  }
  
  /*
   * Return a DOMNodeList from the result of the sQuery
   **/
   
  public function query($sQuery, $sNamespace = '') {
    
    $xPath = new DOMXPath($this);
    
    if ($sNamespace) {
      
      $xPath->registerNamespace('m', 'http://www.w3.org/1999/xhtml');
    }
    
    $mResult = $xPath->query($sQuery);
    if (!$mResult && Controler::isAdmin()) Controler::addMessage("XPath [$sQuery] : Aucun résultat.", 'warning');
    return $mResult;
  }
  
  /*
   * Extract the first result of a DOMNodeList if possible
   **/
  
  public function queryOne($oCollection) {
    
    if ($oCollection && $oCollection->length) return $oCollection->item(0);
    else return null;
  }
  
  /*
   * Extract a string value from a mixed variable
   **/
  
  public function queryString($mValue) {
    
    if (is_object($mValue)) {
      
      if (get_class($mValue) == 'DOMNodeList') $mValue = $mValue->item(0);
      if (get_class($mValue) == 'XML_Element' || get_class($mValue) == 'XML_Attribute') $mValue = $mValue->nodeValue;
    }
    
    return (string) $mValue;
  }
  
  public function parse($oTemplate) {
    
    $oStyleSheet = new XSLTProcessor();
    $oStyleSheet->importStylesheet($oTemplate);
    
    // Transformation et affichage du résultat
    
    $oResult = new XML_Document();
    $oResult->loadText($oStyleSheet->transformToXML($this));
    
    return $oResult;
  }
  
  public function __toString() {
    
    $this->formatOutput = true;
    if (!$this->isEmpty()) return $this->saveXML();
    else return '';
  }
}

class XML_Attribute extends DOMAttr {
  
  public function __construct($sName, $sValue) {
    
    parent::__construct($sName, $sValue);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function set($sValue) {
    
    $this->value = (string) $sValue;
  }
  
  public function __toString() {
    
    return $this->name.'="'.$this->value.'"';
  }
}

class XML_CData extends DOMCharacterData {
  
  
}

class XML_Text extends DOMText {
  
  public function __construct($sContent) {
    
    parent::__construct((string) $sContent);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function __toString() {
    
    return $this->nodeValue;
  }
}

/*
 * Alias de XML_Element
 **/

class XML_Tag extends XML_Element { }

class XML_Element extends DOMElement {
  
  private $aChildren = array();
  
  public function __construct($sName = 'default', $oContent = '', $aAttributes = array(), $oDocument = null) {
    
    parent::__construct($sName);
    // dsp(Controler::getBacktrace());
    //if (!$oDocument) 
    $oDocument = new XML_Document();
    $oDocument->appendChild($this);
    
    $this->set($oContent);
    if ($aAttributes) $this->addAttributes($aAttributes);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function read($sQuery) {
    
    $xPath = new DOMXPath($this->getDocument());
    return $this->getDocument()->queryString($xPath->evaluate($sQuery, $this));
  }
  
  public function get($sQuery) {
    
    return $this->getDocument()->queryOne($this->query($sQuery));
  }
  
  public function set() {
    
    if (func_num_args() > 1) {
      
      foreach (func_get_args() as $mValue) $this->set($mValue);
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
        
      if (is_object($mValue)) {
        
        if (($mValue instanceof XML_Document)) {
          
          $this->cleanChildren();
          $this->add($mValue);
          
        } else if (($mValue instanceof XML_Element) || ($mValue instanceof XML_Text)) {
          
          $this->cleanChildren();
          $mValue = $this->addChild($mValue);
          
        } else if ($mValue instanceof XML_Attribute) {
          
          $this->cleanAttributes();
          
          $this->setAttribute($mValue->name, $mValue->value);
          $mValue = $this->getAttribute($mValue->name);
          
        } else if ($mValue instanceof DOMNodeList) {
          
          $this->cleanChildren();
          foreach ($mValue as $oChild) $this->add($oChild);
          
          $mValue = null;
        }
        
      } else if (is_array($mValue)) {
        
        $this->cleanAttributes();
        $this->cleanChildren();
        
        $mValue = $this->add($mValue);
        
      } else {
        
        $this->cleanChildren();
        $mValue = $this->addText($mValue);
      }
      
      return $mValue;
    }
    
    return null;
  }
  
  public function cleanChildren() {
    
    if ($this->hasChildNodes()) foreach ($this->childNodes as $oChild) $this->removeChild($oChild);
  }
  
  public function cleanAttributes() {
    
    foreach ($this->attributes as $oAttribute) $this->removeAttributeNode($oAttribute);
  }
  
  public function add() {
    
    if (func_num_args() > 1) {
      
      foreach (func_get_args() as $mValue) $this->add($mValue);
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
      
      if (is_object($mValue)) {
        
        /* XML_Element or XML_Text */
        if ($mValue instanceof XML_Element || $mValue instanceof XML_Text) {
          
          $mValue = $this->addChild($mValue);
          
        } else if ($mValue instanceof XML_Attribute) {
          
          $mValue = $this->addAttribute($mValue);
          
        } else if ($mValue instanceof XML_Document) {
          
          if ($mValue instanceof XML_Action) $mValue = $mValue->parse();
          
          /* XML_Document */
          
          // TODO : Ajout des XMLNS
          // dsp(Controler::getBacktrace());
          if ($mValue->getRoot()) $mValue = $this->addChild($mValue->getRoot());
          else $mValue = null;
          
          /* Undefined object (Forced String) */
          
        } else $mValue = $this->addText($mValue);
        
        /* Array */
        
      } else if (is_array($mValue) && $mValue) {
        
        foreach ($mValue as $mSubValue) $this->add($mSubValue);
        
        /* String, Integer, Float, Bool, Resource, ... ? */
        
      } else $mValue = $this->addText($mValue);
      
      return $mValue;
    }
    
    return null;
  }
  
  public function addAttribute($oAttribute) {
    
    if ($oAttribute->getDocument() && $oAttribute->getDocument() != $this->getDocument())
      $oAttribute = $this->getDocument()->importNode($oAttribute);
    
    $this->setAttributeNode($oAttribute);
    
    return $oAttribute;
  }
  
  public function setAttributes($aAttributes) {
    
    $this->cleanAttributes();
    $this->addAttributes($aAttributes);
  }
  
  public function addAttributes($aAttributes) {
    
    foreach ($aAttributes as $sKey => $sValue) $this->addAttribute($sKey, $sValue);
  }
  
  public function addChild($oChild) {
    
    if (is_object($oChild)) {
      
      if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this->getDocument())) {
        
        $oChild = $this->getDocument()->importNode($oChild, true);
      }
      
      $this->appendChild($oChild);
      
    } else $this->add($oChild);
    
    return $oChild;
  }
  
  public function addText($sValue) {
    
    if ($sValue) {
      
      $oText = new XML_Text($sValue);
      $this->appendChild($oText);
      return $oText;
      
    } else return $sValue;
  }
  
  public function query($sQuery) {
    
    $xPath = new DOMXPath($this->getDocument());
    
    $mResult = $xPath->query($sQuery, $this);
    
    if (!$mResult && Controler::isAdmin()) Controler::addMessage("XPath [$sQuery] : Aucun résultat.", 'warning');
    return $mResult;
  }
  
  public function implode($sSep, $cChildren) {
    
    $sContent = '';
    
    foreach ($cChildren as $iIndex => $oChild) {
      
      $sContent .= (string) $oChild;
      if ($iIndex != $cChildren->length - 1) $sContent .= $sSep;
    }
    
    return $sContent;
  }
  
  public function __toString() {
    
    if ($this->childNodes->length) $sChildren = $this->implode('', $this->childNodes);
    else $sChildren = '';
    
    if ($this->attributes->length) $sAttributes = ' '.$this->implode(' ', $this->attributes);
    else $sAttributes = '';
    
    $sResult = '<'.$this->nodeName.$sAttributes;
    
    if ($sChildren) $sResult .= '>'.$sChildren.'</'.$this->nodeName.'>';
    else $sResult .= ' />';
    
    return $sResult;
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
