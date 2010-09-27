<?php

/**
 * XML_Element ..
 */
class XML_Element extends DOMElement implements XML_Composante {
  
  /**
   * @param string $sName Full name of the element (prefix + local name)
   * @param mixed $mContent Content of the element
   * @param array $aAttributes Associated array of attributes
   * @param string $sUri Associated namespace uri
   * @param XML_Document $oDocument Document owner of the element
   */
  public function __construct($sName = 'default', $mContent = '', $aAttributes = array(), $sUri = null, $oDocument = null) {
    
    $sName = trim((string) $sName);
    if (!$sName) $sName = 'default';
    
    parent::__construct($sName, null, $sUri);
    
    if (!$oDocument) $oDocument = new XML_Document();
    
    $oDocument->add($this);
    $this->set($mContent);
    
    if ($aAttributes) $this->addAttributes($aAttributes);
  }
  
  /**
   * @return XML_Document the document of current element (alias of $ownerDocument property)
   */
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function isReal() {
    
    if (isset($this->localName)) return true;
    else return false;
  }
  
  /**
   * @return string The CSS path of the element relative to his parent and brotherhood. ex: 'div > a:eq(2)'
   */
  public function getCSSPath($oLastParent = null, $sNamespace = null) {
    
    if ($sNamespace === null) $sNamespace = $this->getNamespace();
    
    if ($this->useNamespace($sNamespace)) {
      
      $iPrevious = 1;
      $oSibling = $this;
      
      while ($oSibling = $oSibling->getPrevious()) {
        
        if (!$oSibling->useNamespace($sNamespace)) {
          
          $iPrevious += $oSibling->getChildren($sNamespace, null)->length;
          
        } else if ($oSibling->isElement()) $iPrevious++;
      }
      
      $sPath = '*:nth-child('.$iPrevious.')';
      
      if ($this->getParent() instanceof XML_Element) {
        
        if ($this->getParent() !== $oLastParent) $sPath = $this->getParent()->getCSSPath($oLastParent, $sNamespace).' > '.$sPath;
        
      } else dspm(xt('Impossible de déterminer le chemin CSS de %s depuis %s', view($this), view($oLastParent)), 'xml/error');
      
    } else {
      
      $sPath = $this->getParent()->getCSSPath($oLastParent, $sNamespace);
    }
    
    return $sPath;
  }
  
  /**
   * Create a DOMXPath object
   * @return DOMXPath An XPath associated with querie's namespaces
   */
  private function buildXPath($mValues, $sUri) {
    
    $oXPath = new DOMXPath($this->getDocument());
    
    if (!is_array($mValues)) $this->oldBuildXPath($oXPath, $mValues, $sUri);
    else foreach ($mValues as $sPrefix => $sNamespace) $oXPath->registerNamespace($sPrefix, $sNamespace);
    
    return $oXPath;
  }
  
  /**
   * Create a DOMXPath object
   * @param string $sPrefix Prefix of the namespace used in the query
   * @param string $sUri Uri corresponding to the prefix precedly defined
   * @return DOMXPath An XPath associated with querie's prefix
   */
  private function oldBuildXPath(&$oXPath, $sPrefix, $sUri) {
    
    $sResultUri = '';
    
    if ($sUri && $sPrefix) $sResultUri = $sUri;
    else if ($this->useDefaultNamespace()) {
      
      $sResultUri = $this->getNamespace();
      
      if ($sPrefix != '-') $sPrefix = 'ns';
      else $sPrefix = '';
    }
    
    if ($sPrefix) {
      
      // Use Namespace
      
      if (!$sResultUri) $sResultUri = $this->lookupNamespaceURI($sPrefix);
      
      if ($sResultUri) $oXPath->registerNamespace($sPrefix, $sResultUri);
      else {
        
        // if (Controler::useStatut('warning')) Controler::addMessage(xt('Element : Aucun URI pour le préfix "%s" !', new HTML_Strong($sPrefix)), 'xml/warning');
        // ////// LOOP CRASH TODO /////// //
        return null;
      }
    }
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
        if (SYLMA_ACTION_STATS && Controler::getUser()->isMember(SYLMA_AUTHENTICATED)) Controler::infosSetQuery($sQuery);
        
        if ($mResult === null) {
          
          $mResult = '';
          if (Controler::useStatut('xml/report')) Controler::addMessage(xt("Element->read(%s) : Aucun résultat", new HTML_Strong($sQuery)), 'xml/report');
        }
        
      } else if (Controler::useStatut('xml/report')) Controler::addMessage(xt("Element->read(%s) : Impossible de crée l'objet XPath", new HTML_Strong($sQuery)), 'xml/report');
      
      return $mResult;
      
    } else return $this->getValue();
    
    // else return $this->getName();
  }
  
  /**
   * XPath Query
   * @param string $sQuery Query to execute
   * @param string $sPrefix Prefix of the namespace used in the query
   * @param string $sUri Uri corresponding to the prefix precedly defined
   * @return XML_NodeList Result of the XPath query
   */
  public function query($sQuery, $mValue = '', $sUri = '') {
    
    if (is_string($sQuery) && $sQuery) {
      
      $oXPath = $this->buildXPath($mValue, $sUri);
      
      if ($oXPath) {
        
        $mResult = $oXPath->query($sQuery, $this);
        
        XML_Controler::addStat('query');
        if (SYLMA_ACTION_STATS && Controler::getUser()->isMember(SYLMA_AUTHENTICATED)) Controler::infosSetQuery($sQuery);
        
        // if (!$mResult || !$mResult->length) Controler::addMessage(xt("Element->query(%s) : Aucun résultat", new HTML_Strong($sQuery)), 'xml/report');
        // ////// report & notice type will crash system, maybe something TODO /////// //
        return new XML_NodeList($mResult);
        
      } else if (Controler::useStatut('xml/report')) Controler::addMessage(xt("Element->query(%s) : Impossible de crée l'objet XPath", new HTML_Strong($sQuery)), 'xml/report');
      
    } else {
      
      // if ($this->isEmpty()) Controler::addMessage(xt('Element->query(%s) : Requête impossible, élément vide !', new HTML_Strong($sQuery)), 'xml/warning');
      if (Controler::useStatut('warning')) Controler::addMessage('Element : Requête vide ou invalide !', 'xml/warning');
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
  
  public function readByName($sName, $sUri = null) {
    
    if ($oResult = $this->getByName($sName, $sUri)) return $oResult->read();
    else return '';
  }
  
  public function getByName($sName, $sUri = null) {
    
    if ($sUri) $aResults = $this->getElementsByTagNameNS($sUri, $sName);
    else $aResults = $this->getElementsByTagName($sName);
    
    if ($aResults->length) return $aResults->item(0);
    else return null;
  }
  
  public function getById($sId) {
    
    return $this->getDocument()->getElementById($sId);
  }
  /**
   * Add an attribute object to the element
   * @param XML_Attribute $oAttribute Attribute to add to the element, may be owned by the document owner
   * @return XML_Attribute The attribute passed in argument (?? normally)
   */
  public function setAttributeNode($oAttribute) {
    
    // TODO : RIGHTS
    
    if (!($oAttribute->getDocument() === $this->getDocument())) $oAttribute = $this->getDocument()->importNode($oAttribute);
    
    return parent::setAttributeNode($oAttribute);
  }
  
  public function hasAttributes() {
    
    if (func_get_args()) {
      
      foreach (func_get_args() as $sArg) if (!$this->getAttribute($sArg)) return false;
      
    } else return ($this->attributes->length);
    
    return true;
  }
  
  /**
   * Evaluate the attribute as a boolean value (true or false or TRUE or FALSE) or compare as a string
   * @param string $sAttribute Attribute name to get
   * @param boolean|null|string $mDefault Value to compare or return by default
   * @return boolean|null The value of the attribute, or $mDefault if it's not a boolean value
   */
  public function testAttribute($sAttribute, $mDefault = null, $sNamespace = '') {
    
    if (is_string($mDefault)) return ($this->getAttribute($sAttribute, $sNamespace) == $mDefault);
    return strtobool(strtolower($this->getAttribute($sAttribute, $sNamespace)), $mDefault);
  }
  
  /**
   * Set an attribute of the element, remove the attribute if $sValue is null
   * @param string $sName The name of the attribute
   * @param string $sValue The value of the attribute
   */
  public function setAttribute($sName, $sValue = '', $sUri = null) {
    
    // TODO : RIGHTS
    
    if ($sValue !== '' && $sValue !== null) {
      
      if (!$sUri) return parent::setAttribute($sName, checkEncoding($sValue));
      else return parent::setAttributeNS($sUri, $sName, checkEncoding($sValue));
      
    } else return $this->removeAttribute($sName);
  }
  
  public function addClass($sClass) {
    
    if ($sActualClass = $this->getAttribute('class')) $aClasses = explode(' ', $sActualClass);
    else $aClasses = array();
    
    if (!in_array($sClass, $aClasses)) $aClasses[] = $sClass;
    
    $sClasses = $aClasses ? implode(' ', $aClasses) : '';
    
    $this->setAttribute('class', $sClasses);
  }
  
  /**
   * Import then add with {@link setAttributeNode()} an attribute object to the element
   * @param XML_Attribute $oAttribute Attribute to add to the element
   * @return XML_Attribute The attribute added to the element
   */
  public function addAttribute($oAttribute) {
    
    //$oAttribute = clone $oAttribute;
    
    if ($oAttribute->getDocument() && $oAttribute->getDocument() != $this->getDocument()) {
      
      $oAttribute = $this->getDocument()->importNode($oAttribute, false);
    }
    
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
  public function addAttributes($mAttributes) {
    
    if (is_array($mAttributes)) foreach ($mAttributes as $sKey => $sValue) $this->setAttribute($sKey, $sValue);
    else if (is_object($mAttributes)) foreach ($mAttributes as $oAttribute) $this->setAttribute($oAttribute->getName(), $oAttribute->getValue());
    return $mAttributes;
  }
  
  public function getAttributes() {
    
    return new XML_NodeList($this->attributes);
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
  
  public function getId() {
    
    return $this->getAttribute('id');
  }
  /**
   * Remove the children then add the mixed values given in argument with {@link add()}
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string Value(s) to replace actual content
   * @return XML_Element|XML_Text|XML_Attribute The value(s) given in argument
   */
  public function set() {
    
    if (func_num_args() > 1) {
      
      $this->set(func_get_arg(0));
      
      for ($i = 1; $i < func_num_args(); $i++) $this->add(func_get_arg($i));
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
      
      $this->cleanChildren();
      return $this->add($mValue);
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
  
  // TODO : Priority
  public function cloneAttributes($oElement, $mAttribute = null) {
    
    if ($mAttribute) {
      
      if (is_array($mAttribute)) {
        
        foreach ($mAttribute as $sAttribute)
          if ($oElement->hasAttribute($sAttribute)) $this->cloneAttributes($oElement, $sAttribute);
        
      } else $this->setAttribute($mAttribute, $oElement->getAttribute($mAttribute));
      
    } else {
      
      foreach ($oElement->getAttributes() as $oAttribute)
        $this->setAttribute($oAttribute->getName(), $oAttribute->getValue());
    }
  }
  
  public function merge($oElement, $bSelfPrior = false) {
    
    $oResult = new XML_Document($this);
    
    if ($oFirst = $this->getFirst()) // TODO : fucking first child namespace BUG
      $oResult->getFirst()->replace(new XML_Element($oFirst->getName(), $oFirst->getChildren(), $oFirst->getAttributes(), $oFirst->getNamespace()));
    
    foreach ($oElement->getChildren() as $oChild) {
      
      if ($oSame = $oResult->getByName($oChild->getName())) {
        
        if (!$bSelfPrior) $oSame->replace($oChild);
        
      } else $oResult->add($oChild);
    }
    
    //$oResult->cloneAttributes($oElement);
    
    return $oResult->getRoot();
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
    
    $mResult = null;
    
    if (!$this->isRoot() && $this->getParent()) $mResult = $this->getParent()->insert(func_get_args(), $this);
    else dspm(array(t('Element : Impossible d\'insérer un noeud avant le noeud racine'), $this->messageParse()),
 'xml/error');
    
    return $mResult;
  }
  
  /**
   * Add the mixed values given in argument with {@link insert()} after the current element
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string Value(s) to add to actual content
   * @return XML_Element|XML_Text|XML_Attribute The last object added to content
   */
  public function insertAfter() {
    
    if ($this->nextSibling) $this->nextSibling->insertBefore(func_get_args());
    else if ($this->parentNode) $this->parentNode->add(func_get_args());
  }
  
  /**
   * Insert the value given in argument before the $oNext element, if null insert at the end of the children's list
   * @param XML_Document|XML_Element|XML_Attribute|XML_Text|XML_NodeList|string $mValue The value to add to actual content
   * @param XML_Element $oNext The element that will follow the value
   * @return XML_Element|XML_Text|XML_Attribute The object added to content
   */
  public function insert($mValue, $oNext = null) {
    
    if (is_object($mValue)) {
      
      if ($mValue instanceof XML_Element || $mValue instanceof XML_Text || $mValue instanceof XML_CData || $mValue instanceof XML_Comment) {
        
        /* XML Base nodes */
        
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
        
        // Forced parsing !!
        
        if (method_exists($mValue, 'parse')) {
          
          $mResult = $mValue->parse();
          
          if ($mResult != $mValue) return $this->insert($mResult, $oNext);
          else Controler::addMessage(xt('L\'objet parsé de classe "%s" ne doit pas se retourner lui-même !', new HTML_Strong(get_class($mValue))), 'xml/error');
          
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
    
    if ($sValue || (string) $sValue === '0') return $this->insertChild(new XML_Text($sValue), $oNext);
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
    
    if (is_object($oChild) && ($oChild instanceof XML_Element || $oChild instanceof XML_Text || $oChild instanceof XML_CData || $oChild instanceof XML_Comment)) {
      
      if ($oChild->getDocument() && ($oChild->getDocument() !== $this->getDocument())) {
        
        $oChild = $this->getDocument()->importNode($oChild, 1);
        
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
  public function replace($mContent) {
    
    $oResult = null;
    
    if ($mContent !== $this) {
      
      if ($this->isRoot()) {
        
        if ($mContent instanceof XML_NodeList) {
          
          if ($mContent->length > 1) Controler::addMessage(array(t('L\'élément parent ne peut être remplacé que par un unique enfant !'), $this->messageParse()), 'xml/error');
          else $mContent = $mContent->item(0);
        }
        
        if (!($mContent instanceof XML_Element)) Controler::addMessage(array(t('L\'élément parent ne peut être remplacé que par un objet XML_Element !'), $this->messageParse()), 'xml/error');
        else {
          // TODO : strange things
          if ($mContent->isDefaultNamespace($mContent->getNamespace())) {
            //$oResult = $this->getDocument()->set($mContent); return $oResult;
            
            $oRoot = new XML_Element($mContent->getName(false), null, null, $mContent->getNamespace());
            $oRoot->cloneAttributes($mContent);
            
            $oRoot->add($mContent->getChildren());
            
            $oResult = $this->getDocument()->set($oRoot);
            
          } else $this->getDocument()->set($mContent);
        }
        
      } else {
        
        $oResult = $this->insertBefore($mContent);
        $this->remove();
      }
    }
    
    return $oResult;
  }
  
  /**
   * Remove the actual element
   * @return mixed Don't know what :( TODO
   */
  public function remove() {
    
    if ($this->parentNode) return $this->parentNode->removeChild($this);
    else return null;
    // else if ($this->getDocument()->getRoot() == $this) return $this->getDocument()->removeChild($this);
  }
  
  public function move($oElement) {
    
    $oResult = $oElement->add($this);
    $this->remove();
    
    return $oResult;
  }
  
  /**
   * Return the list of children of the current element with {@link $childNodes}
   * @return XML_NodeList The children :)
   */
  public function getChildren($sNamespace = null, $mDepth = null) {
    
    if ($sNamespace) {
      
      $lResult = new XML_NodeList();
      
      if ($this->isComplex()) {
        
        foreach ($this->getChildren() as $oChild) {
          
          if ($oChild->useNamespace($sNamespace)) $lResult->add($oChild);
          else if ($mDepth === null || $mDepth > 0) {
            
            if ($mDepth) $mDepth--;
            $lResult->add($oChild->getChildren($sNamespace));
          }
        }
      }
      
      return $lResult;
      
    } else return new XML_NodeList($this->childNodes);
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
  public function addNode($sName, $oContent = '', $aAttributes = null, $sUri = null) {
    
    // Node : Automatically created Element based on strings and arrays
    
    return $this->insertChild($this->getDocument()->createNode($sName, $oContent, $aAttributes, $sUri));
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
  
  public function toArray($sAttribute = null, $iDepthAttribute = 0) {
    
    if (!$this->hasChildren() || $this->isTextElement()) $mValue = $this->getValue();
    else {
      
      $mValue = array();
      
      if ($this->getAttribute('key-type') == 'index') {
        
        $bIndex = true;
        $sChildAttribute = null;
        
      } else {
        
        $bIndex = false;
        if ($sAttribute && $iDepthAttribute > 0) $sChildAttribute = $sAttribute;
        else $sChildAttribute = $this->getAttribute('attribute-key');
      }
      
      foreach ($this->getChildren() as $oChild) {
        
        if ($oChild->isElement()) {
          
          list($sKey, $mSubValue) = $oChild->toArray($sChildAttribute, $iDepthAttribute - 1);
          
          if (!$bIndex) $mValue[$sKey] = $mSubValue;
          else $mValue[] = $mSubValue;
          
        } else {
          
          $mValue[] = $this->getValue();
        }
      }
    }
    
    if ($sAttribute) $sName = $this->getAttribute($sAttribute);
    else $sName = $this->getName(true);
    
    
    return array($sName, $mValue);
  }
  
  /**
   * Return a new element tree, with only the elements selected by namespace
   */
  public function extractNS($sNamespace, $bKeep = false) {
    
    $oResult = null;
    $oContainer = new XML_Element;
    
    foreach ($this->getChildren() as $oChild) {
      
      if ($oChild->isElement()) $oContainer->add($oChild->extractNS($sNamespace, $bKeep));
      else if ($this->getNamespace() == $sNamespace) {
        
        $oContainer->add($oChild);
        if (!$bKeep) $oChild->remove();
      }
    }
    
    if ($this->getNamespace() == $sNamespace) {
      
      $oResult = clone $this;
      $oResult->cleanChildren();
      
      if (!$bKeep) $this->replace($this->getChildren());
    }
    
    if ($oResult) $oResult->add($oContainer->getChildren());
    else $oResult = $oContainer->getChildren();
    
    return $oResult;
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
  
  /*** Others ***/
  
  public function isFirst() {
    
    return ($this->isRoot() || $this->getParent()->getFirst() === $this);
  }
  
  public function isRoot() {
    
    return (!$this->getParent() || ($this->getParent() === $this->getDocument())); // TODO tocheck
  }
  
  public function isEmpty() {
    
    return !$this->hasChildren();
  }
  
  public function isComplex() {
    
    return ($this->hasChildren() && ($this->countChildren() > 1 || $this->getFirst()->isElement()));
  }
  
  public function isSimple() {
    
    return (!$this->hasElementChildren() && $this->hasChildren());
  }
  
  /**
   * Test wether actual element has children or not
   * @return boolean The children actual existenz fact (or not)
   */
  
  public function hasElementChildren() { // TODO remove
    
    return $this->isComplex();
  }
  
  public function isTextElement() { // TODO remove
    
    return $this->isSimple();
  }
  
  public function isText() {
    
    return false;
  }
  
  public function isElement() {
    
    return true;
  }
  
  /*** Properties ***/
  
  public function getParent($sNamespace = null) {
    
    if ($sNamespace) {
      
      if ($this->isRoot()) return null;
      else if ($this->getParent()->getNamespace() != $sNamespace) return $this->getParent()->getParent($sNamespace);
      else return $this->getParent();
      
    } else return $this->parentNode;
  }
  
  public function getLast() {
    
    return $this->lastChild;
  }
  
  public function getFirst() {
    
    return $this->firstChild;
  }
  
  public function getNext() {
    
    return $this->nextSibling;
  }
  
  public function getPrevious() {
    
    return $this->previousSibling;
  }
  
  public function getNamespace($sPrefix = '') {
    
    if ($sPrefix != '') return $this->lookupNamespaceURI($sPrefix);
    else return $this->namespaceURI;
  }
  
  public function useDefaultNamespace() {
    
    return $this->isDefaultNamespace($this->getNamespace());
  }
  
  public function useNamespace($sNamespace = '') {
    
    if ($sNamespace) return ($this->getNamespace() == $sNamespace);
    else return ($this->getNamespace());
  }
  
  public function getPrefix() {
    
    return $this->prefix;
  }
  
  public function getName($bLocal = true) {
    
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
  
  /*** Render ***/
  
  public function parseXSL($oTemplate) {
    
    $oDocument = new XML_Document($this);
    
    return $oDocument->parseXSL($oTemplate);
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
    
    if (!$this->isRoot()) $this->insertBefore("\n".str_repeat('  ', $iLevel));
    
    foreach ($this->getChildren() as $oChild) {
      
      if ($oChild instanceof XML_Element) {
        
        $oChild->formatOutput($iLevel + 1);
        
      } else if ($oChild instanceof XML_CData || $oChild instanceof XML_Comment) {
        
        $oChild->getParent()->insert("\n".str_repeat('  ', $iLevel + 1), $oChild);
      }
    }
    
    if ($this->hasChildren()) {
      
      if ($this->countChildren() > 1) $this->add("\n".str_repeat('  ', $iLevel)); // || strlen($this->getFirst()) > 80
    }
  }
  
  public function viewResume($iLimit = 165, $bDecode = true) {
    
    $sView = stringResume($this->view(false, false, true), $iLimit, true);
    
    if ($bDecode) return htmlspecialchars_decode($sView);
    else return $sView;
  }
  
  public function view($bContainer = true, $bIndent = false, $bFormat = false) {
    
    $oResult = $this->cloneNode(true);
    
    if ($oResult) { // TODO, check if isReal()
      
      if ($bIndent) $oResult->formatOutput();
      if ($bFormat) $oResult = htmlspecialchars((string) $oResult);
      
      if ($bContainer) $oResult = new HTML_Tag('pre', wordwrap($oResult, 100));
      
    } else dspm(t('Impossible de créer l\'élément'), 'error');
    
    return $oResult;
  }
  
  public function dsp($bHtml = false) {
    
    $oResult = clone $this;
    $oResult->formatOutput();
    
    echo new HTML_Tag('pre', htmlspecialchars($oResult));
  }
  
  public function messageParse() {
    
    return new HTML_Div($this->viewResume(), array('class' => 'message-element'));
  }
  
  public function __toString() {
    
    // try {
      
      if (isset($this->nodeName) && $this->nodeName) {
        
        if ($this->childNodes && $this->childNodes->length) $sChildren = $this->implode('', $this->childNodes);
        else $sChildren = '';
        
        if ($this->attributes && $this->attributes->length) $sAttributes = ' '.$this->implode(' ', $this->attributes);
        else $sAttributes = '';
        
        $sResult = '<'.$this->nodeName.$sAttributes;
        
        if ($sChildren) $sResult .= '>'.$sChildren.'</'.$this->nodeName.'>';
        else $sResult .= ' />';
        
        return $sResult;
        
      } else {
        
        Controler::addMessage(t('Elément vide :('), 'xml/warning');
        return '';
      }
		// } catch ( Exception $e ) {
      
			// Controler::addMessage('Element : '.$e->getMessage(), 'xml/error');
		// }
  }
}
