<?php

define('VIEW_QUERY', false);

define('MODE_READ', 4);
define('MODE_WRITE', 2);
define('MODE_EXECUTION', 1);

function xt () {
  
  if (func_num_args()) {
    
    $aArguments = func_get_args();
    $sValue = array_shift($aArguments);
    
    if (count($aArguments) && FORMAT_MESSAGES) return strtoxml(vsprintf(t($sValue), $aArguments));
    else return t($sValue);
  }
  
  return '';
}

function strtoxml ($sValue) {
  
  $oDocument = new XML_Document('<div xmlns="'.NS_XHTML.'">'.$sValue.'</div>');
  
  if ($oDocument->getRoot() && !$oDocument->getRoot()->isEmpty()) {
    
    return $oDocument->getRoot()->getChildren();
    
  } else {
    
    XML_Controler::addMessage(t('StrToXml : Transformation impossible'), 'warning');
    
    return null;
  }
}

class XML_Helper extends XML_Document {
  
  private $aBlocs = array();
  
  public function __construct($mChildren = '') {
    
    if ($mChildren === '') $mChildren = new HTML_Div('', array('class' => 'helper'));
    
    parent::__construct($mChildren);
  }
  
  public function loadAction($sPath, $oRedirect = null) {
    
    return new Action($sPath, $oRedirect);
  }
  
  public function addAction($sPath, $oRedirect = null) {
    
    $this->add($this->loadAction($sPath, $oRedirect));
  }
  
  public function setBloc($sKey, $mValue) {
    
    if ($sKey) $this->aBlocs[$sKey] = $mValue;
    return $mValue;
  }
  
  public function addBloc($sKey, $oTarget = null) {
    
    if ($oTarget && $oTarget instanceof XML_Element) return $oTarget->add($this->getBloc($sKey));
    else return $this->add($this->getBloc($sKey));
  }
  
  public function getBloc($sKey) {
    
    if (!array_key_exists($sKey, $this->aBlocs)) {
      
      $oBloc = new XML_Element($sKey);
      $this->aBlocs[$sKey] = $oBloc;
    }
    
    return $this->aBlocs[$sKey];
  }
  
  public function parse() {
    
    if (!$this->isEmpty()) return $this->getRoot();
    else return null;
  }
}

class XML_Document extends DOMDocument {
  
  private $iMode = null;
  private $sPath = null;
  
  public function __construct($mChildren = '', $iMode = MODE_READ) {
    
    parent::__construct('1.0', 'utf-8');
    
    $this->preserveWhiteSpace = false;
    // $this->substituteEntities = false;
    
    $this->registerNodeClass('DOMDocument', 'XML_Document');
    $this->registerNodeClass('DOMElement', 'XML_Element');
    $this->registerNodeClass('DOMText', 'XML_Text');
    $this->registerNodeClass('DOMAttr', 'XML_Attribute');
    $this->registerNodeClass('DOMCharacterData', 'XML_CData');
    $this->registerNodeClass('DOMDocumentFragment', 'XML_Fragment');
    
    $this->setMode($iMode);
    
    if ($mChildren) {
      
      // if Object else String
      if (is_object($mChildren)) $this->set($mChildren);
      else if (is_string($mChildren)) $this->startString($mChildren);
    }
  }
  
  private function setMode($iMode) {
    
    $this->iMode = in_array($iMode, array(MODE_EXECUTION, MODE_WRITE, MODE_READ)) ? $iMode : MODE_READ;
  }
  
  private function extractMode($oNode) {
    
    $iMode = 7;
    
    if (!($oNode->hasAttributeNS(NS_SECURITY, 'owner') && $oNode->hasAttributeNS(NS_SECURITY, 'mode') && $oNode->hasAttributeNS(NS_SECURITY, 'group'))) {
      
      XML_Controler::addMessage(xt('Sécurité : Élément sécurisé incomplet : %s', new HTML_Tag('em', $oNode->viewResume())), 'warning');
      
    } else {
      
      $sOwner = $oNode->getAttribute('owner', NS_SECURITY);
      $sMode = $oNode->getAttribute('mode', NS_SECURITY);
      $sGroup = $oNode->getAttribute('group', NS_SECURITY);
      
      $iResult = Controler::getUser()->getMode($sOwner, $sGroup, $sMode, $oNode);
      if ($iResult !== null) $iMode = $iResult;
    }
    
    return $iMode;
  }
  
  private function appendLoadRights() {
    
    $bSecured = false;
    
    if (!$this->isEmpty()) {
      
      if (Controler::getUser()) {
        
        $oNodes = $this->query('//*[@ls:owner or @ls:mode or @ls:group]', 'ls', NS_SECURITY);
        
        if ($oNodes->length) {
          
          $bSecured = true;
          
          if (XML_Controler::useStatut('report')) {
            
            $oPath = new HTML_Strong($this->sPath);
            
            if ($oNodes->length == 1) $oMessage = t('1 élément  sécurisé trouvé dans %s', $oPath);
            else $oMessage = xt('%i éléments  sécurisés trouvés dans %s', $oNodes->length, $oPath);
            
            XML_Controler::addMessage($oMessage, 'report');
          }
        }
        
        $this->removeLoadRights($oNodes);
      }
    }
    
    return $bSecured;
  }
  
  private function removeLoadRights($oNodes) {
    
    foreach ($oNodes as $oNode) {
      
      if ($oNode) {
        
        $bKeep = false;
        $iMode = $this->extractMode($oNode);
        
        if ($iMode) $bKeep = ($this->iMode & $iMode);
        if (!$bKeep) $oNode->remove();
      }
    }
  }
  
  private function appendSaveRights($oTargetDocument) {
    
  }
  
  public function startString($sString) {
    
    // if Path else XML String else new XML_Element
    if ($sString{0} == '/') $this->loadFile($sString);
    else if ($sString{0} == '<') $this->loadText($sString);
    else $this->set(new XML_Element($sString, '', null, '', $this));
  }
  
  public function createNode($sName, $oContent = '', $aAttributes = null) {
    
    return new XML_Element($sName, $oContent, $aAttributes, '', $this);
  }
  
  public function isEmpty() {
    
    return !$this->getRoot();
  }
  
  public function loadFile($sPath) {
    
    $this->sPath = $sPath;
    
    if (($oFile = Controler::getFile($sPath, true)) && $oFile->checkRights($this->iMode)) {
      
      if ($oFile->getDocument() === null) {
        
        parent::load(MAIN_DIRECTORY.$sPath);
        
        if ($this->isEmpty()) {
          
          XML_Controler::addMessage(xt('Document : Aucun contenu dans %s', new HTML_Strong($sPath)), 'warning');
          $oFile->setDocument(new XML_Document);
          
        } else {
          
          if (XML_Controler::useStatut('report')) XML_Controler::addMessage(xt('Document : Chargement du fichier %s', new HTML_Strong($sPath)), 'report');
          XML_Controler::addStat('load');
          
          $oFile->setDocument(new XML_Document($this->getRoot())); // getRoot avoid parsing of specials classes
          $oFile->isSecured($this->appendLoadRights());
        }
        
      } else if (!$oFile->getDocument()->isEmpty()) {
        
        $this->set($oFile->getDocument());
        if ($oFile->isSecured()) $this->appendLoadRights();
      }
    }
  }
  
  public function loadFreeFile($sPath) {
    
    parent::load(MAIN_DIRECTORY.$sPath);
    
    if ($this->isEmpty()) if (XML_Controler::useStatut('warning')) XML_Controler::addMessage(xt('Document (free) : Aucun contenu dans %s', new HTML_Strong($sPath)), 'warning');
    else {
      
      if (XML_Controler::useStatut('report')) XML_Controler::addMessage(xt('Document (free) : Chargement du fichier %s', new HTML_Strong($sPath)), 'report');
      XML_Controler::addStat('load');
    }
  }
  
  public function loadText($sContent) {
    
    if ($sContent) {
      
      parent::loadXML($sContent);
      if ($this->isEmpty()) XML_Controler::addMessage('Document : contenu invalide', 'warning');
      XML_Controler::addStat('read');
      
    } else XML_Controler::addMessage('Document : Aucun contenu. La chaîne est vide !', 'error');
    
    // $this->appendLoadRights(); TODO or not
  }
  
  public function save($sPath) {
    
    if ((!$oFile = Controler::getFile($sPath)) || $oFile->checkRights(MODE_WRITE)) {
      
      parent::save(MAIN_DIRECTORY.$sPath);
      return true;
      
    } else return false;
  }
  
  /**
   * Method loadFile() alias
   */
  public function load($sPath) {
    
    $this->loadFile($sPath);
  }
  
  /**
   * Method loadText() alias
   */
  public function loadXML() {
    
    return $this->loadText($sContent);
  }
  
  public function getChildren() {
    
    if ($this->getRoot()) return $this->getRoot()->getChildren();
    else return null;
  }
  
  public function getRoot() {
    
    if (isset($this->documentElement)) return $this->documentElement;
    else return null;
  }
  
  public function test($sPath) {
    
    return (bool) $this->get($sPath);
  }
  
  /**
   * Return a String from the result of the sQuery
   */
  
  public function read($sQuery = '', $sPrefix = '', $sUri = '') {
    
    if ($this->getRoot()) {
      
      if ($sQuery) return $this->getRoot()->read($sQuery, $sPrefix, $sUri);
      else return $this->getRoot()->getValue();
      
    } else return null;
  }
  
  /**
   * Return an XML_Element from the result of the sQuery
   */
  
  public function get($sQuery, $sPrefix = '', $sUri = '') {
    
    if ($this->getRoot()) return $this->getRoot()->get($sQuery, $sPrefix, $sUri);
    else return null;
  }
  
  public function set() {
    
    if (func_num_args() > 1) {
      
      $this->set(func_get_args());
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
          
      if (is_object($mValue)) {
        
        if ($mValue instanceof XML_Document && !method_exists($mValue, 'parse')) {
          
          /* XML_Document */
          
          if ($mValue->getRoot()) $this->setChild($mValue->getRoot());
          else XML_Controler::addMessage('Document->set() - Document vide', 'notice');
          
        } else if ($mValue instanceof XML_Element) {
          
          /* XML_Element */
          
          $this->setChild($mValue);
          
        } else {
          
          /* Undefined object (Action, XML_Action, others) */
          
          if (method_exists($mValue, 'parse')) {
            
            $mResult = $mValue->parse();
            
            if ($mResult != $mValue) return $this->set($mResult);
            else XML_Controler::addMessage(xt('L\'objet parsé de classe "%s" ne doit pas se retourner lui-même !', new HTML_Strong(get_class($mValue))), 'error');
            
          } else if ($this->getRoot()) $this->getRoot()->set($mValue);
        }
        
      } else if (is_array($mValue) && $mValue) {
        
        /* Array */
        
        if (count($mValue) > 1) {
          
          // > 1
          
          $aChildren = array();
          
          $this->set(array_shift($mValue));
          foreach ($mValue as $oChild) $aChildren = $this->add($oChild);
          
          $mValue = $aChildren;
          
          // = 1
          
        } else $mValue = $this->set(array_pop($mValue));
        
        // If String load as XML String
        
      } else if (is_string($mValue)) $this->startString($mValue);
      
      return $mValue;
      
    } else if ($this->getRoot()) $this->getRoot()->remove();
    
    return null;
  }
  
  public function addNode($sName, $oContent = '', $aAttributes = null) {
    
    if ($this->getRoot()) return $this->getRoot()->addNode($sName, $oContent, $aAttributes);
    else return $this->setChild($this->createNode($sName, $oContent, $aAttributes));
  }
  
  public function setChild($oChild) {
    
    if (!$this->isEmpty()) $this->getRoot()->remove();
    
    if ($oChild && is_object($oChild)) {
      
      if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this)) {
        
        $oChild = $this->importNode($oChild);
      }
      
      parent::appendChild($oChild);
      
    } else XML_Controler::addMessage('Element->setChild : No object', 'error');
    
    // $this->appendRights();
    
    return $oChild;
  }
  
  /**
   * Method add() alias
   */
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
  
  public function importNode($oChild, $bDepth = true) {
    
    if ($oChild) {
      
      if ($oChild instanceof HTML_Tag) {
        
        $oChild = clone $oChild;
        $oChild->parse();
      }
      
      return parent::importNode($oChild, $bDepth);
      
    } else XML_Controler::addMessage('Document->importNode : No object', 'error');
  }
  
  /**
   * Return a DOMNodeList from the result of the sQuery
   */
  
  public function query($sQuery, $sPrefix = '', $sUri = '') {
    
    if ($this->getRoot()) return $this->getRoot()->query($sQuery, $sPrefix, $sUri);
    else return new XML_NodeList;
  }
  
  /**
   * Extract the first result of a DOMNodeList if possible
   */
   
  public function queryArray($sQuery, $sPrefix = '', $sUri = '') {
    
    $aResult = array();
    $oResult = $this->query($sQuery, $sPrefix, $sUri);
    foreach ($oResult as $oStatut) $aResult[] = $oStatut->read();
    
    return $aResult;
  }
  
  public function queryOne($oCollection) {
    
    if ($oCollection && $oCollection->length) return $oCollection->item(0);
    else return null;
  }
  
  /**
   * Extract a string value from a mixed variable
   */
  
  public function queryString($mValue) {
    
    if (is_object($mValue)) {
      
      if (get_class($mValue) == 'DOMNodeList') $mValue = $mValue->item(0);
      if (get_class($mValue) == 'XML_Element' || get_class($mValue) == 'XML_Attribute') $mValue = $mValue->nodeValue;
    }
    
    return (string) $mValue;
  }
  
  public function parseXSL($oTemplate) {
    
    $oResult = null;
    
    if ($oTemplate && !$oTemplate->isEmpty()) {
      
      $oStyleSheet = new XSLTProcessor();
      $oStyleSheet->importStylesheet($oTemplate);
      // Transformation et affichage du résultat
      
      $oResult = new XML_Document();
      $oResult->loadText($oStyleSheet->transformToXML($this));
      XML_Controler::addStat('parse');
    }
    
    return $oResult;
  }
  
  public function view($bEscape = false) {
    
    $oView = new XML_Document($this);
    $oView->formatOutput();
    
    $oPre = new HTML_Tag('pre');
    
    if ($bEscape) $sResult = htmlspecialchars($oView);
    else $sResult = (string) $oView;
    
    $oPre->addText($sResult);
    
    return $oPre;
  }
  
  public function formatOutput() {
    
    if ($this->getRoot()) $this->getRoot()->formatOutput();
  }
  
  public function dsp($bHtml = false) {
    
    echo $this->view(true);
    /*
    $oView = new XML_Document($this);
    $oView->formatOutput();
    
    $oPre = new XML_Element('pre');
    $oPre->addText($oView);
    
    echo $oPre;*/
  }
  
  /**
   * Method __toString() alias
   */
  public function saveXML() {
    
    return $this->__toString();
  }
  
  public function __toString($bHtml = false) {
    
    $sResult = '';
    
    if (!$this->isEmpty()) {
      
      if ($bHtml) $sResult = parent::saveXML(null, LIBXML_NOEMPTYTAG); //
      else $sResult = parent::saveXML();
    }
    
    return $sResult;
  }
}

/**
 * XML_Element ..
 */
class XML_Element extends DOMElement {
  
  /**
   * @param string $sName Full name of the element (prefix + local name)
   * @param mixed $mContent Content of the element
   * @param array $aAttributes Associated array of attributes
   * @param string $sUri Associated namespace uri
   * @param XML_Document $oDocument Document owner of the element
   */
  public function __construct($sName = '', $mContent = '', $aAttributes = array(), $sUri = null, $oDocument = null) {
    
    $sName = trim((string) $sName);
    if (!$sName) $sName = 'default';
    parent::__construct($sName, null, $sUri);
    
    if (!$oDocument) $oDocument = new XML_Document();
    $oDocument->add($this);
    // $this->remove();
    $this->set($mContent);
    if ($aAttributes) $this->addAttributes($aAttributes);
  }
  
  /**
   * @return XML_Document the document of current element (alias of $ownerDocument property)
   */
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  /**
   * Create a DOMXPath object
   * @param string $sPrefix Prefix of the namespace used in the query
   * @param string $sUri Uri corresponding to the prefix precedly defined
   * @return DOMXPath An XPath associated with querie's prefix
   */
  private function buildXPath($sPrefix, $sUri) {
    
    $oXPath = new DOMXPath($this->getDocument());
    
    if ($sUri) $sResultUri = $sUri;
    else {
      
      if ($this->isDefaultNamespace($this->getNamespace())) {
        
        $sResultUri = $this->getNamespace();
        
        if ($sPrefix != '-') $sPrefix = 'ns';
        else $sPrefix = '';
      }
    }
    
    if ($sPrefix) {
      
      // Use Namespace
      
      if ($sPrefix != 'ns') $sResultUri = $this->lookupNamespaceURI($sPrefix);
      
      if ($sResultUri) $oXPath->registerNamespace($sPrefix, $sResultUri);
      else {
        
        // if (XML_Controler::useStatut('warning')) XML_Controler::addMessage(xt('Element : Aucun URI pour le préfix "%s" !', new HTML_Strong($sPrefix)), 'warning');
        // ////// LOOP CRASH TODO /////// //
        return null;
      }
    }
    
    return $oXPath;
  }
  
  /**
   * XPath Evaluation if {@link $sQuery} is not null else return {@link getValue()}
   * @param string $sQuery Query to execute
   * @param string $sPrefix Prefix of the namespace used in the query
   * @param string $sUri Uri corresponding to the prefix precedly defined
   * @return string Result of the XPath evaluation or {@link getValue()}
   */
  public function read($sQuery = '', $sPrefix = '', $sUri = '') {
    
    if ($sQuery) {
      
      $mResult = '';
      
      $oXPath = $this->buildXPath($sPrefix, $sUri);
      
      if ($oXPath) {
        
        $mResult = $oXPath->evaluate($sQuery, $this);
        $mResult = $this->getDocument()->queryString($mResult);
        
        XML_Controler::addStat('query');
        if (VIEW_QUERY) echo 'read : '.$sQuery.new HTML_Br;
        
        if ($mResult === null) {
          
          $mResult = '';
          if (XML_Controler::useStatut('report')) XML_Controler::addMessage(xt("Element->read(%s) : Aucun résultat", new HTML_Strong($sQuery)), 'report');
        }
        
      } else if (XML_Controler::useStatut('report')) XML_Controler::addMessage(xt("Element->read(%s) : Impossible de crée l'objet XPath", new HTML_Strong($sQuery)), 'report');
      
      return $mResult;
      
    } else if ($this->getValue()) return $this->getValue();
    else return '';
    // else return $this->getName();
  }
  
  /**
   * XPath Query
   * @param string $sQuery Query to execute
   * @param string $sPrefix Prefix of the namespace used in the query
   * @param string $sUri Uri corresponding to the prefix precedly defined
   * @return XML_NodeList Result of the XPath query
   */
  public function query($sQuery, $sPrefix = '', $sUri = '') {
    
    if (is_string($sQuery) && $sQuery) {
      
      $oXPath = $this->buildXPath($sPrefix, $sUri);
      
      if ($oXPath) {
        
        $mResult = $oXPath->query($sQuery, $this);
        
        XML_Controler::addStat('query');
        if (VIEW_QUERY) echo 'query : '.$sQuery.new HTML_Br;
        
        // if (!$mResult || !$mResult->length) XML_Controler::addMessage(xt("Element->query(%s) : Aucun résultat", new HTML_Strong($sQuery)), 'report');
        // ////// report & notice type will crash system, maybe something TODO /////// //
        return new XML_NodeList($mResult);
        
      } else if (XML_Controler::useStatut('report')) XML_Controler::addMessage(xt("Element->query(%s) : Impossible de crée l'objet XPath", new HTML_Strong($sQuery)), 'report');
      
    } else {
      
      // if ($this->isEmpty()) XML_Controler::addMessage(xt('Element->query(%s) : Requête impossible, élément vide !', new HTML_Strong($sQuery)), 'warning');
      if (XML_Controler::useStatut('warning')) XML_Controler::addMessage('Element : Requête vide ou invalide !', 'warning');
    }
    
    return new XML_NodeList;
  }
  
  /**
   * XPath Query
   * @param string $sQuery Query to execute
   * @param string $sPrefix Prefix of the namespace used in the query
   * @param string $sUri Uri corresponding to the prefix precedly defined
   * @return XML_Element The first element resulting from the XPath query
   */
  public function get($sQuery, $sPrefix = '', $sUri = '') {
    
    return $this->getDocument()->queryOne($this->query($sQuery, $sPrefix, $sUri));
  }
  
  /**
   * Add an attribute object to the element
   * @param XML_Attribute $oAttribute Attribute to add to the element, may be owned by the document owner
   * @return XML_Attribute The attribute passed in argument (?? normally)
   */
  public function setAttributeNode($oAttribute) {
    
    // TODO : RIGHTS
    return parent::setAttributeNode($oAttribute);
  }
  
  /**
   * Evaluate if the attribute has a boolean value (true or false or TRUE or FALSE)
   * @param string $sAttribute Query to execute
   * @return boolean|null The value of the attribute, or null if it's not a boolean value
   */
  public function testAttribute($sAttribute) {
    
    $sValue = strtolower($this->getAttribute($sAttribute));
    
    if ($sValue == 'true') return true;
    else if ($sValue == 'false') return false;
    else return null;
  }
  
  /**
   * Set an attribute of the element, remove the attribute if $sValue is null
   * @param string $sName The name of the attribute
   * @param string $sValue The value of the attribute
   */
  public function setAttribute($sName, $sValue = '') {
    
    // TODO : RIGHTS
    if ($sValue !== '' && $sValue !== null) return parent::setAttribute($sName, $sValue);
    else return $this->removeAttribute($sName);
  }
  
  /**
   * Import then add with {@link setAttributeNode()} an attribute object to the element
   * @param XML_Attribute $oAttribute Attribute to add to the element
   * @return XML_Attribute The attribute added to the element
   */
  public function addAttribute($oAttribute) {
    
    if ($oAttribute->getDocument() && $oAttribute->getDocument() != $this->getDocument())
      $oAttribute = $this->getDocument()->importNode($oAttribute, false);
    
    $this->setAttributeNode($oAttribute);
    
    return $oAttribute;
  }
  
  /**
   * Remove all attributes then add the new ones via {@link addAttributes}
   * @param array $aAttributes The associative array containing the name of the attribute in the key and the value in the array values
   * @return array The associative array passed in argument
   */
  public function setAttributes($aAttributes) {
    
    $this->cleanAttributes();
    return $this->addAttributes($aAttributes);
  }
  
  /**
   * Set an associative array of attributes via {@link setAttribute()}
   * @param array $aAttributes The associative array containing the name of the attribute in the key and the value in the array values
   * @return array The associative array passed in argument
   */
  public function addAttributes($aAttributes) {
    
    foreach ($aAttributes as $sKey => $sValue) $this->setAttribute($sKey, $sValue);
    return $aAttributes;
  }
  
  /**
   * Return the value of the attribute named $sName
   * @param array $sName The name of the attribute
   * @param array $sUri The associative namespace uri of the attribute
   * @return string The value of the attribute
   */
  public function getAttribute($sName, $sUri = '') {
    
    if ($sUri) return parent::getAttributeNS($sUri, $sName);
    else return parent::getAttribute($sName);
  }
  
  /**
   * Remove the children then add the mixed values given in argument with {@link add()}
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string Value(s) to replace actual content
   * @return XML_Element|XML_Text|XML_Attribute The value(s) given in argument
   */
  public function set() {
    
    if (func_num_args() > 1) {
      
      $this->set(func_get_arg(0));
      
      // If this is the root, then we add the others in it
      if ($this->isRoot()) $oParent = $this->parentNode;
      else $oParent = $this;
      
      for ($i = 1; $i < func_num_args(); $i++) $oParent->add(func_get_arg($i));
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
      
      if (is_object($mValue)) {
        
        $this->cleanChildren();
        if (($mValue instanceof XML_Document)) {
          
          // XML_Document
          
          $this->add($mValue);
          
        } else if (($mValue instanceof XML_Element) || ($mValue instanceof XML_Text)) {
          
          // XML_Element, XML_Text
          
          $mValue = $this->insertChild($mValue);
          
        } else if ($mValue instanceof XML_Attribute) {
          
          // XML_Attribute
          
          return $this->setAttributeNode($mValue);
          
        } else if ($mValue instanceof XML_NodeList) {
          
          // XML_NodeList
          
          return $this->add($mValue);
          
        } else return $this->addText($mValue); // forced string
        
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
  
  /**
   * Remove all the children of the element
   */
  public function cleanChildren() {
    
    if ($this->hasChildren()) $this->getChildren()->remove();
  }
  
  /**
   * Remove all the attributes of the element
   */
  public function cleanAttributes() {
    
    foreach ($this->attributes as $oAttribute) $this->removeAttributeNode($oAttribute);
  }
  
  /**
   * Add the mixed values given in argument with {@link insert()} at the end of the children's list
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string Value(s) to add to actual content
   * @return XML_Element|XML_Text|XML_Attribute The last object added to content
   */
  public function add() {
    
    return $this->insert(func_get_args());
  }
  
  /**
   * Add the mixed values given in argument with {@link insert()} at the top of the children's list
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string Value(s) to add to actual content
   * @return XML_Element|XML_Text|XML_Attribute The last object added to content
   */
  public function shift() {
    
    if (!$this->isEmpty()) return $this->insert(func_get_args(), $this->firstChild);
    else return $this->insert(func_get_args());
  }
  
  /**
   * Add the mixed values given in argument with {@link insert()} before the current element
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string Value(s) to add to actual content
   * @return XML_Element|XML_Text|XML_Attribute The last object added to content
   */
  public function insertBefore() {
    
    if (!$this->isRoot() && $this->getParent()) $this->getParent()->insert(func_get_args(), $this);
    else if (XML_Controler::useStatut('error')) XML_Controler::addMessage(t('Element : Impossible d\'insérer un noeud ici (root)'), 'error');
  }
  
  /**
   * Add the mixed values given in argument with {@link insert()} after the current element
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string Value(s) to add to actual content
   * @return XML_Element|XML_Text|XML_Attribute The last object added to content
   */
  public function insertAfter() {
    
    if ($this->nextSibling) { $this->nextSibling->insertBefore(func_get_args()); echo 'ok'; }
    else if ($this->parentNode) { echo 'pas ok'; $this->parentNode->add(func_get_args()); }
  }
  
  /**
   * Insert the value given in argument before the $oNext element, if null insert at the end of the children's list
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string $mValue The value to add to actual content
   * @param XML_Element $oNext The element that will follow the value
   * @return XML_Element|XML_Text|XML_Attribute The object added to content
   */
  public function insert($mValue, $oNext = null) {
    
    if (is_object($mValue)) {
      
      if ($mValue instanceof XML_Element || $mValue instanceof XML_Text) {
        
        /* XML_Element or XML_Text */
        
        $mValue = $this->insertChild($mValue, $oNext);
        
      } else if ($mValue instanceof XML_Attribute) {
        
        /* XML_Attribute */
        
        $mValue = $this->addAttribute($mValue);
        
      } else if ($mValue instanceof XML_NodeList) {
        
        /* XML_NodeList */
        
        foreach ($mValue as $oChild) $this->insert($oChild, $oNext);
        
      } else if ($mValue instanceof XML_Document && !method_exists($mValue, 'parse')) {
        
        /* XML_Document */
        
        // TODO : add XMLNS ?!
        
        if ($mValue->getRoot()) $mValue = $this->insertChild($mValue->getRoot(), $oNext);
        else $mValue = null;
        
      } else {
        
        /* Undefined object (Action, XML_Action, others) */
        
        if (method_exists($mValue, 'parse')) {
          
          $mResult = $mValue->parse();
          
          if ($mResult != $mValue) return $this->insert($mResult, $oNext);
          else XML_Controler::addMessage(xt('L\'objet parsé de classe "%s" ne doit pas se retourner lui-même !', new HTML_Strong(get_class($mValue))), 'error');
          
        } else $mValue = $this->insertText($mValue, $oNext); // Forced string
      }
      
      /* Array */
      
    } else if (is_array($mValue)) {
      
      if ($mValue) foreach ($mValue as $mSubValue) $mValue = $this->insert($mSubValue, $oNext);
      
    } else if ($mValue !== null) {
      
      /* String, Integer, Float, Bool, Resource, ... ? */
      
      $mValue = $this->insertText($mValue, $oNext); // Forced string
    }
    
    return $mValue;
  }
  
  /**
   * Insert the string variable result given in argument before the $oNext element, if null insert at the end of the children's list
   * @param mixed $sValue The value to add to actual content, will be transform to text
   * @param XML_Element $oNext The element that will follow the value
   * @return XML_Text The text element added to content
   */
  public function insertText($sValue, $oNext = null) {
    
    if ($sValue || $sValue === 0) return $this->insertChild(new XML_Text($sValue), $oNext);
    else return $sValue;
  }
  
  /**
   * Insert the element given in argument before the $oNext element, if null insert at the end of the children's list
   * @param XML_Element $oChild The element to add to actual content
   * @param XML_Element $oNext The element that will follow the value
   * @return XML_Element The element added to content
   */
  public function insertChild($oChild, $oNext = null) {
    
    if ($oChild === $oNext) $oNext = null;
    
    if (is_object($oChild) && ($oChild instanceof XML_Element || $oChild instanceof XML_Text)) {
      
      if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this->getDocument())) {
        
        $oChild = $this->getDocument()->importNode($oChild);
      }
      
      // TODO : RIGHTS
      if ($oNext) parent::insertBefore($oChild, $oNext);
      else parent::appendChild($oChild);
      
      return $oChild;
      
    } return null;
  }
  
  /**
   * Replace the actual element with the one given in argument
   * @param XML_Element $oChild The element wish will replace the actual one
   * @return XML_Element The element added to content
   */
  public function replace($oChild) {
    
    if ($oChild != $this) {
      
      $this->insertBefore($oChild);
      $this->remove();
      
    } //else if (XML_Controler::useStatut('notice')) XML_Controler::addMessage(xt('replace() : Impossible de remplacer un élément par lui-même : %s', $this->viewResume()), 'notice');
    
    return $oChild;
  }
  
  /**
   * Remove the actual element
   * @return mixed Don't know what :( TODO
   */
  public function remove() {
    
    if ($this->parentNode) return $this->parentNode->removeChild($this);
    // else if ($this->getDocument()->getRoot() == $this) return $this->getDocument()->removeChild($this);
  }
  
  /**
   * Return the list of children of the current element with {@link $childNodes}
   * @return XML_NodeList The children :)
   */
  public function getChildren() {
    
    return new XML_NodeList($this->childNodes);
  }
  
  /**
   * Return the number of children of the current element
   * @return integer The children's count
   */
  public function countChildren() {
    
    return $this->childNodes->length;
  }
  
  /**
   * Test wether actual element has children or not
   * @return boolean The children actual existenz fact (or not)
   */
  public function hasChildren() {
    
    return $this->hasChildNodes();
  }
  
  /**
   * Alias function {@link add()}
   */
  public function appendChild() {
  
    $this->add(func_get_args());
  }
  
  /**
   * Create with {@link XML_Document::createNode()} an element then insert with {@link insertChild()} it to the end of the children's list
   * Faster than creating an element with "new"
   * @param string $sName Full name of the element (prefix + local name)
   * @param mixed $mContent Content of the element
   * @param array $aAttributes Associated array of attributes
   * @return XML_Element The element added to content
   */
  public function addNode($sName, $oContent = '', $aAttributes = null) {
    
    // Node : Automatically created Element based on strings and arrays
    
    return $this->insertChild($this->getDocument()->createNode($sName, $oContent, $aAttributes));
  }
  
  /**
   * Create with {@link XML_Document::createNode()} an element then insert with {@link insertChild()} before the $oNext element, if null insert to the end of the children's list
   * Faster than creating an element with "new"
   * @param string $sName Full name of the element (prefix + local name)
   * @param mixed $mContent Content of the element
   * @param array $aAttributes Associated array of attributes
   * @param XML_Element $oNext The element that will follow the value
   * @return XML_Element The element added to content
   */
  public function insertNode($sName, $oContent = '', $aAttributes = null, $oNext = null) {
    
    return $this->insertChild($this->getDocument()->createNode($sName, $oContent, $aAttributes), $oNext);
  }
  
  /*** Array ***/
  
  public function addArray($aChildren, $sName = '') {
    
    $aResult = array();
    
    foreach ($aChildren as $sKey => $sValue) {
      
      if ($sName) $aResult[] = $this->addNode($sName, $sValue, array('key' => $sKey));
      else if (!is_numeric($sKey)) $aResult[] = $this->addNode($sKey, $sValue);
      else $aResult[] = $this->addNode($sValue);
    }
    
    return $aResult;
  }
  
  /*** Tests ***/
  
  public function isFirst() {
    
    return ($this->isRoot() || $this->getParent()->getFirst() === $this);
  }
  
  public function isRoot() {
    
    return (!$this->getParent() || ($this->getParent() === $this->getDocument())); // TODO tocheck
  }
  
  public function isEmpty() {
    
    return !$this->hasChildren();
  }
  
  public function isText() {
    
    return false;
  }
  
  public function isElement() {
    
    return true;
  }
  
  /*** Properties ***/
  
  public function getParent() {
    
    return $this->parentNode;
  }
  
  public function getFirst() {
    
    return $this->firstChild;
  }
  
  public function getNamespace() {
    
    return $this->namespaceURI;
  }
  
  public function useNamespace($sNamespace = '') {
    
    if ($sNamespace) return ($this->getNamespace() == $sNamespace);
    else return ($this->getNamespace());
  }
  
  public function getPrefix() {
    
    return $this->prefix;
  }
  
  public function getName($bLocal = false) {
    
    if ($bLocal) return $this->localName;
    else return $this->nodeName;
  }
  
  /*** Text ***/
  
  public function getValue() {
    
    return $this->textContent;
  }
  
  public function addText($sValue) {
    
    return $this->insertText($sValue);
  }
  
  public function implode($sSep, $cChildren) {
    
    $sContent = '';
    
    foreach ($cChildren as $iIndex => $oChild) {
      
      $sContent .= (string) $oChild;
      if ($iIndex != $cChildren->length - 1) $sContent .= $sSep;
    }
    
    return $sContent;
  }
  
  /*** Render ***/
  
  public function formatOutput($iLevel = 0) {
    
    if (!$this->isRoot()) {
      
      $this->insertBefore("\n".str_repeat('  ', $iLevel));
    }
    
    foreach ($this->getChildren() as $oChild) $oChild->formatOutput($iLevel + 1);
    if ($this->hasChildren()) {
      
      if ($this->countChildren() > 1 || strlen($this->getFirst()) > 80) $this->add("\n".str_repeat('  ', $iLevel));
    }
  }
  
  public function viewResume($iLimit = 100, $bDecode = false) {
    
    $sView = stringResume(htmlspecialchars($this->view()), $iLimit);
    $iLastSQuote = strrpos($sView, '&');
    $iLastEQuote = strrpos($sView, ';');
    
    if ($iLastSQuote && $iLastEQuote < $iLastSQuote) $sView = substr($sView, 0, $iLastSQuote).'...';
    if ($bDecode) return htmlspecialchars_decode($sView);
    else return $sView;
  }
  
  public function view($bIndent = false, $bFormat = false) {
    
    $oResult = clone $this;
    
    if ($bIndent) {
      
      $oResult->formatOutput();
      $oResult = new HTML_Tag('pre', wordwrap($oResult, 120));
    }
    
    if ($bFormat) return htmlspecialchars($oResult);
    return $oResult;
  }
  
  public function dsp($bHtml = false) {
    
    $oResult = clone $this;
    $oResult->formatOutput();
    
    echo new HTML_Tag('pre', htmlspecialchars($oResult));
  }
  
  public function __toString() {
    
    // try {
      
      if (isset($this->nodeName) && $this->nodeName) {
        
        // if (!$this->isReady()) return '';
        
        if ($this->childNodes && $this->childNodes->length) $sChildren = $this->implode('', $this->childNodes);
        else $sChildren = '';
        
        if ($this->attributes && $this->attributes->length) $sAttributes = ' '.$this->implode(' ', $this->attributes);
        else $sAttributes = '';
        
        $sResult = '<'.$this->nodeName.$sAttributes;
        
        if ($sChildren) $sResult .= '>'.$sChildren.'</'.$this->nodeName.'>';
        else $sResult .= ' />';
        
        return $sResult;
        
      } else {
        
        XML_Controler::addMessage(t('Elément vide :('), 'warning'); 
        return '';
      }
		// } catch ( Exception $e ) {
      
			// XML_Controler::addMessage('Element : '.$e->getMessage(), 'error');
		// }
  }
}

class XML_Attribute extends DOMAttr {
  
  public function __construct($sName, $sValue) {
    
    parent::__construct($sName, $sValue);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function remove() {
    
    $this->ownerElement->removeAttributeNode($this);
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
      else {
        
        XML_Controler::addMessage(xt('Object " %s " cannot be converted to string !', new HTML_Strong(get_class($mContent))), 'error');
        $mContent = '';
      }
    }
    
    // if (!(is_string($mContent) || is_numeric($mContent))) $mContent = '';
    parent::__construct($mContent);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function replace($oChild) {
    
    $this->insertBefore($oChild);
    $this->remove();
    return $oChild;
  }
  
  public function remove() {
    
    return $this->parentNode->removeChild($this);
  }
  
  public function formatOutput($iLevel = 0) {
    
    return null;
  }
  
  public function isText() {
    
    return true;
  }
  
  public function isElement() {
    
    return false;
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
  
  public function __construct($oNodeList = null) {
    
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
  
  public function __toString() {
    
    return implode(' ', $this->aNodes);
  }
}

class XML_Fragment extends DOMDocumentFragment { }

class XSL_Document extends XML_Element {
  
  public function __construct() {
    
    $this->insertChild(new XML_Element('output', array('method' => 'xml', 'encoding' => 'utf-8'), true, 'xsl'));
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
