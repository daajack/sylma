<?php


function xt () {
  
  if (func_num_args()) {
    
    $aArguments = func_get_args();
    $sValue = array_shift($aArguments);
    
    return strtoxml(t(vsprintf($sValue, $aArguments)));
  }
}

function strtoxml ($sValue) {
  
  $oDocument = new XML_Document('<div>'.$sValue.'</div>');
  
  if ($oDocument->getRoot() && !$oDocument->getRoot()->isEmpty()) {
    
    return $oDocument->getRoot()->getChildren();
    
  } else {
    
    XML_Controler::addMessage(array(
      t('StrToXml : Transformation impossible'),
      new HTML_Br,
      new HTML_Strong($sValue)), 'error');
    
    return null;
  }
}

class XML_Document extends DOMDocument {
  
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
  
  private function appendRights() {
    
    // $this->buildRights();
    
    // if (Controler::getUser()) {
      // (Controler::isUser($sOwner) && Controler::isGroup($sGroup) && $sMode) {
    
  }
  
  private function buildRights() {
    
    if (Controler::getUser() && $this->getRoot() && $this->hasNamespace(NS_SECURITY)) {
      
      $oNodes = $this->query('//*[@ls:owner|@ls:mode|@ls:group]');
      
      for ($i = $oNodes->length - 1; $i >= 0; $i--) {
        
        $oNode = $oNodes->item($i);
        
        $oNode->setRights(array(
          'owner' => $oNode->getAttribute('ls:owner'),
          'group' => $oNode->getAttribute('ls:group'),
          'mode' => $oNode->getAttribute('ls:mode')));
        
        if (Controler::isAdmin()) {
          
          XML_Controler::addMessage(array(
            'Element sécurisé : ',
            new HTML_Strong($oNode->getName()),
            ' - ',
            implosion(':', ' | ', $oNode->getRights())), 'notice');
        }
      }
    }
  }
  
  public function view($bHtml = false) {
    
    $oView = new XML_Document($this);
    $oView->formatOutput();
    
    $oPre = new XML_Tag('pre');
    $oPre->addText($oView);
    
    return $oPre;
  }
  
  public function formatOutput() {
    
    if ($this->getRoot()) $this->getRoot()->formatOutput();
  }
  
  public function dsp($bHtml = false) {
    
    $oView = new XML_Document($this);
    $oView->formatOutput();
    
    $oPre = new XML_Tag('pre');
    $oPre->addText(htmlentities($oView));
    
    echo $oPre;
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
        
        XML_Controler::addMessage(xt('Document : Chargement d\'un fichier - %s', new HTML_Strong($sPath)), 'report');
        $this->loadFile($sPath);
        
        if ($this->isEmpty())
          XML_Controler::addMessage(xt('Document : Aucun contenu dans %s', new HTML_Strong($sPath)), 'warning');
        
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
    XML_Controler::addStat('load');
    
    $this->appendRights();
  }
  
  public function loadText($sContent) {
    
    if ($sContent) {
      
      parent::loadXML($sContent);
      if ($this->isEmpty()) XML_Controler::addMessage(array('Document : contenu invalide', new HTML_Br, $sContent), 'error');
      
    } else XML_Controler::addMessage('Document : Aucun contenu. La chaîne est vide !', 'error');
    
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
          
          if ($mValue->getRoot()) {
            
            $mValue = $this->importNode($mValue->getRoot(), true);
            $this->setChild($mValue);
            
          } else XML_Controler::addMessage('Document->set() - Document vide', 'warning');
          
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
    
    if ($oChild && is_object($oChild)) {
      
      if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this)) {
        
        $oChild = $this->importNode($oChild, true);
      }
      
      parent::appendChild($oChild);
      
    } else XML_Controler::addMessage('Element->setChild : No object', 'error');
    
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
  
  public function addArray($aChildren, $sName = '') {
    
    if ($this->getRoot()) return $this->getRoot()->addArray($aChildren, $sName);
    else return null;
  }
  
  public function importNode($oChild, $bDepth) {
    
    if ($oChild) {
      
      if ($oChild instanceof HTML_Tag) {
        
        $oChild = clone $oChild;
        $oChild->parse();
      }
      
      return parent::importNode($oChild, $bDepth);
      
    } else XML_Controler::addMessage('Document->importNode : No object', 'error');
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
    XML_Controler::addStat('parse');
    
    return $oResult;
  }
  
  /*
   * Method __toString() alias
   * Security override
   **/
  public function saveXML() {
    
    return $this->__toString();
  }
  
  public function __toString() {
    
    if (!$this->isEmpty()) return parent::saveXML();
    else return '';
  }
}
/*
 * Alias of XML_Element
 **/

class XML_Tag extends XML_Element { }

class XML_Element extends DOMElement {
  
  private $aRights = array();
  
  public function __construct($sName = '', $oContent = '', $aAttributes = array(), $oDocument = null) {
    
    $sName = trim((string) $sName);
    if (!$sName) $sName = 'default';
    parent::__construct($sName);
    
    if (!$oDocument) $oDocument = new XML_Document();
    $oDocument->add($this);
    // $this->remove();
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
  
  public function getRights() {
    
    if (Controler::isAdmin()) return $this->aRights;
  }
  
  public function setRights($aRights) {
    
    $bUpdate = false;
    
    if (
      !array_key_exists('owner', $this->aRights) &&
      array_key_exists('owner', $aRights) &&
      $aRights['owner']) {
      
      $this->aRights['owner'] = $aRights['owner'];
      $bUpdate = true;
    }
    
    if (
      !array_key_exists('group', $this->aRights) && 
      array_key_exists('group', $aRights) &&
      ($aRights['group'] !== '')) {
      
      $this->aRights['group'] = $aRights['group'];
      $bUpdate = true;
    }
    
    if (
      !array_key_exists('mode', $this->aRights) && 
      array_key_exists('mode', $aRights) &&
      $aRights['mode']) {
      
      $this->aRights['mode'] = $aRights['mode'];
      $bUpdate = true;
    }
    
    foreach ($this->getRights() as $sKey => $sValue)
      $this->setAttribute('ls:'.$sKey, $sValue);
    
    if ($bUpdate) {
      
      foreach ($this->getChildren() as $oChild) {
        
        if ($oChild->nodeType == 1) $oChild->setRights($this->getRights());
      }
    }
    
    // XML_Controler::addMessage('+'.$bUpdate.' - '.implosion(':', ' | ', $this->getRights()));
  }
  
  public function view($bHtml = false) {
    
    $oView = $this;
    $oView->formatOutput();
    
    return new XML_Element('pre', htmlentities($oView));
  }
  
  public function dsp($bHtml = false) {
    
    echo $this->view();
  }
  
  protected function _buildXPath($sQuery = '', $sPrefix = '') {
    
    $oXPath = new DOMXPath($this->getDocument());
    
    if ($sUrl = $this->getDocument()->getRoot()->getAttribute('xmlns')) {
      
      if ($sPrefix != '-') $sPrefix = 'ns';
      else $sPrefix = '';
    }
    
    if ($sPrefix) {
      
      // Use Namespace
      
      if ($sPrefix != 'ns') $sUrl = $this->lookupNamespaceURI($sPrefix);
      
      if ($sUrl) {
        
        XML_Controler::addMessage(xt(
          "Element : Ajout de l'espace de nom : '%s' => '%s'",
          new HTML_Strong($sPrefix),
          new HTML_Strong($sUrl)), 'report');
        
        $oXPath->registerNamespace($sPrefix, $sUrl);
        
      } else {
        
        XML_Controler::addMessage(xt("Element : Espace de nom '%s' inconnu !", new HTML_Strong($sPrefix)), 'warning');
      }
    }
    
    return $oXPath;
  }
  
  public function read($sQuery = '', $sPrefix = '') {
    
    if ($sQuery) {
      
      $xPath = new DOMXPath($this->getDocument());
      
      if ($sPrefix) $xPath->registerNamespace($sPrefix, $this->lookupNamespaceURI($sPrefix));
      
      $mResult = $this->_buildXPath($sQuery, $sPrefix)->evaluate($sQuery, $this);
      $mResult = $this->getDocument()->queryString($mResult);
      
      XML_Controler::addStat('query');
      
      if ($mResult === null) {
        
        $mResult = '';
        XML_Controler::addMessage(xt("Element->read(%s) : Aucun résultat", $sQuery), 'warning');
      }
      
      return $mResult;
      
    } else if ($this->getValue()) return $this->getValue();
    else return $this->getName();
  }
  
  /*
   * XPath Query
   * @param $sQuery
   *   Query to execute
   * @param $sPrefix
   *   Prefix of the namespace where to lookup the result
   **/
  
  public function query($sQuery, $sPrefix = '') {
    
    if (!$this->isEmpty() && is_string($sQuery) && $sQuery) {
      
      $mResult = $this->_buildXPath($sQuery, $sPrefix)->query($sQuery, $this);
      
      XML_Controler::addStat('query');
      
      if (!$mResult || !$mResult->length)
        XML_Controler::addMessage(xt("Element->query(%s) : Aucun résultat", new HTML_Strong($sQuery)), 'warning');
      // ////// report type will crash system in DEBUG mode, maybe something TODO /////// //
      return new XML_NodeList($mResult);
      
    } else {
      
      if ($this->isEmpty()) XML_Controler::addMessage('Element : Requête impossible, élément vide !', 'warning');
      else XML_Controler::addMessage('Element : Requête vide !', 'warning');
    }
  }
  
  public function get($sQuery, $sPrefix = '') {
    
    return $this->getDocument()->queryOne($this->query($sQuery, $sPrefix));
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
   * setAttribute() Security override
   **/
  public function setAttribute($sName, $sValue = '') {
    
    // TODO : RIGHTS
    if ($sValue) parent::setAttribute($sName, $sValue);
    else $this->removeAttribute($sName);
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
      if (!$this->isParent()) $oParent = $this->parentNode;
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
        
      } else if ($mValue !== null) {
        
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
    
    if (!$this->isParent()) $this->parentNode->insert(func_get_args(), $this);
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
        
      } else $mValue = $this->insertText($mValue, $oNext);
      
      /* Array */
      
    } else if (is_array($mValue)) {
      
      if ($mValue) foreach ($mValue as $mSubValue) $mValue = $this->insert($mSubValue, $oNext);
      /* String, Integer, Float, Bool, Resource, ... ? */
      
    } else if ($mValue !== null) $mValue = $this->insertText($mValue, $oNext);
    
    return $mValue;
  }
  
  public function insertText($sValue, $oNext = null) {
    
    return $this->insertChild(new XML_Text($sValue), $oNext);
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
  
  public function isParent() {
    
    return ($this->parentNode instanceof XML_Document);
  }
  
  public function hasChildren() {
    
    return $this->hasChildNodes();
  }
  
  public function isEmpty() {
    
    return !$this->hasChildren();
  }
  
  /*
   * Alias function add()
   * appendChild() Security override
   **/
  public function appendChild() {
  
    $this->add(func_get_args());
  }
  
  /*** Node : Automatically created Element based on strings and arrays ***/
  
  public function addNode($sName, $oContent = '', $aAttributes = null) {
    
    return $this->insertChild($this->getDocument()->createNode($sName, $oContent, $aAttributes));
  }
  
  public function insertNode($sName, $oContent = '', $aAttributes = null, $oNext = null) {
    
    return $this->insertChild($this->getDocument()->createNode($sName, $oContent, $aAttributes), $oNext);
  }
  
  /*** Array ***/
  
  public function addArray($aChildren, $sName = '') {
    
    $aResult = array();
    
    foreach ($aChildren as $sKey => $sValue) {
      
      if ($sName) $aResult[] = $this->addNode($sName, $sValue, array('name' => $sKey));
      else if (!is_numeric($sKey)) $aResult[] = $this->addNode($sKey, $sValue);
      else $aResult[] = $this->addNode($sValue);
    }
    
    return $aResult;
  }
  
  /*** Text ***/
  
  public function getValue() {
    
    return $this->textContent;
  }
  
  public function addText($sValue) {
    
    return $this->insertText($sValue);
  }
  
  public function hasNamespace($sNamespace = '') {
    
    if ($sNamespace) return ($this->getNamespace() == $sNamespace);
    else return ($this->getNamespace());
  }
  
  public function getNamespace() {
    
    return $this->namespaceURI;
  }
  
  public function getPrefix() {
    
    return $this->prefix;
  }
  
  public function getName($bLocal = false) {
    
    if ($bLocal) return $this->localName;
    else return $this->nodeName;
  }
  
  public function implode($sSep, $cChildren) {
    
    $sContent = '';
    
    foreach ($cChildren as $iIndex => $oChild) {
      
      $sContent .= (string) $oChild;
      if ($iIndex != $cChildren->length - 1) $sContent .= $sSep;
    }
    
    return $sContent;
  }
  
  public function formatOutput($iLevel = 0) {
    
    if (!$this->isParent()) {
      
      $this->insertBefore("\n".str_repeat('  ', $iLevel));
    }
    
    foreach ($this->getChildren() as $oChild) $oChild->formatOutput($iLevel + 1);
    if ($this->hasChildren()) $this->add("\n".str_repeat('  ', $iLevel));
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
  
  // private $aRights = array();
  
  public function __construct($mContent) {
    
    if (is_object($mContent)) {
      
      if (method_exists($mContent, '__toString')) $mContent = (string) $mContent;
      else XML_Controler::addMessage('Text : Objet not allowed', 'error');
    }
    
    // if (!(is_string($mContent) || is_numeric($mContent))) $mContent = '';
    parent::__construct($mContent);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  // public function setRights($aRights) {
    
  // }
  public function formatOutput($iLevel = 0) {
    
    return null;
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
      else XML_Controler::addMessage('NodeList : Type invalide !', 'error');
      
    } else {
      
      // XML_Controler::addMessage('NodeList : Tableau vide !', 'warning');
    }
  }
  
  public function toArray($sMode = '') {
    
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
      
      if (method_exists($oNode, $sMethod)) {
        
        $aEvalArguments = array();
        for ($i = 0; $i < count($aArguments); $i++) $aEvalArguments[] = "\$aArguments[$i]";
        
        eval('$oResult = $oNode->$sMethod('.implode(', ', $aEvalArguments).');');
      }
      else XML_Controler::addMessage(xt('NodeList : Méthode %s introuvable', new HTML_Strong($sMethod)), 'error');
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
