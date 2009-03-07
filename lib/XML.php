<?php


function strtoxml ($sValue) {
  
  $oDocument = new XML_Document('<div>'.$sValue.'</div>');
  
  if ($oDocument->getRoot() && !$oDocument->getRoot()->isEmpty()) {
    
    return $oDocument->getRoot()->getChildren();
    
  } else {
    
    XML_Controler::addMessage(array('StrToXml : '.t('Transformation impossible'), new HTML_Br, $sValue), 'error');
    return null;
  }
}

class XML_Document extends DOMDocument {
  
  public $isDocument = true;
  
  public function __construct($mChildren = '', $sSource = '') {
    
    parent::__construct('1.0', 'utf-8');
    
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
  
  public function appendRights() {
    
    $this->buildRights();
    
    // if (Controler::getUser()) {
      // (Controler::isUser($sOwner) && Controler::isGroup($sGroup) && $sMode) {
    
  }
  
  public function buildRights() {
    
    if (Controler::getUser() && $this->getRoot() && $this->lookupNamespaceURI('ls')) {
      
      $aNodes = $this->query('//*[@ls:owner]', 'ls'); //|@ls:mode|@ls:group
      
      /*foreach ($aNodes as $oNode) {
        
        if ($sOwner = $oNode->read('@ls:owner', 'ls')) {
          
          if ($sOwner == Controler::getUser()->getBloc('user')) {
            
            // Propriétaire du fichier
            
          }
          
          if ($sMode = $oNode->read('@ls:mode', 'ls')) {
            
            $sGroup = $oNode->read('@ls:group', 'ls');
            
            if ($sGroup !== '' && $sGroup !== null) {
              
              // Appartient à un groupe
              
              if (Controler::getUser()->isMember($sGroup)) $bGroup = true;
            }
          }
          
        }
      }*/
    }
  }
  
  public function view($bHtml = false) {
    
    $this->formatOutput = true;
    $oView = new XML_Element('pre');
    $oView->addText($this);
    $this->formatOutput = false;
    
    return $oView;
  }
  
  public function dsp($bHtml = false) {
    
    echo $this->view();
  }
  
  public function startString($sString, $sSource = '') {
    
    // if Path else XML String else new XML_Element
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
      
      case 'db' : 
        
        $this->loadDatabase($sPath);
        
      break;
      
      case 'file' : 
      default :
        
        XML_Controler::addMessage(t('Document : Chargement d\'un fichier : ').$sPath, 'report');
        $this->loadFile($sPath);
        //XML_Controler::addMessage(array(t('Aucun contenu. Le chargement du fichier \''), new HTML_Strong($sPath), '\' a échoué !'), 'error'); }
        
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
  
  public function load($sPath) {
    
    parent::load($sPath);
    $this->appendRights();
  }
  
  public function loadText($sContent) {
    
    if ($sContent) parent::loadXML($sContent);
    else XML_Controler::addMessage('Document : Aucun contenu. La chaîne est vide !', 'error');
    
    $this->appendRights();
  }
  
  /*
   * Method loadText() alias
   * Security override
   **/
  public function loadXML() {
    
    return $this->loadText($sContent);
  }
  
  public function getChildren() {
    
    if ($this->getRoot()) return $this->getRoot()->getChildren();
    else return null;
  }
  
  public function getRoot() {
    
    try { $oRoot = $this->documentElement; }
    catch (Exception $e) { $oRoot = null; }
    
    return $oRoot;
  }
  
  public function test($sPath) {
    
    return (bool) $this->get($sPath);
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
      
      $this->set(func_get_args());
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
          
      if (is_object($mValue)) {
        
        if ($mValue instanceof XML_Document) {
          
          // XML_Document, XML_Action
          
          if ($mValue instanceof XML_Action) $mValue = $mValue->parse();
          
          if ($this->getRoot()) $this->removeChild($this->getRoot());
          
          $mValue = $this->importNode($mValue->getRoot(), true);
          $this->setChild($mValue);
          
        } else if ($mValue instanceof XML_Element) {
          
          // XML_Element
          
          if ($this->getRoot()) $this->removeChild($this->getRoot());
          
          if ($mValue->getDocument() && $mValue->getDocument() !== $this) {
            
            $mValue = $this->importNode($mValue, true);
          }
          
          $this->setChild($mValue);
          
          // Else passed to Root
          
        } else if ($this->getRoot()) $this->getRoot()->set($mValue);
        
        // If String load as XML String
        
      } else if (is_array($mValue) && $mValue) {
        
        if (count($mValue) > 1) {
          
          // > 1
          
          $aChildren = array();
          
          $this->set($this->array_shift($mValue));
          foreach ($mValue as $oChild) $aChildren = $this->add($oChild);
          
          $mValue = $aChildren;
          
          // = 1
          
        } else $mValue = $this->set(array_pop($mValue));
        
      } else if (is_string($mValue)) $this->startString($mValue);
      
      return $mValue;
      
    } else if ($this->getRoot()) $this->removeChild($this->getRoot());
    
    return null;
  }
  
  public function addNode($sName, $oContent = '', $aAttributes = null) {
    
    if ($this->getRoot()) return $this->getRoot()->addNode($sName, $oContent, $aAttributes);
    else return $this->setChild($this->createNode($sName, $oContent, $aAttributes));
  }
  
  public function setChild($oChild) {
    
    if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this)) {
      
      $oChild = $this->importNode($oChild, true);
    }
    
    parent::appendChild($oChild);
    // $this->appendRights();
    
    return $oChild;
  }
  
  /*
   * Method add() alias
   * Security override
   **/
  public function appendChild() {
    
    $this->add(func_get_args());
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
/*
 * Alias of XML_Element
 **/

class XML_Tag extends XML_Element { }

class XML_Element extends DOMElement {
  
  private $aChildren = array();
  private $bReady = false;
  
  public function __construct($sName = '', $oContent = '', $aAttributes = array(), $oDocument = null) {
    
    // $this->bReady = true;
    
    $sName = trim((string) $sName);
    if (!$sName) $sName = 'default';
    parent::__construct($sName);
    
    if (!$oDocument) $oDocument = new XML_Document();
    
    $oDocument->add($this);
    $this->remove();
    
    $this->set($oContent);
    if ($aAttributes) $this->addAttributes($aAttributes);
    
  }
  
  public function isReady() {
    
    return $this->bReady;
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  /*** Reading ***/
  
  public function view($bHtml = false) {
    
    return new XML_Element('pre', (string) $this);
  }
  
  public function dsp($bHtml = false) {
    
    echo new XML_Element('pre', htmlentities($this));
  }
  
  public function read($sQuery = '', $sNamespace = '') {
    
    if ($sQuery) {
      
      $xPath = new DOMXPath($this->getDocument());
      
      if ($sNamespace) $xPath->registerNamespace($sNamespace, $this->lookupNamespaceURI($sNamespace));
      
      $mResult = $this->getDocument()->queryString($xPath->evaluate($sQuery, $this));
      if (!$mResult) XML_Controler::addMessage("Element : Requête '$sQuery' : Aucun résultat.", 'warning');
      
      return $mResult;
      
    } else if ($this->getValue()) return $this->getValue();
    else return $this->getName();
  }
  
  /*
   * XPath Query
   * @param $sQuery
   *   Query to execute
   * @param $sNamespace
   *   Namespace where to lookup the result
   **/
  
  public function query($sQuery, $sNamespace = '') {
    
    if (!$this->isEmpty() && is_string($sQuery) && $sQuery) {
      
      $xPath = new DOMXPath($this->getDocument());
      $mResult = null;
      
      if ($sNamespace) {
        
        // Use Namespace
        
        if ($sUrl = $this->lookupNamespaceURI($sNamespace)) {
          
          XML_Controler::addMessage("Element : Ajout de l'espace de nom : '$sNamespace'", 'report');
          $xPath->registerNamespace($sNamespace, $sUrl);
          
          $mResult = $xPath->query($sQuery, $this);
          
        } else {
          
          XML_Controler::addMessage(strtoxml(sprintf(t("Element : Espace de nom '%s' inconnu !"), new HTML_Strong($sNamespace))), 'warning');
        }
        
      } else {
        
        // No Namespace
        
        $mResult = $xPath->query($sQuery, $this);
      }
      
      if (!$mResult || !$mResult->length) XML_Controler::addMessage(array('Element : ', strtoxml(sprintf(t("Requête '%s' : Aucun résultat."), new HTML_Strong($sQuery)))), 'warning');
      
      return new XML_NodeList($mResult);
      
    } else {
      
      if ($this->isEmpty()) XML_Controler::addMessage('Element : Requête impossible, élément vide !', 'warning');
      else XML_Controler::addMessage('Element : Requête vide !', 'warning');
    }
  }
  
  public function test($sPath, $sNamespace = '') {
    
    return (bool) $this->get($sPath, $sNamespace);
  }
  
  public function get($sQuery, $sNamespace = '') {
    
    return $this->getDocument()->queryOne($this->query($sQuery, $sNamespace));
  }
  
  /*** Attributes ***/
  
  /*
   * setAttributeNode() Security override
   **/
  public function setAttributeNode($oAttribute) {
    
    // TODO : RIGHTS
    parent::setAttributeNode($oAttribute);
  }
  
  /*
   * setAttributeNode() Security override
   **/
  public function setAttribute($sName, $sValue) {
    
    // TODO : RIGHTS
    parent::setAttribute($sName, $sValue);
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
  
  /*** Children ***/
  
  public function set() {
    
    if (func_num_args() > 1) {
      
      $this->set(func_get_arg(0));
      
      // If this is the root, then we add the others in it
      if ($this->parentNode) $oParent = $this->parentNode;
      else $oParent = $this;
      
      for ($i = 1; $i < func_num_args(); $i++) $oParent->add(func_get_arg($i));
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
      
      if (is_object($mValue)) {
        
        if (($mValue instanceof XML_Document)) {
          
          // XML_Document
          
          $this->cleanChildren();
          $this->add($mValue);
          
        } else if (($mValue instanceof XML_Element) || ($mValue instanceof XML_Text)) {
          
          // XML_Element, XML_Text
          
          $this->cleanChildren();
          $mValue = $this->insertChild($mValue);
          
        } else if ($mValue instanceof XML_Attribute) {
          
          // XML_Attribute
          
          $this->cleanAttributes();
          return $this->setAttributeNode($mValue);
          
        } else if ($mValue instanceof XML_NodeList) {
          
          // XML_NodeList
          
          $this->cleanChildren();
          return $this->add(func_get_args());
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
    
    if (!$this->isEmpty()) return $this->insert(func_get_args(), $this->firstChild);
    else return $this->insert(func_get_args());
  }
  
  public function insertBefore() {
    
    if ($this->parentNode) $this->parentNode->insert(func_get_args(), $this);
    else XML_Controler::addMessage('Element : Impossible d\'insérer un noeud ici (root)', 'error');
  }
  
  public function insertAfter() {
    
    if ($this->nextSibling) $this->nextSibling->insertBefore(func_get_args());
    else if ($this->parentNode) $this->parentNode->add(func_get_args());
  }
  
  public function insert($mValue, $oNext = null) {
    
    if (is_object($mValue)) {
      
      /* XML_Element or XML_Text */
      
      if ($mValue instanceof XML_Element || $mValue instanceof XML_Text) {
        
        /* XML_Element or XML_Text */
        
        $mValue = $this->insertChild($mValue, $oNext);
        
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
        
        if ($mValue->getRoot()) $mValue = $this->insertChild($mValue->getRoot(), $oNext);
        else $mValue = null;
        
        
      } else if ($mValue instanceof XML_NodeList) {
        
        /* XML_NodeList */
        
        foreach ($mValue as $oChild) $this->insert($oChild, $oNext);
        
        /* Undefined object (Forced String) */
        
      } else $mValue = $this->addText($mValue);
      
      /* Array */
      
    } else if (is_array($mValue)) {
      
      if ($mValue) foreach ($mValue as $mSubValue) $mValue = $this->insert($mSubValue, $oNext);
      /* String, Integer, Float, Bool, Resource, ... ? */
      
    } else $mValue = $this->addText($mValue);
    
    return $mValue;
  }
  
  public function insertChild($oChild, $oNext = null) {
    
    if (is_object($oChild) && ($oChild instanceof XML_Element || $oChild instanceof XML_Text)) {
      
      if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this->getDocument())) {
        
        $oChild = $this->getDocument()->importNode($oChild, true);
      }
      
      // TODO : RIGHTS
      if ($oNext) parent::insertBefore($oChild, $oNext);
      else parent::appendChild($oChild);
      
      return $oChild;
      
    } return null;
  }
  
  public function remove() {
    
    if ($this->parentNode) return $this->parentNode->removeChild($this);
    else if ($this->getDocument()->getRoot() == $this) $this->getDocument()->removeChild($this);
  }
  
  public function getChildren() {
    
    return new XML_NodeList($this->childNodes);
  }
  
  public function isEmpty() {
    
    return !$this->hasChildNodes();
  }
  
  public function getValue() {
    
    return $this->textContent;
  }
  
  /*
   * Method add() alias
   * appendChild() Security override
   **/
  public function appendChild() {
  
    $this->add(func_get_args());
  }
  
  /*** Node (Automatically created Element basis on strings and arrays) ***/
  
  public function addNode($sName, $oContent = '', $aAttributes = null) {
    
    return $this->insertChild($this->getDocument()->createNode($sName, $oContent, $aAttributes));
  }
  
  public function insertNode($sName, $oContent = '', $aAttributes = null, $oNext = null) {
    
    return $this->insertChild($this->getDocument()->createNode($sName, $oContent, $aAttributes), $oNext);
  }
  
  /*** Array ***/
  
  public function addArray($aChildren) {
    
    $aResult = array();
    
    foreach ($aChildren as $sKey => $sValue) {
      
      if (!is_numeric($sKey)) $aResult[] = $this->addNode($sKey, $sValue);
      else $aResult[] = $this->addNode($sValue);
    }
    
    return $aResult;
  }
  
  /*** Text ***/
  
  public function addText($sValue) {
    
    return $this->insertChild(new XML_Text($sValue));
  }
  
  public function getName() {
    
    return $this->nodeName;
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
    
    try {
      
      // if (!$this->isReady()) return '';
      
      if ($this->childNodes && $this->childNodes->length) $sChildren = $this->implode('', $this->childNodes);
      else $sChildren = '';
      
      if ($this->attributes && $this->attributes->length) $sAttributes = ' '.$this->implode(' ', $this->attributes);
      else $sAttributes = '';
      
      $sResult = '<'.$this->nodeName.$sAttributes;
      
      if ($sChildren) $sResult .= '>'.$sChildren.'</'.$this->nodeName.'>';
      else $sResult .= ' />';
      
      return $sResult;
      
		} catch ( Exception $e ) {
      
			XML_Controler::addMessage('Element : '.$e->getMessage(), 'error');
		}
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
  
  public function __construct($mContent) {
    
    if (is_object($mContent)) {
      
      if (method_exists($mContent, '__toString')) $mContent = (string) $mContent;
      else XML_Controler::addMessage('Text : Objet interdit', 'error');
    }
    
    // if (!(is_string($mContent) || is_numeric($mContent))) $mContent = '';
    parent::__construct($mContent);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function __toString() {
    
    try {
      
      return $this->nodeValue;
      
		} catch ( Exception $e ) {
      
			XML_Controler::addMessage('Text : '.$e->getMessage(), 'error');
		}
  }
}

class XML_NodeList implements Iterator {
  
  private $aNodes = array();
  public $length;
  protected $iIndex = 0;
  
  public function __construct($oNodeList) {
    
    if ($oNodeList) {
      
      foreach ($oNodeList as $oNode) $this->aNodes[] = $oNode;
      
      if (is_array($oNodeList)) $this->length = count($oNodeList);
      else if ($oNodeList instanceof DOMNodeList) $this->length = $oNodeList->length;
      
    } else {
      
      // XML_Controler::addMessage('NodeList : Tableau vide !', 'warning');
    }
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

class XML_Fragment extends DOMDocumentFragment { }

class XSL_Document extends XML_Tag {
  
  public function __construct() {
    
    $this->insertChild(new XML_Tag('output', array('method' => 'xml', 'encoding' => 'utf-8'), true, 'xsl'));
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
