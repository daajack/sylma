<?php

class XML_Fragment extends DOMDocumentFragment { }

class XML_NodeList implements Iterator {
  
  private $aNodes = array();
  public $length;
  protected $iIndex = 0;
  
  public function __construct($oNodeList) {
    
    foreach ($oNodeList as $oNode) $this->aNodes[] = $oNode;
    
    if (is_array($oNodeList)) $this->length = count($oNodeList);
    else if ($oNodeList instanceof DOMNodeList) $this->length = $oNodeList->length;
  }
  
  public function toArray($sMode = 'default') {
    
    $aResults = array();
    
    foreach ($this as $oNode) {
      
      switch ($sMode) {
        
        case 'name' : $aResults[] = $oNode->getName(); break;
        
        default :
          
          if ($oNode->isEmpty()) $aResults[] = $oNode->getName();
          else $aResults[$oNode->getName()] = $oNode->getValue();
      }
    }
    
    return $aResults;
  }
  
  public function item($iKey) {
    
    if (array_key_exists($iKey, $this->aNodes)) return $this->aNodes[$iKey];
    else return null;
  }
  
  public function __call($sMethod, $aArguments) {
    
    foreach ($this->aNodes as $oNode) {
      
      if (method_exists($oNode, $sMethod)) $oNode->$sMethod($aArguments);
    }
  }
  
  public function rewind() {
    
    $this->iIndex = 0;
  }
  
  public function next() {
    
    $this->iIndex++;
  }
  
  public function key() {
    
    return $this->iIndex;
  }
  
  public function current() {
    
    return $this->aNodes[$this->iIndex];
  }
  
  public function valid() {
    
    return ($this->iIndex < count($this->aNodes));
  }
}

class XML_Document extends DOMDocument {
  
  public $isDocument = true;
  
  public function __construct($mChildren = 'div', $sSource = '') {
    
    parent::__construct('1.0');
    
    $this->preserveWhiteSpace = false;
    
    $this->registerNodeClass('DOMDocument', 'XML_Document');
    $this->registerNodeClass('DOMElement', 'XML_Element');
    $this->registerNodeClass('DOMText', 'XML_Text');
    $this->registerNodeClass('DOMAttr', 'XML_Attribute');
    $this->registerNodeClass('DOMCharacterData', 'XML_CData');
    $this->registerNodeClass('DOMDocumentFragment', 'XML_Fragment');
    
    if ($mChildren) {
      
      // if Object else String
      if (is_object($mChildren)) $this->set($mChildren);
      else if (is_string($mChildren)) $this->startString($mChildren, $sSource);
    }
  }
  
  public function dsp($bHtml = false) {
    
    echo '<pre>';
    $this->formatOutput = true;
    echo htmlentities($this).'<br/>';
    $this->formatOutput = false;
    echo '</pre>';
  }
  
  public function startString($sString, $sSource = '') {
    
    // if Path else LoadText else Root
    if ($sSource == 'file' || $sString{0} == '/') $this->loadDocument($sString, $sSource);
    else if ($sString{0} == '<') $this->loadText($sString);
    else $this->set(new XML_Element($sString, '', null, $this));
  }
  
  public function createNode($sName, $oContent = '', $aAttributes = null) {
    
    return new XML_Element($sName, $oContent, $aAttributes, $this);
  }
  
  public function loadDocument($sPath = '', $sSource = '') {
    
    $sContent = '';
    
    switch ($sSource) {
      
      case 'file' : 
        
        $this->loadFile($sPath);
        
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
    
    $this->load(MAIN_DIRECTORY.$sPath);
  }
  
  public function getChildren() {
    
    if ($this->getRoot()) return $this->getRoot()->getChildren();
    else return null;
  }
  
  public function loadText($sContent) {
    
    if ($sContent) $this->loadXML($sContent);
    else XML_Controler::addMessage('Aucun contenu', 'error');
  }
  
  public function getRoot() {
    
    try { $oRoot = $this->documentElement; }
    catch (Exception $e) { $oRoot = null; }
    
    return $oRoot;
  }
  
  /*
   * Return a String from the result of the sQuery
   **/
  
  public function read($sQuery = '', $sNamespace = '') {
    
    if ($this->getRoot()) {
      if ($sQuery) {
        
        if ($this->getRoot()) return $this->getRoot()->read($sQuery, $sNamespace);
        else return null;
        
      } else return $this->getRoot()->getValue();
      
    } else return null;
  }
  
  /*
   * Return an XML_Element from the result of the sQuery
   **/
  
  public function get($sQuery, $sNamespace = '') {
    
    if ($this->getRoot()) return $this->getRoot()->get($sQuery, $sNamespace);
    else return null;
  }
  
  public function set() {
    
    if (func_num_args() > 1) {
      
      $this->set(func_get_arg(0));
      for ($i = 1; $i < func_num_args(); $i++) $this->add(func_get_arg($i));
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
          
      if (is_object($mValue)) {
        
        if ($mValue instanceof XML_Document) {
          
          // XML_Document, XML_Action
          
          if ($mValue instanceof XML_Action) $mValue = $mValue->parse();
          
          if ($this->getRoot()) $this->removeChild($this->getRoot());
          
          $mValue = $this->importNode($mValue->getRoot(), true);
          $this->appendChild($mValue);
          
        } else if ($mValue instanceof XML_Element) {
          
          // XML_Element
          
          if ($this->getRoot()) $this->removeChild($this->getRoot());
          
          if ($mValue->getDocument() && $mValue->getDocument() !== $this) {
            
            $mValue = $this->importNode($mValue, true);
          }
          
          $this->appendChild($mValue);
          
          // Else passed to Root
          
        } else if ($this->getRoot()) $this->getRoot()->set($mValue);
        
        // If String load as XML String
        
      } else if (is_string($mValue)) $this->startString($mValue);
      
      return $mValue;
      
    } else if ($this->getRoot()) $this->removeChild($this->getRoot());
    
    return null;
  }
  
  public function addNode($sName, $oContent = '', $aAttributes = null) {
    
    if ($this->getRoot()) return $this->getRoot()->addNode($sName, $oContent, $aAttributes);
    else return $this->appendChild($this->createNode($sName, $oContent, $aAttributes));
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
  
  public function addArray($aChildren) {
    
    if ($this->getRoot()) return $this->getRoot()->addArray($aChildren);
    else return null;
  }
  
  public function importNode($oChild, $bDepth) {
    
    if ($oChild instanceof HTML_Tag) {
      
      $oChild = clone $oChild;
      $oChild->parse();
    }
    
    return parent::importNode($oChild, $bDepth);
  }
  
  /*
   * Return a DOMNodeList from the result of the sQuery
   **/
  
  public function query($sQuery, $sNamespace = '') {
    
    if ($this->getRoot()) return $this->getRoot()->query($sQuery, $sNamespace);
    else return null;
  }
  
  /*
   * Extract the first result of a DOMNodeList if possible
   **/
   
  public function queryArray($sQuery, $sNamespace = '') {
    
    $aResult = array();
    $oResult = $this->query($sQuery);
    foreach ($oResult as $oStatut) $aResult[] = $oStatut->read();
    
    return $aResult;
  }
  
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
  
  public function parseXSL($oTemplate) {
    
    $oStyleSheet = new XSLTProcessor();
    $oStyleSheet->importStylesheet($oTemplate);
    // Transformation et affichage du résultat
    
    $oResult = new XML_Document();
    $oResult->loadText($oStyleSheet->transformToXML($this));
    
    return $oResult;
  }
  
  public function __toString() {
    
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
 * Alias of XML_Element
 **/

class XML_Tag extends XML_Element { }

class XML_Element extends DOMElement {
  
  private $aChildren = array();
  
  public function __construct($sName = '', $oContent = '', $aAttributes = array(), $oDocument = null) {
    
    $sName = trim((string) $sName);
    if (!$sName) $sName = 'default';
    parent::__construct($sName);
    
    if (!$oDocument) $oDocument = new XML_Document();
    
    $oDocument->appendChild($this);
    $oDocument->removeChild($this);
    
    $this->set($oContent);
    if ($aAttributes) $this->addAttributes($aAttributes);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function read($sQuery = '') {
    
    if ($sQuery) {
      
      $xPath = new DOMXPath($this->getDocument());
      return $this->getDocument()->queryString($xPath->evaluate($sQuery, $this));
      
    } else if ($this->getValue()) return $this->getValue();
    else return $this->getName();
  }
  
  public function query($sQuery, $sNamespace = '') {
    
    if (is_string($sQuery) && $sQuery) {
      
      $xPath = new DOMXPath($this->getDocument());
      $mResult = $xPath->query($sQuery, $this);
      
      return new XML_NodeList($mResult);
      
    } else {
      echo 'Erreur de requête !';
      echo new HTML_Div(Controler::getBacktrace());
      exit;
      return null;
    // ERROR : if (!$mResult) Pas de résultat dans la requête
    }
  }
  
  public function get($sQuery) {
    
    return $this->getDocument()->queryOne($this->query($sQuery));
  }
  
  public function set() {
    
    if (func_num_args() > 1) {
      
      $this->set(func_get_arg(0));
      for ($i = 1; $i < func_num_args(); $i++) $this->add(func_get_arg($i));
      
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
          
        } else if ($mValue instanceof XML_NodeList) {
          
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
    
    return $this->insert(func_get_args());
  }
  
  public function shift() {
    
    if (!$this->isEmpty()) return $this->insert(func_get_args(), $this->get('*'));
    else return $this->insert(func_get_args());
  }
  
  public function insert($mValue, $oNext = null) {
    
    if (is_object($mValue)) {
      
      /* XML_Element or XML_Text */
      
      if ($mValue instanceof XML_Element || $mValue instanceof XML_Text) {
        
        /* XML_Element or XML_Text */
        
        $mValue = $this->addChild($mValue, $oNext);
        
      } else if ($mValue instanceof XML_Attribute) {
        
        /* XML_Attribute */
        
        $mValue = $this->addAttribute($mValue);
        
      } else if ($mValue instanceof XML_Document) {
        
        if ($mValue instanceof XML_Action) {
          
          /* XML_Action */
          
          $mValue = $mValue->parse();
          
          // If result is not a doc
          if (!($mValue instanceof XML_Document)) return $this->insert($mValue, $oNext);
        }
        
        /* XML_Document */
        
        // TODO : add XMLNS
        
        if ($mValue->getRoot()) $mValue = $this->addChild($mValue->getRoot(), $oNext);
        else $mValue = null;
        
        
      } else if ($mValue instanceof XML_NodeList) {
        
        /* XML_NodeList */
        
        foreach ($mValue as $oChild) $this->insert($oChild, $oNext);
        
        /* Undefined object (Forced String) */
        
      } else $mValue = $this->addText($mValue);
      
      /* Array */
      
    } else if (is_array($mValue) && $mValue) {
      
      foreach ($mValue as $mSubValue) $mValue = $this->insert($mSubValue, $oNext);
      
      /* String, Integer, Float, Bool, Resource, ... ? */
      
    } else $mValue = $this->addText($mValue);
    
    return $mValue;
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
    
    foreach ($aAttributes as $sKey => $sValue) $this->setAttribute($sKey, $sValue);
  }
  
  public function addChild($oChild, $oNext = null) {
    
    if (is_object($oChild)) {
      
      if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this->getDocument())) {
        
        $oChild = $this->getDocument()->importNode($oChild, true);
      }
      
      if ($oNext) $this->insertBefore($oChild, $oNext);
      else $this->appendChild($oChild);
      
    } else $this->add($oChild);
    
    return $oChild;
  }
  
  public function addNode($sName, $oContent = '', $aAttributes = null) {
    
    return $this->appendChild($this->getDocument()->createNode($sName, $oContent, $aAttributes));
  }
  
  public function insertNode($sName, $oContent = '', $aAttributes = null, $oNext = null) {
    
    if ($oNext) return $this->insertBefore($this->getDocument()->createNode($sName, $oContent, $aAttributes), $oNext);
    else return $this->addNode($sName, $oContent, $aAttributes);
  }
  
  public function addArray($aChildren) {
    
    $aResult = array();
    
    foreach ($aChildren as $sKey => $sValue) {
      
      if (!is_numeric($sKey)) $aResult[] = $this->addNode($sKey, $sValue);
      else $aResult[] = $this->addNode($sValue);
    }
    
    return $aResult;
  }
  
  public function addText($sValue, $oNext = null) {
    
    if ($sValue) {
      
      $oText = new XML_Text($sValue);
      
      if ($oNext) $this->insertBefore($oText, $oNext);
      else $this->appendChild($oText);
      
      return $oText;
      
    } else return $sValue;
  }
  
  public function getChildren() {
    
    return new XML_NodeList($this->childNodes);
  }
  
  public function isEmpty() {
    
    return !$this->hasChildNodes();
  }
  
  public function getName() {
    
    return $this->nodeName;
  }
  
  public function getValue() {
    
    return $this->textContent;
  }
  
  public function remove() {
    
    return $this->parentNode->removeChild($this);
  }
  
  public function implode($sSep, $cChildren) {
    
    $sContent = '';
    
    foreach ($cChildren as $iIndex => $oChild) {
      
      $sContent .= (string) $oChild;
      if ($iIndex != $cChildren->length - 1) $sContent .= $sSep;
    }
    
    return $sContent;
  }
  
  public function dsp() {
    
    echo htmlentities($this).'<br/>';
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
