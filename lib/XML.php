<?php

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
    
    Controler::addMessage(t('StrToXml : Transformation impossible'), 'xml/warning');
    
    return null;
  }
}

interface XML_Composante {
  
  // public function getValue();
  // public function formatOutput();
  public function isElement();
  public function isText();
  public function remove();
  public function messageParse();
  public function __toString();
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
  private $oFile = null;
  
  public function __construct($mChildren = '', $iMode = MODE_READ) {
    
    parent::__construct('1.0', 'UTF-8');
    
    $this->preserveWhiteSpace = false;
    // $this->substituteEntities = false;
    
    $this->registerNodeClass('DOMDocument', 'XML_Document');
    $this->registerNodeClass('DOMElement', 'XML_Element');
    $this->registerNodeClass('DOMText', 'XML_Text');
    $this->registerNodeClass('DOMAttr', 'XML_Attribute');
    $this->registerNodeClass('DOMCdataSection', 'XML_CData');
    $this->registerNodeClass('DOMDocumentFragment', 'XML_Fragment');
    
    $this->setMode($iMode);
    
    if ($mChildren) {
      
      // if Object else String
      if (is_object($mChildren)) $this->set($mChildren);
      else if (is_string($mChildren)) $this->startString($mChildren);
    }
  }
  
  public function getMode() {
    
    return $this->iMode;
  }
  
  private function setMode($iMode) {
    
    $this->iMode = in_array($iMode, array(MODE_EXECUTION, MODE_WRITE, MODE_READ)) ? $iMode : MODE_READ;
  }
  
  private function extractMode($oNode) {
    
    $iMode = 7;
    
    if (!($oNode->hasAttributeNS(NS_SECURITY, 'owner') &&
      $oNode->hasAttributeNS(NS_SECURITY, 'mode') &&
      $oNode->hasAttributeNS(NS_SECURITY, 'group'))) {
      
      Controler::addMessage(xt('Sécurité : Élément sécurisé incomplet : %s', new HTML_Tag('em', $oNode->viewResume())), 'xml/warning');
      
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
          
          if (Controler::useStatut('xml/report')) {
            
            if ($this->getFile()) $sPath = $this->getFile()->parse();
            else $sPath = 'le document';
            
            if ($oNodes->length == 1) $oMessage = xt('%s élément  sécurisé trouvé dans %s', new HTML_Strong('1'), $sPath);
            else $oMessage = xt('%s éléments  sécurisés trouvés dans %s', new HTML_Strong($oNodes->length), $sPath);
            
            Controler::addMessage($oMessage, 'xml/report');
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
    if ($sString{0} == '/') return $this->loadFile($sString);
    else if ($sString{0} == '<') return $this->loadText($sString);
    else return $this->set(new XML_Element($sString, '', null, '', $this));
  }
  
  public function createNode($sName, $oContent = '', $aAttributes = null, $sUri = null) {
    
    return new XML_Element($sName, $oContent, $aAttributes, $sUri, $this);
  }
  
  public function isEmpty() {
    
    return !$this->getRoot();
  }
  
  public function loadFile($sPath) {
    
    if (($oFile = Controler::getFile($sPath, true)) && $oFile->checkRights($this->iMode)) {
      
      $this->oFile = $oFile;
      
      if (!$oFile->isLoaded()) {
        
        // not yet loaded
        
        if (SYLMA_ACTION_STATS && ($oFile->getExtension() != 'eml') && Controler::getUser()->isMember('0'))
          Controler::infosSetFile($oFile, true);
        
        parent::load(MAIN_DIRECTORY.$sPath);
        if (Controler::useStatut('xml/report')) Controler::addMessage(xt('Chargement du fichier %s', $oFile->parse()), 'xml/report');
        
        if ($this->isEmpty()) {
          
          Controler::addMessage(xt('Aucun contenu dans %s', $oFile->parse()), 'xml/warning');
          $oFile->setDocument(new XML_Document);
          
        } else {
          
          XML_Controler::addStat('file');
          
          $oFile->setDocument(new XML_Document($this->getRoot())); // getRoot avoid parsing of specials classes like actions
          $oFile->isFileSecured($this->appendLoadRights());
        }
        
        return true;
        
      } else if (!$oFile->getFreeDocument()->isEmpty()) {
        
        // already loaded
        
        if (SYLMA_ACTION_STATS && ($oFile->getExtension() != 'eml') && Controler::getUser()->isMember('0'))
          Controler::infosSetFile($oFile, false);
        
        $this->set($oFile->getFreeDocument());
        if ($oFile->isFileSecured()) $this->appendLoadRights();
        
        return true;
      }
      
    } else Controler::addMessage(xt('Fichier %s introuvable', new HTML_Strong($sPath)), 'xml/warning');
    
    return false;
  }
  
  public function loadFreeFile($sPath) {
    
    parent::load(MAIN_DIRECTORY.$sPath);
    if (Controler::useStatut('xml/report')) dspm(xt('Chargement [libre] du fichier %s', new HTML_Strong($sPath)), 'xml/report');
    
    if ($this->isEmpty()) dspm(xt('Aucun contenu [libre] dans %s', new HTML_Strong($sPath)), 'xml/warning');
    
    XML_Controler::addStat('file');
  }
  
  public function loadText($sContent) {
    
    if ($sContent) {
      
      // TODO : Bug with not UTF-8, can't display the text -> recursive call
      // if (!parent::loadXML($sContent)) Controler::addMessage(xt('Document : Chargement texte impossible, contenu invalide : %s', new HTML_Tag('pre', htmlspecialchars(wordwrap($sContent, 100)))), 'xml/warning');
      if (!parent::loadXML($sContent)) Controler::addMessage(t('Chargement texte impossible, contenu invalide'), 'xml/warning');
      if ($this->isEmpty()) Controler::addMessage(t('Chargement texte échoué, aucun résultat'), 'xml/warning');
      
      XML_Controler::addStat('load');
      
    } else Controler::addMessage(t('Document : Chargement texte impossible, la chaîne est vide !'), 'xml/error');
    
    // $this->appendLoadRights(); TODO or not
  }
  
  public function setFile($oFile) {
    
    $this->oFile = $oFile;
    return $oFile;
  }
  
  public function getFile() {
    
    return $this->oFile;
  }
  
  public function save($sPath = null) {
    
    if ($sPath || ($sPath = (string) $this->getFile())) {
      
      $sName = substr(strrchr($sPath, '/'), 1);
      $sDirectory = substr($sPath, 0, strlen($sPath) - strlen($sName) - 1);
      
      if ($oDirectory = Controler::getDirectory($sDirectory)) {
        
        $bSecuredFile = ($sName == SECURITY_FILE);
        $bAccess = ($oFile = Controler::getFile($sPath)) ? $oFile->checkRights(MODE_WRITE) : $oDirectory->checkRights(MODE_WRITE);
        
        // TEMPORARY System fo avoiding erasing of protected files from not admin users
        
        if ((!$bSecuredFile && $bAccess) || ($bSecuredFile && Controler::isAdmin())) {
          
          if ($oFile && $oFile->isFileSecured()) {
            
            // Secured File
            
            $oDocument = $oFile->getDocument();
            $bSecured = false;
            
            $oNodes = $oDocument->query('//*[@ls:owner or @ls:mode or @ls:group]', 'ls', NS_SECURITY);
            
            if ($oNodes->length) {
              
              foreach ($oNodes as $oNode) {
                
                if ($oNode) {
                  
                  $iMode = $this->extractMode($oNode);
                  
                  if (!$bSecured && $iMode) $bSecured = !(MODE_WRITE & $iMode);
                }
              }
            }
            
          } else $bSecured = false; // Not secured file
          
          if (!$bSecured || Controler::isAdmin()) {
            
            $this->formatOutput = true;
            
            $sPath = MAIN_DIRECTORY.$sPath;
            
            if ($oFile) unlink($sPath);
            $bResult = parent::save($sPath);
            
            $oDirectory->updateFile($sName);
            
          } else dspm(xt('Le fichier %s contient des balises protégées, le système ne permet actuellement pas de modifier ce type de fichier, veuillez contacter l\'administrateur !', new HTML_Strong($sPath)), 'error');

          
          return $bResult;
          
        } else dspm(xt('Droits insuffisants pour sauvegarder le fichier dans %s !', new HTML_Strong($sPath)), 'xml/error');
        
      } else dspm(xt('Le répertoire de destination %s n\'existe pas !', new HTML_Strong($sPath)), 'xml/error');
      
    } else  dspm(t('Aucun chemin pour la sauvegarde !'), 'xml/error');
    
    return false;
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
  
  public function set() {
    
    if (func_num_args() > 1) {
      
      $this->set(func_get_args());
      
    } else if (func_num_args() == 1) {
      
      $mValue = func_get_arg(0);
          
      if (is_object($mValue)) {
        
        if ($mValue instanceof XML_Document && !method_exists($mValue, 'parse')) {
          
          /* XML_Document */
          
          if ($mValue->getRoot()) {
            
            $this->setChild($mValue->getRoot());
            
          } else Controler::addMessage('Document->set() - Document vide', 'xml/notice');
          
        } else if ($mValue instanceof XML_Element) {
          
          /* XML_Element */
          
          $this->setChild($mValue);
          
        } else if ($mValue instanceof XML_NodeList) {
          
          /* XML_NodeList */
          
          $this->set($mValue->current());
          $mValue->next();
          
          while ($mValue->valid()) {
            
            $this->add($mValue->current());
            $mValue->next();
          }
          
        } else {
          
          /* Undefined object (Action, XML_Action, others) */
          
          if (method_exists($mValue, 'parse')) {
            
            $mResult = $mValue->parse();
            
            if ($mResult != $mValue) return $this->set($mResult);
            else Controler::addMessage(xt('L\'objet parsé de classe "%s" ne doit pas se retourner lui-même !', new HTML_Strong(get_class($mValue))), 'xml/error');
            
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
        
      } else if (is_string($mValue)) $mValue = $this->startString($mValue);
      
      return $mValue;
      
    } else if ($this->getRoot()) $this->getRoot()->remove();
    
    return null;
  }
  
  public function addNode($sName, $oContent = '', $aAttributes = null, $sUri = '') {
    
    if ($this->getRoot()) return $this->getRoot()->addNode($sName, $oContent, $aAttributes, $sUri);
    else return $this->setChild($this->createNode($sName, $oContent, $aAttributes, $sUri));
  }
  
  public function setChild($oChild) {
    
    if (!$this->isEmpty()) $this->getRoot()->remove();
    
    if ($oChild && is_object($oChild)) {
      
      if ((bool) $oChild->getDocument() && ($oChild->getDocument() !== $this)) {
        
        $oChild = $this->importNode($oChild);
      }
      
      parent::appendChild($oChild);
      
    } else Controler::addMessage('Element->setChild : No object', 'xml/error');
    
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
  
  public function importNode($oChild, $bDepth = true) {
    
    if ($oChild) {
      
      if ($oChild instanceof HTML_Tag) {
        
        $oChild = clone $oChild;
        $oChild->parse();
      }
      
      return parent::importNode($oChild, $bDepth);
      
    } else Controler::addMessage('Document->importNode : No object', 'xml/error');
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
  
  /**
   * Return the first row of a result of an xpath query
   */
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
  
  /**
   * Return a new document, with only the elements selected by namespace
   */
   public function extractNS($sNamespace, $bKeep = false) {
    
    if (!$this->isEmpty()) return new XML_Document($this->getRoot()->extractNS($sNamespace, $bKeep));
    else return null;
  }
  
  /** 
   * Parse a template with this document as parameter
   */
  public function parseXSL($oDocument) {
    
    if (is_object($oDocument)) {
      
      if (!$oDocument instanceof XSL_Document) {
        
        $oTemplate = new XSL_Document($oDocument);
        $oTemplate->setFile($oDocument->getFile());
        
      } else $oTemplate = $oDocument;
      
      return $oTemplate->parseDocument($this);
      
    } else Controler::addMessage(xt('Template %s invalide !', Controler::formatResource($oDocument)), 'xml/error');
    
    return null;
  }
  
  public function view($bContainer = true, $bIndent = true, $bFormat = false) {
    
    $oView = new XML_Document($this);
    
    if ($bIndent) $oView->formatOutput();
    
    if ($bFormat) $sResult = htmlspecialchars($oView->display(false, false));
    else $sResult = $oView->display(false, false);
    
    if ($bContainer) {
      
      $oResult = new HTML_Tag('pre');
      $oResult->add($sResult);
      
    } else $oResult = $sResult;
    
    return $oResult;
  }
  
  public function formatOutput() {
    
    if ($this->getRoot()) $this->getRoot()->formatOutput();
  }
  
  public function dsp($bHtml = false) {
    
    echo $this->view(true, true, $bHtml);
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
    
    return $this->display();
  }
  
  /**
   * Return path of the document as a string
   */
  public function parseFile() {
    
    if ($this->getFile()) return $this->getFile()->parse();
    else return new HTML_Em(t('- aucun chemin -'));
  }
  
  public function __call($sMethod, $aArguments) {
    
    $oResult = null;
    
    if ($oRoot = $this->getRoot()) {
      
      if (method_exists($oRoot, $sMethod)) {
        
        $aEvalArguments = array();
        for ($i = 0; $i < count($aArguments); $i++) $aEvalArguments[] = "\$aArguments[$i]";
        
        eval('$oResult = $oRoot->$sMethod('.implode(', ', $aEvalArguments).');');
        
      } else Controler::addMessage(array(xt('Document : Méthode %s introuvable', new HTML_Strong($sMethod)), Controler::getBacktrace()), 'xml/error');
      
    } else Controler::addMessage(xt('Document vide : Impossible d\'appliquer la méthode %s', new HTML_Strong($sMethod)), 'xml/error');
    
    return $oResult;
  }
  
  public function display($bHtml = false, $bDeclaration = true) {
    
    $sResult = '';
    
    if (!$this->isEmpty()) {
      
      if ($bHtml) $sResult = parent::saveXML(null, LIBXML_NOEMPTYTAG); //
      else {
        
        if ($bDeclaration) $sResult = parent::saveXML();
        else $sResult = parent::saveHTML();
      }
    }
    
    return $sResult;
  }
  
  public function __toString() {
    
    return $this->display();
  }
}

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
  
  /**
   * @return string The CSS name of the element relative to his brotherhood. ex: 'a:eq(2)'
   */
  private function getCSSPathName() {
    
    if (!$sPrefix = $this->getPrefix()) {
      
      $sPrefix = 'ns';
      $sName = 'ns:'.$this->getName(false);
      
    } else $sName = $this->getName();
    
    $aNS = array($sPrefix => $this->getNamespace());
    
    // first check all children
    
    $aSiblings = $this->getParent()->query($sName, $aNS);
    
    if ($aSiblings->length - 1) {
      
      // if there are, get the preceding count
      
      // $iPrevious = $this->query("preceding-sibling::$sName", $aNS)->length + 1;
      // $sName = $this->getName(true).':nth-child('.$iPrevious.')';
      $iPrevious = $this->query("preceding-sibling::*", $aNS)->length + 1;
      $sName = '*:nth-child('.$iPrevious.')';
      
    } else $sName = $this->getName(true);
    
    return $sName;
  }
  
  /**
   * @return string The CSS path of the element relative to his parent and brotherhood. ex: 'div > a:eq(2)'
   */
  public function getCSSPath($oLastParent = null) {
    
    $oNodes = $this->query("ancestor-or-self::*[namespace-uri() = '{$this->getNamespace()}']");
    $oNodes->reverse();
    
    $oNode = null;
    $aPath = array();
    
    foreach ($oNodes as $oNode) {
      
      if ($oLastParent && ($oLastParent === $oNode)) break;
      else $aPath[] = $oNode->getCSSPathName();
    }
    
    $sResult  = ($oLastParent === $oNode) ? '' : '/';
    
    return $sResult.implode(' > ', array_reverse($aPath));
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
        if (XML_VIEW_QUERY) echo 'read : '.$sQuery.new HTML_Br;
        
        if ($mResult === null) {
          
          $mResult = '';
          if (Controler::useStatut('xml/report')) Controler::addMessage(xt("Element->read(%s) : Aucun résultat", new HTML_Strong($sQuery)), 'xml/report');
        }
        
      } else if (Controler::useStatut('xml/report')) Controler::addMessage(xt("Element->read(%s) : Impossible de crée l'objet XPath", new HTML_Strong($sQuery)), 'xml/report');
      
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
  public function query($sQuery, $mValue = '', $sUri = '') {
    
    if (is_string($sQuery) && $sQuery) {
      
      $oXPath = $this->buildXPath($mValue, $sUri);
      
      if ($oXPath) {
        
        $mResult = $oXPath->query($sQuery, $this);
        
        XML_Controler::addStat('query');
        if (XML_VIEW_QUERY) echo 'query : '.$sQuery.new HTML_Br;
        
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
  
  /**
   * Add an attribute object to the element
   * @param XML_Attribute $oAttribute Attribute to add to the element, may be owned by the document owner
   * @return XML_Attribute The attribute passed in argument (?? normally)
   */
  public function setAttributeNode($oAttribute) {
    
    // TODO : RIGHTS
    return parent::setAttributeNode($oAttribute);
  }
  
  public function hasAttributes() {
    
    foreach (func_get_args() as $sArg) if (!$this->getAttribute($sArg)) return false;
    
    return true;
  }
  
  /**
   * Evaluate the attribute as a boolean value (true or false or TRUE or FALSE) or compare as a string
   * @param string $sAttribute Attribute name to get
   * @param boolean|null|string $mDefault Value to compare or return by default
   * @return boolean|null The value of the attribute, or $mDefault if it's not a boolean value
   */
  public function testAttribute($sAttribute, $mDefault = null) {
    
    if (is_string($mDefault)) return ($this->getAttribute($sAttribute) == $mDefault);
    return strtobool(strtolower($this->getAttribute($sAttribute)), $mDefault);
  }
  
  /**
   * Set an attribute of the element, remove the attribute if $sValue is null
   * @param string $sName The name of the attribute
   * @param string $sValue The value of the attribute
   */
  public function setAttribute($sName, $sValue = '', $sUri = null) {
    
    // TODO : RIGHTS
    
    if ($sValue !== '' && $sValue !== null) {
      
      if ($sUri) return parent::setAttribute($sName, $sValue);
      else return parent::setAttributeNS($sUri, $sName, $sValue);
      
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
  
  public function cloneAttributes($oElement) {
    
    foreach ($oElement->getAttributes() as $oAttribute)
      $this->setAttribute($oAttribute->getName(), $oAttribute->getValue());
  }
  
  public function cloneAttribute($oElement, $mAttribute = null) {
    
    if ($mAttribute) {
      
      if (is_array($mAttribute)) {
        
        foreach ($mAttribute as $sAttribute)
          if ($oElement->hasAttribute($sAttribute)) $this->cloneAttribute($oElement, $sAttribute);
        
      } else $this->setAttribute($mAttribute, $oElement->getAttribute($mAttribute));
      
    } else $this->cloneAttributes($oElement);
  }
  
  public function merge($oElement, $bSelfPrior = false) {
    
    $oResult = new XML_Element($this->getName(false), $this->getChildren(), $this->getAttributes(), $this->getNamespace());
    
    foreach ($oElement->getChildren() as $oChild) {
      
      if ($oSame = $oResult->get($oChild->getName(), $oChild->getPrefix(), $oChild->getNamespace())) {
        
        if (!$bSelfPrior) $oSame->replace($oChild);
        
      } else $oResult->add($oChild);
    }
    
    $oResult->cloneAttribute($oElement);
    
    return $oResult;
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
    else Controler::addMessage(array(t('Element : Impossible d\'insérer un noeud avant le noeud racine'), $this->messageParse()), 'xml/error');
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
      
      if ($mValue instanceof XML_Element || $mValue instanceof XML_Text || $mValue instanceof XML_CData) {
        
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
    
    if (is_object($oChild) && ($oChild instanceof XML_Element || $oChild instanceof XML_Text || $oChild instanceof XML_CData)) {
      
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
  public function hasElementChildren() {
    
    return ($this->hasChildren() && ($this->countChildren() > 1 || $this->getFirst()->isElement()));
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
  
  public function toArray($sAttribute = null) {
    //dspf($this);
    if ($this->isTextElement()) $mValue = $this->getValue();
    else {
      
      $mValue = array();
      
      if (!$bIndex = $this->testAttribute('key-type', 'index')) $sChildAttribute = $this->getAttribute('attribute-key');
      else $sChildAttribute = null;
      
      foreach ($this->getChildren() as $oChild) {
        
        if ($oChild->isElement()) {
          
          list($sKey, $mSubValue) = $oChild->toArray($sChildAttribute);
          
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
  
  public function isTextElement() {
    
    return (!$this->hasElementChildren() && $this->hasChildren());
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
  
  public function getLast() {
    
    return $this->lastChild;
  }
  
  public function getFirst() {
    
    return $this->firstChild;
  }
  
  public function getNamespace() {
    
    return $this->namespaceURI;
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
    
    /*foreach ($this->query('//namespace') as $oChild) {
      
      $this->setAttribute('xmlns:'.$oChild->getName(), $oChild->getValue(), NS_XMLNS);
    }*/
    
    if ($bIndent) $oResult->formatOutput();
    if ($bFormat) $oResult = htmlspecialchars((string) $oResult);
    
    if ($bContainer) $oResult = new HTML_Tag('pre', wordwrap($oResult, 100));
    
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
        
        Controler::addMessage(t('Elément vide :('), 'xml/warning');
        return '';
      }
		// } catch ( Exception $e ) {
      
			// Controler::addMessage('Element : '.$e->getMessage(), 'xml/error');
		// }
  }
}

class XML_Attribute extends DOMAttr {
  
  public function __construct($sName, $sValue) {
    
    parent::__construct($sName, $sValue);
  }
  
  public function getName() {
    
    return $this->name;
  }
  
  public function getValue() {
    
    return $this->value;
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

class XML_CData extends DOMCdataSection implements XML_Composante {
  
  public function setValue($mValue) {
    
    $this->data = (string) $mValue;
  }
  
  public function getValue() {
    
    return $this->data;
  }
  
  public function remove() {
    
    return $this->parentNode->removeChild($this);
  }
  
  public function isText() {
    
    return true;
  }
  
  public function isElement() {
    
    return false;
  }
  
  public function messageParse() {
    
    return new HTML_Span((string) $this, array('class' => 'message-element'));
  }
  
  public function formatOutput($iLevel = 0) {
    
    return null;
  }
  
  public function __toString() {
    
    return $this->data;
    // return "<![CDATA[\n".$this->data.']]>';
  }
}

class XML_Text extends DOMText implements XML_Composante {
  
  // private $aRights = array();
  
  public function __construct($mContent) {
    
    if (is_object($mContent)) {
      
      if (method_exists($mContent, '__toString')) $mContent = (string) $mContent;
      else {
        
        Controler::addMessage(xt('Object " %s " cannot be converted to string !', new HTML_Strong(get_class($mContent))), 'xml/error');
        $mContent = '';
      }
    }
    // if (!(is_string($mContent) || is_numeric($mContent))) $mContent = '';
    // if ($mContent === 0) $mContent = '00'; //dom bug ?
    parent::__construct($mContent);
  }
  
  public function getDocument() {
    
    return $this->ownerDocument;
  }
  
  public function replace($mChild) {
    
    if (is_string($mChild)) $oChild = new XML_Text($mChild);
    else $oChild = $mChild;
    
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
  
  public function messageParse() {
    
    return new HTML_Span((string) $this, array('class' => 'message-element'));
  }
  
  public function __toString() {
    
    try {
      
      return $this->nodeValue;
      
		} catch ( Exception $e ) {
      
			Controler::addMessage('Text : '.$e->getMessage(), 'xml/error');
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
      else if ($oNodeList instanceof DOMNodeList || $oNodeList instanceof DOMNamedNodeMap) $this->length = $oNodeList->length;
      else Controler::addMessage('NodeList : Type invalide !', 'xml/error');
      
    } else {
      
      // Controler::addMessage('NodeList : Tableau vide !', 'xml/warning');
    }
  }
  
  public function toArray($sMode = null, $sAttribute = null) {
    
    $aResults = array();
    
    foreach ($this as $oNode) {
      
      switch ($sMode) {
        
        case 'id' : $aResults[$oNode->getAttribute('id')] = $oNode->getChildren()->toArray(); break;
        case 'name' : $aResults[] = $oNode->getName(); break;
        // case 'attribute' : $aResult[] = $oNode->getAttribute($sAttribute);
        default :
          
          // if ($oNode->isEmpty()) $aResults[] = $oNode->getName();
          if ($oNode->isText()) $aResults[] = $oNode->getValue();
          else $aResults[$oNode->getName()] = $oNode->getValue();
      }
    }
    
    return $aResults;
  }
  
  public function getFirst() {
    
    return $this->item(0);
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
        
      } else Controler::addMessage(xt('NodeList : Méthode %s introuvable', new HTML_Strong($sMethod)), 'xml/error');
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
    
    if (array_key_exists($this->iIndex, $this->aNodes)) return $this->aNodes[$this->iIndex];
    else return null;
  }
  
  public function view() {
    
    $aResult = array();
    foreach ($this->aNodes as $oNode) $aResult[] = $oNode->view(true, true, false);
    
    return new HTML_Div($aResult);
  }
  
  public function valid() {
    
    return ($this->iIndex < count($this->aNodes));
  }
  
  public function reverse() {
    
    $this->aNodes = array_reverse($this->aNodes);
    $this->rewind();
  }
  
  public function implode($sSeparator = ' ') {
    
    $aResult = array();
    
    foreach ($this->aNodes as $oNode) {
      
      $aResult[] = $oNode; 
      $aResult[] = $sSeparator;
    }
    
    array_pop($aResult);
    return $aResult;
  }
  
  public function __toString() {
    
    return implode('', $this->implode());
  }
}

class XML_Fragment extends DOMDocumentFragment { }

class XSL_Document extends XML_Document {
  
  private $oProcessor = null;
  
  public function __construct($mChildren = '', $iMode = MODE_READ) {
    
    $this->oProcessor = new XSLTProcessor();
    if ($mChildren) parent::__construct($mChildren, $iMode);
    else {
      
      parent::__construct(new XML_Element('xsl:stylesheet', null, array('xmlns' => NS_XHTML, 'version' => '1.0'), NS_XSLT), $iMode);
      
      //new XML_Element('output', array('method' => 'xml', 'encoding' => 'utf-8'), true, 'xsl'));
      // 'xmlns:fo'    => 'http://www.w3.org/1999/XSL/Format',
      // 'xmlns:axsl'  => 'http://www.w3.org/1999/XSL/TransformAlias',
    }//$this->includeExternals();
  }
  
  public function removeParameter($sLocalName, $sUri = '') {
    
    $bResult = $this->getProcessor()->removeParameter($sUri, $sLocalName);
    
    if (!$bResult) Controler::addMessage(xt('Suppression impossible du paramètre %s - [%s]', new HTML_Strong($sName), new HTML_Strong($sValue), new HTML_Strong($sUri)), 'xml/warning');
    return $bResult;
  }
  
  public function setParameter($sName, $sValue, $sUri = '') {
    
    $bResult = $this->getProcessor()->setParameter($sUri, $sName, (string) $sValue);
    
    if (!$bResult) Controler::addMessage(xt('Création du paramètre %s impossible avec la valeur %s - [%s]', new HTML_Strong($sName), new HTML_Strong($sValue), new HTML_Strong($sUri)), 'xml/warning');
    return $bResult;
  }
  
  public function getParameter($sLocalName, $sUri = '') {
    
    $mResult = $this->getProcessor()->getParameter($sUri, $sLocalName);
    
    if (!$mResult) Controler::addMessage(xt('Aucun résultat pour le paramètre %s - [%s]', new HTML_Strong($sName), new HTML_Strong($sUri)), 'xml/warning');
    return $mResult;
  }
  
  private function getProcessor() {
    
    return $this->oProcessor;
  }
  
  public function includeExternals(&$aPaths = array(), $iLevel = 0) {
    
    $sPath = (string) $this->getFile();
    $iMaxLevel = XSL_MAX_IMPORT_DEPTH;
    
    if ($iLevel > $iMaxLevel) {
      
      Controler::addMessage(xt('Trop d\'imbrications ou redondances dans %s dans %s', new HTML_Strong(t('les importations')), $this->getFile()), 'warning');
      return false;
      
    } else if ($sPath && in_array($sPath, $aPaths)) {
      
      // Template ever imported
      return false;
      
    } else {
      
      $oExternals = $this->query('/*/xsl:include | /*/xsl:import', 'xsl', NS_XSLT);
      
      if ($oExternals->length) {
        
        $aPaths[] = $sPath;
        
        foreach ($oExternals as $oExternal) {
          
          if ($sHref = $oExternal->getAttribute('href')) {
            //dspm($oExternal->view());
            //dspm($this->getFile()->getParent().' / '.$sImportPath);
            if ($this->getFile()) $sImportPath = Controler::getAbsolutePath($sHref, $this->getFile()->getParent().'/');
            else $sImportPath = Controler::getAbsolutePath($sHref, '/');
            
            $oTemplate = new XSL_Document($sImportPath, $this->getMode());
            
            if (!$oTemplate->isEmpty() && $oTemplate->includeExternals($aPaths, $iLevel + 1)) {
              
              switch ($oExternal->getName(true)) {
                
                case 'include' : $oExternal->replace($oTemplate->getChildren()); break;
                case 'import' : $this->shift($oTemplate->getChildren()); break;
              }
            }
          }
          
          $oExternal->remove();
        }
      }
    }
    
    return true;
  }
  
  public function parseDocument($oDocument, $bXML = true) {
    
    $mResult = null;
    
    if ($oDocument && !$oDocument->isEmpty() && !$this->isEmpty()) {
      
      $this->includeExternals();
      $this->getProcessor()->importStylesheet($this);
      
      $sResult = $this->getProcessor()->transformToXML($oDocument);
      
      if ($bXML) {
        
        $mResult = new XML_Document($sResult);
        
        if ($mResult->isEmpty()) Controler::addMessage(array(
          t('Un problème est survenu lors de la transformation XSL !'),
          new HTML_Hr,
          $sResult), 'xml/warning');
        
      } else $mResult = substr($sResult, 21);
      
      XML_Controler::addStat('parse');
    }
    
    return $mResult;
    
  }
}
