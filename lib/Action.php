<?php

class XML_Action extends XML_Document {
  
  private $oPath = null;
  private $oPathResume = null;
  private $sName = '';
  private $aVariables = array();
  private $oRedirect = null;
  private $sStatut = null;
  private $aProcessors = array();
  private $aNS = array('le' => SYLMA_NS_EXECUTION, 'le' => SYLMA_NS_INTERFACE, 'xsl', SYLMA_NS_XSLT );
  
  private $aQueries = array();
  
  // stats & infos resume
  private $aStats = array();
  public $aSubActions = array();
  private $oResume = null;
  
  public function __construct($mPath = null, $oRedirect = null, $aProcessors = array()) {
    
    if ($mPath) { // allow anonymouse action
      
      if ($mPath instanceof XML_Path) {
        
        if ($mPath->getPath()) $this->oPath = $mPath;
        else {
          
          dspm(xt('Chemin invalide pour l\'action %s', new HTML_Strong($mPath->getOriginalPath())), 'action/error');
          $this->oPath = new XML_Path(SYLMA_PATH_ERROR);
        }
        
      } else $this->oPath = new XML_Path($mPath, null, true);
      
      if (!$oRedirect) $oRedirect = new Redirect;
      $this->setRedirect($oRedirect);
      
      if ($aProcessors) {
        
        foreach ($aProcessors as $oProcessor) $oProcessor->startAction($this);
        $this->aProcessors = $aProcessors;
      }
      
      parent::__construct((string) $this->getPath(), MODE_EXECUTION);
      
    } else parent::__construct();
  }
  
  private function getDirectory() {
    
    $sParent = '';
    
    if (!$this->getPath()->getFile()) dspm(array(t('Chemin introuvable pour l\'action'), $this->getPath()), 'action/error');
    else $sParent = $this->getPath()->getFile()->getParent();
    
    return $sParent;
  }
  
  public function getPath() {
    
    return $this->oPath;
  }
  
  private function getStatut() {
    
    return $this->sStatut;
  }
  
  private function setStatut($sStatut) {
    
    $this->sStatut = $sStatut;
  }
  
  private function getAbsolutePath($sPath) {
    
    return Controler::getAbsolutePath($sPath, $this->getDirectory());
  }
  
  private function loadInterface($oInterface) {
    
    $oResult = null;
    $sMethod = '';
    $aArguments = array();
    
    if ($oInterface = Action_Controler::setInterface($oInterface)) {
      
      $sClassName = $oInterface->readByName('name');
      if ($sFile = $oInterface->readByName('file')) $sFile = $this->getAbsolutePath($sFile);
      
      if ($oConstruct = $oInterface->getByName('method-construct')) {
        
        if ($oConstruct->hasChildren()) {
          
          $aArguments = $this->parseArguments($oConstruct, array(), true);
          
          if (!$aArguments && ($oConstruct->query('ns:argument[@required="false"]')->length != $oConstruct->query('ns:argument')->length)) {
            
            dspm('Erreur dans les arguments, impossible de construire l\'objet', 'action/warning');
            return null;
          }
        }
      }
      
      $oObject = $this->buildClass($sClassName, $sFile, $aArguments);
      
      if (($sMethod = $this->getPath()->getIndex()) && is_string($sMethod)) {
        
        // simulate action interface call, with args recup (get-redirect) and default return (return)
        
        $oElement = new XML_Element('li:'.$sMethod, null, array('get-redirect' => 'true', 'return' => 'true'), SYLMA_NS_INTERFACE);
        list($oSubResult, $bSubResult) = $this->runInterfaceMethod($oObject, $oElement, $this);
        
        if ($bSubResult) $oResult = $oSubResult;
        else $oResult = $oObject;
        
      } else $oResult = $oObject;
    }
    
    return $oResult;
  }
  
  private function getVariable($sKey) {
    
    if (array_key_exists($sKey, $this->aVariables)) return $this->aVariables[$sKey];
    else {
      
      dspm(xt('La variable "%s" n\'existe pas dans %s !', new HTML_Strong($sKey), $this->getPath()->parse()), 'action/error');
      return null;
    }
  }
  
  private function setVariable($sKey, $mValue) {
    
    $this->aVariables[$sKey] = $mValue;
    /*if ($mValue) 
    else if (array_key_exists($sKey, $this->aVariables)) unset($this->aVariables[$sKey]);*/
  }
  
  private function setVariableElement($oElement, $mVariable) {
    
    if ($sVariable = $oElement->getAttribute('set-variable')) {
      
      $this->setVariable($sVariable, $mVariable);
      if (Controler::useStatut('action/report')) dspm(xt('Ajout de la variable "%s" : %s', $sVariable, Controler::formatResource($mVariable)), 'action/report');
    }
  }
  
  public function runInterfaceList($mObject, $oElement, $bStatic = false) {
    
    $mResult = null;
    $aResults = array();
    
    if (is_array($mObject)) $mObject = new Action_Array($mObject);
    
    if (is_object($mObject) || $bStatic) $oInterface = Action_Controler::getInterface($mObject);
    else $oInterface = null;
    
    foreach ($oElement->getChildren() as $oChild) {
      
      if ($oChild->isElement()) {
        
        if ($oChild->getNamespace() == SYLMA_NS_INTERFACE) {
          
          list($mResult, $bReturn) = $this->runInterfaceMethod($mObject, $oChild, $oInterface, $bStatic);
          
          if ($bReturn) $aResults[] = $mResult;
          
        } else if ($oProcessor = $this->getProcessor($oChild->getNamespace())) {
          
          $mResult = $this->runProcessor($oChild, $oProcessor);
          //$oChild->remove();
          
          if (Controler::useStatut('action/report')) dspm(array(
            t('Construction [ifc] :'),
            Controler::formatResource($mResult),
            $oChild->messageParse()), 'action/report');
          
        } else {
          
          dspm(array(xt('runInterfaceList() : L\'élément suivant n\'est pas permis dans %s ', $this->getPath()->parse()), $oChild->messageParse()), 'action/error');
          $oChild->remove();
        }
        
      } else $aResults[] = $oElement->getValue();
    }
    
    if ($aResults) {
      
      if (count($aResults) == 1) $mResult = $aResults[0];
      else $mResult = new XML_NodeList($aResults);
    }
    
    return array($mResult, ($aResults));
  }
  
  /**
   * Beginning of all XML_Action, run an object's method
   * @param mixed $mObject The object having the method
   * @param XML_Element $oElement The element containing the method parameters
   * @param XML_Document $oInterface The interface document of the object
   * @param boolean $bStatic Type of call to the object, if true it's a static::call
   * @return array [0] The return value, [1] A boolean that indicates if the value have to be keeped
   */
  private function runInterfaceMethod($mObject, $oElement, $oInterface, $bStatic = false) {
    
    $oResult = null;
    $bReturn = false;
    $sActionMethod = $oElement->getName(true);
    
    if ($sActionMethod == 'if') {
      
      if ($mObject) $oResult = $this->buildArgument($oElement->getChildren());
      $bReturn = $oElement->testAttribute('return', true);
      
    } else if ($sActionMethod == 'if-not') {
      
      if (!$mObject) $oResult = $this->buildArgument($oElement->getChildren());
      $bReturn = $oElement->testAttribute('return', true);
      
    } else if (!$oInterface) {
      
      dspm(array(xt('Pas d\'interface pour l\'instruction %s dans %s (Objet : %s) ',
        view($oElement),
        $this->getPath()->parse(),
        view($mObject))), 'action/warning');
      
    } else if (!$oMethod = $oInterface->get("ns:method[@path='$sActionMethod']")) {
      
      dspm(array(xt('Méthode "%s" inexistante dans l\'interface "%s"', new HTML_Strong($oElement->getName(true)), new HTML_Strong($oInterface->read('ns:name'))), $oElement->messageParse()), 'action/warning');
      
    } else {
      
      // @return (bool) : erase & replace parent result up-to caller
      $bReturn = $oElement->testAttribute('return');
      if ($bReturn === null) $bReturn = $oMethod->testAttribute('return-default', false);
      
      // @le:format (string) : force children in one var with type indicated
      
      if ($sFormat = $oElement->getAttribute('format', SYLMA_NS_EXECUTION)) {
        
        $aArguments = array('index' => array($this->parseBaseType($sFormat, $oElement)));
        $oElement->cleanChildren();
        
      } else $aArguments = $this->loadElementArguments($oElement);
      
      // check name in interface
      
      if (!$sMethod = $oMethod->getAttribute('name')) {
        
        dspm('Interface invalide, attribut \'nom\' manquant', 'action/error');
        
      } else {
        
        // control arguments with the interface
        $aArgumentsPatch = $this->parseArguments($oMethod, $aArguments, $oElement->testAttribute('get-redirect'));
        
        // run method
        if ($aArgumentsPatch) $oResult = $this->runMethod($mObject, $sMethod, $aArgumentsPatch, $bStatic);
        else dspm(xt('Arguments invalides pour la méthode "%s" dans "%s"', new HTML_Strong($oElement->getName(true)), $this->getPath()->parse()), 'action/notice');
        
        // check variable
        $this->setVariableElement($oElement, $oResult);
        
        $bSubReturn = false;
        
        // run children
        if ($oElement->hasChildren()) list($oSubResult, $bSubReturn) = $this->runInterfaceList($oResult, $oElement);
        
        if ($bSubReturn) return array($oSubResult, true);
        else return array($oResult, $bReturn);
      }
    }
    
    return array($oResult, $bReturn);
  }
  
  private function loadElementArguments($oElement) {
    
    // Load arguments and remove 'em from oElement
    
    $aArguments = array(
      'assoc' => array(),
      'index' => array());
    
    // $oTempElement = clone $oElement;
    
    foreach ($oElement->getChildren() as $iKey => $oChild) {
      
      if ($oChild->isElement()) {
        
        if (!$oChild->useNamespace(SYLMA_NS_INTERFACE)) {
          
          // get @le:name or le:argument/@name
          
          if (!$sName = $oChild->getAttribute('name', SYLMA_NS_EXECUTION)) {
            
            if ($oChild->getName() == 'argument' && $oChild->useNamespace(SYLMA_NS_EXECUTION)) $sName = $oChild->getAttribute('name');
            else $sName = '';
          }
          
          $mResult = $this->buildArgument($oChild);
          $oChild->remove();
          
          if ($sName) $aArguments['assoc'][$sName] = $mResult;
          else $aArguments['index'][] = $mResult;
        }
        
      } else {
        
        $aArguments['index'][] = $this->buildArgument($oChild);
        $oChild->remove();
      }
    }
    
    //echo 'fin : '.$oElement->getName();
    return $aArguments;
  }
  
  private function buildArgumentAction($oElement) {
    
    $mResult = null;
    $bRun = false;
    
    // get the path
    
    if (!$sPath = $oElement->getAttribute('path')) {
      
      if (!$oElement->hasChildren()) {
        
        dspm(xt('Aucun chemin spécifié pour l\'action dans %s.', new HTML_Strong($this->getPath())), 'action/warning');
        
      } else {
        
        $sPath = (string) $this->buildArgument($oElement->getFirst());
        $oElement->getFirst()->remove();
      }
    }
    
    if ($sPath) {
      
      $oPath = new XML_Path($this->getAbsolutePath($sPath), null, true);
      
      if ((string) $oPath == (string) $this->getPath()) {
        
        dspm(array(xt('Récursivité dans l\'action, chemin "%s" invalide !', $oPath->parse()), $oElement->messageParse()), 'action/error');
        
      } else {
        
        $oRedirect = clone $this->getRedirect();
        
        // get arguments
        
        if ($oElement->hasChildren()) {
          
          $aArguments = $this->loadElementArguments($oElement);
          
          if ($oElement->testAttribute('send-all-arguments')) $aArguments = array_merge_recursive($aArguments, $this->getPath()->getAllArguments());
          
          $oPath->pushIndex($aArguments['index']);
          $oPath->mergeAssoc($aArguments['assoc']);
        }
        
        // build
        
        $oAction = new XML_Action($oPath, $oRedirect, $this->aProcessors);
        $mResult = $oAction->parse();
        
        // check result
        
        switch ($oAction->getStatut()) {
          
          case 'success' : break;
          case 'redirect' : 
            
            $this->setStatut('redirect');
            $this->setRedirect($mResult);
            
            $mResult = null;
            
            if (Controler::useStatut('action/report')) {
              
              dspm(xt('Redirection de %s dans %s vers %s',
                $oAction->getPath()->parse(), $this->getPath()->parse(), $mResult->getPath()), 'action/report');
            }
            
          break;
          
          default : $mResult = null; break;
        }
        
        $bRun = true;
      }
    }
    
    return array($mResult, $bRun);
  }
  
  private function buildArgumentExecution($oElement) {
    
    $bRun = false;
    $bStatic = false;
    $mResult = null;
    
    $mSubResult = null;
    $bSubReturn = false;
    
    switch ($oElement->getName(true)) {
      
      case 'argument' :
        
        if ($oElement->hasChildren()) {
          
          if ($sFormat = $oElement->getAttribute('format', SYLMA_NS_EXECUTION)) $mResult = $this->parseBaseType($sFormat, $oElement);
          else if ($oElement->countChildren() <= 1) $mResult = $this->buildArgument($oElement->getFirst());
          else dspm(xt('Argument d\'action %s invalide. Nombre d\'enfants incorrect', view($oElement)), 'action/warning');
          
        } else $mResult = null;
        
      break;
      
      case 'test-argument' :
        
        $oArgument = new XML_Element('le:get-argument', null, array(
          'keep' => 'true'), SYLMA_NS_EXECUTION);
        
        if ($sName = $oElement->getAttribute('name')) $oArgument->setAttribute('name', $sName);
        
        if ($oElement->hasChildren()) {
          
          $mArgument = $this->buildArgument($oArgument);
          
          if ((($oElement->testAttribute('value') !== false) && $mArgument) ||
            (($oElement->testAttribute('value') === false) && !$mArgument)) {
            
            $mResult = $this->buildArgument($oElement->getFirst());
          }
        }
        
      break;
      
      case 'get-argument' :
        
        $bKeep = $oElement->testAttribute('keep');
        
        if ($sName = $oElement->getAttribute('name')) {
          
          if ($this->getPath()->hasAssoc($sName)) $mResult = $this->getPath()->getAssoc($sName, ($bKeep !== false));
          else if ($oElement->testAttribute('required', false)) {
            
            dspm(xt('Argument associé %s inexistant dans %s', new HTML_Strong($sName), $this->getPath()->parse()), 'action/error');
          }
          
        } else if ($iIndex = $oElement->getAttribute('index')) $mResult = $this->getPath()->getIndex($iIndex, $bKeep);
        else $mResult = $this->getPath()->getIndex(0, $bKeep);
        
        $bRun = true;
        
      break;
      
      case 'get-settings' :
        
        $mResult = Controler::getSettings($oElement->read());
        
      break;
      
      case 'set-variable' :
        
        if (!$sName = $oElement->getAttribute('name')) $this->dspm(xt('Attribute \'name\' manquant pour %s', $oElement), 'error');
        else {
          
          $mResult = $this->buildArgument($oElement->getChildren());
          $this->setVariable($sName, $mResult);
        }
        
      break;
      
      case 'get-variable' :
        
        if (!$sVariable = $oElement->getAttribute('name')) {
          
          $this->dspm(array(t('Nom de la variable indéfini !'), $oElement->messageParse()), 'action/warning');
          
        } else {
          
          $mResult = $this->getVariable($sVariable);
          
          $bRun = true;
        }
        
      break;
      
      case 'switch' :
        
        if ($oElement->getChildren()->length < 2) $this->dspm(xt('Arguments insuffisants pour %s', $oElement), 'action/error');
        else {
          
          if ($oElement->getFirst()->getName() == 'case') $this->dspm(xt('Le premier argument ne peux pas être %s dans %s', $oElement->getFirst(), $oElement), 'action/error');
          else {
            
            $mResult = array();
            $mTest = $this->buildArgument($oElement->getFirst()->remove());
            
            foreach ($oElement->getChildren() as $oChild) {
              
              if (!$oChild->useNamespace(SYLMA_NS_EXECUTION) || !($oChild->getName() == 'case' || $oChild->getName() == 'default')) {
                
                $this->dspm(xt('Element %s interdit dans %s', $oChild, $oElement), 'action/error');
              
              } else {
                
                if ($oChild->getName() == 'default') {
                  
                  // default
                  
                  if ($oChild != $oElement->getLast()) $this->dspm(xt('%s doit être placer à la fin de %s', view($oChild), view($oElement)), 'action/error');
                  else $mResult[] = $this->buildArgument($oChild->getChildren());
                  
                } else {
                  
                  // case
                  
                  if (!$oChild->getChildren()->length) $this->dspm(xt('Arguments insuffisants pour %s dans %s', view($oChild), view($oElement)), 'error');
                  else {
                    
                    // compare values
                    if (!$mValue = $oChild->getAttribute('test')) $mValue = $this->buildArgument($oChild->getFirst()->remove());
                    
                    // if same add value
                    if ($mValue === $mTest) {
                      
                      $mResult[] = $this->buildArgument($oChild->getChildren());
                      if ($oChild->testAttribute('break', true)) break;
                    }
                  }
                }
              }
            }
          }
        }
        
      break;
      
      case 'function' :
        
        if (!$sName = $oElement->getAttribute('name')) {
          
          dspm(xt('Nom introuvable pour la fonction %s dans %s', view($oElement), $this->getPath()->parse()), 'action/error');
          
        } else {
          
          $mResult = $this->buildArgument($oElement->getChildren());
          
          switch ($sName) {
            
            case 'add-quote' : $mResult = addQuote($mResult); break;
            case 'escape-path' : $mResult = '"'.xmlize($mResult).'"'; break;
            
            default:
              
              dspm(xt('Function %s inconnue, %s dans %s', new HTML_Strong($sName), view($oElement), $this->getPath()->parse()), 'action/error');
          }
        }
        
      break;
      
      case 'interface' :
        
        if (!$sClassName = $oElement->getAttribute('class')) {
          
          dspm(array(
            xt('L\'élément %s doit spécifier une classe avec l\'attribut class', new HTML_Strong($oElement->getName())),
            new HTML_Tag('p', new HTML_Em($oElement->viewResume()))), 'action/error');
          
        } else {
          
          $oInterface = Action_Controler::getInterface($sClassName);
          
          $aArguments = array();
          
          if ($oConstruct = $oInterface->getByName('method-construct')) {
            
            $aArguments = $this->parseArguments($oConstruct, $this->loadElementArguments($oElement));
          }
          
          if ($sPath = $oInterface->readByName('file')) $sPath = $this->getAbsolutePath($sPath);
          
          $mResult = $this->buildClass($sClassName, $sPath, $aArguments);
          $bRun = true;
        }
        
      break;
      
      case 'document' :
        
        if ($oElement->hasChildren()) {
          
          $sClass = $oElement->getAttribute('class');
          
          switch ($sClass) {
            
            case 'xsl' : $mResult = new XSL_Document; break;
            case 'xml' : 
            default : $mResult = new XML_Document; break;
          }
          
          $mResult->set($this->buildArgument($oElement->getFirst()->remove()));
          $bRun = true;
          
        }/* else {
          
          $mResult = new XML_Document('root');
          foreach ($oElement->getChildren() as $oChild) $mResult->add($this->buildArgument($oChild));
        }*/
        
      break;
      
      case 'action' :
        
        list($mResult, $bRun) = $this->buildArgumentAction($oElement);
        
      break;
      
      case 'xquery' :
        
        if (!$oArgument = $this->buildArgument($oElement->getFirst()->remove())) {
          
          dspm(xt('Argument %s invalide pour la création de requête'), 'action/error');
          
        } else $mResult = new XML_XQuery($oArgument);
        
      break;
      
      case 'template' : $sClass = 'XSL_Document';
      case 'file' : 
        
        if (!isset($sClass)) $sClass = 'XML_Document';
        
        $this->replaceAttributesVariables($oElement);
        
        if (!($mPath = $oElement->getAttribute('path')) &&
          (!$oElement->hasChildren() || !($mPath = $this->buildArgument($oElement->getFirst()->remove())))) {
          
          dspm(array(
            xt('Aucun chemin spécifié pour le fichier dans %s.', new HTML_Strong($this->getPath())),
            new HTML_Tag('p', new HTML_Em($oElement->viewResume()))), 'action/warning');
          
        } else {
          
          $iMode = MODE_EXECUTION;
          
          if (($iTempMode = $oElement->getAttribute('mode')) && in_array($iTempMode, array(MODE_READ, MODE_WRITE, MODE_EXECUTION)))
            $iMode = $iTempMode; // TODO is it r'lly good
          
          if (is_string($mPath)) $mPath = $this->getAbsolutePath($mPath);
          
          $mResult = new $sClass($mPath, $iMode);
          
          $bRun = true;
        }
        
      break;
      
      case 'recall' :
        
        if ($oElement->hasChildren()) {
          
          $mResult = $this->buildArgument($oElement->getChildren());
          $mResult = $this->buildArgument($mResult);
        }
        
      break;
      
      case 'namespace' :
        
        $aNamespaces = array(
          'action' => SYLMA_NS_EXECUTION,
          'directory' => SYLMA_NS_DIRECTORY,
          'security' => SYLMA_NS_SECURITY,
          'interface' => SYLMA_NS_INTERFACE,
          'message' => SYLMA_NS_MESSAGES);
        
        if (!$sNamespace = $oElement->read()) {
          
          dspm(xt('Espace de nom introuvable dans %s', $this->getPath()), 'action/error');
          
        } else if (!array_key_exists($sNamespace, $aNamespaces)) {
          
          dspm(xt('Espace de nom %s inconnu dans %s %s', new HTML_Strong($sNamespace), $this->getPath(), $oElement->parse()), 'action/error');
          
        } else $mResult = $aNamespaces[$sNamespace];
        
      break;
      
      case 'php' :
      case 'special' : 
        
        $sSpecialName = $oElement->getAttribute('name');
        
      default :
        
        if (!isset($sSpecialName)) $sSpecialName = $oElement->getName(true);
        $aPhp = array('array', 'string', 'null', 'integer', 'int', 'boolean', 'bool');
        
        if (in_array($sSpecialName, $aPhp)) $mResult = $this->parseBaseType($sSpecialName, $oElement);
        else if ($aSpecial = Action_Controler::getSpecial($sSpecialName, $this, $this->getRedirect())) {
          
          $mResult = $aSpecial['variable'];
          if (!$oElement->hasAttribute('return')) $oElement->setAttribute('return', booltostr($aSpecial['return']));
          
          $bRun = true;
          $bStatic = $aSpecial['static'];
          
        } else {
          
          dspm(array(t('Argument d\'action incorrect, nom inconnu'), $oElement->messageParse()), 'action/error');
        }
        
      break;
    }
    
    $this->setVariableElement($oElement, $mResult);
    
    if (Controler::useStatut('action/report')) dspm(array(xt('Exécution [%s] :', view($oElement)),view($mResult, false)), 'action/report');
    
    // Run children if allowed
    
    if ($bRun && $oElement->hasChildren()) list($mSubResult, $bSubReturn) = $this->runInterfaceList($mResult, $oElement, $bStatic);
    
    // return attribute will define if main result is returned
    
    if (!$oElement->testAttribute('return', true)) $mResult = null;
    $mResult = $bSubReturn ? $mSubResult : $mResult;
    
    // msg
    
    if (Controler::useStatut('action/report') && $bSubReturn) {
      
      dspm(array(xt('%s return sub-result  :', view($oElement)), view($mSubResult, false)), 'action/report');
    }
    // Clone some attribute when element is an le:action
    /*
    if ($oElement->isElement() && $oElement->getName(true) == 'action' && $oElement->useNamespace(SYLMA_NS_EXECUTION) && is_object($mResult)) {
      
      if (($mResult instanceof XML_Document) || ($mResult instanceof XML_Element))
        $mResult->cloneAttributes($oElement, array('class', 'style', 'id'));
      else if ($mResult instanceof XML_NodeList && $mResult->length && $mResult->item(0)->isElement())
        $mResult->item(0)->cloneAttributes($oElement, array('class', 'style'));
      
    }
    */
    return $mResult;
  }
  
  public function buildArgument($oArgument) {
    
    $mResult = null;
    $sAction = 'default';
    
    if ($oArgument instanceof XML_Element) { // XML_Element
      
      if ($oArgument->useNamespace(SYLMA_NS_EXECUTION)) {
        
        /* Execution */
        
        $sAction = 'Executable';
        $mResult = $this->buildArgumentExecution($oArgument);
        
      } else if ($oArgument->useNamespace(SYLMA_NS_INTERFACE)) {
        
        /* Interface */
        
        $sAction = 'Interface';
        dspm(array(t('Aucune méthode ne peut être appellée ici !'), $oArgument->messageParse()), 'action/error');
        $mResult = null;
        
      } else if ($oProcessor = $this->getProcessor($oArgument->getNamespace())) {
        
        /* Other Processors */
        
        $sAction = 'Processus';
        
        $this->replaceAttributesVariables($oArgument);
        $mResult = $this->runProcessor($oArgument, $oProcessor);
        
      } else {
        
        /* Unknown namespace -> copy element */
        
        $sAction = 'Element';
        
        $mResult = clone $oArgument;
        $mResult->cleanChildren();
        
        $this->replaceAttributesVariables($mResult);
        
        if ($oArgument->hasChildren()) {
          
          // Avoid unuseful XML_Nodelist call
          if ($oArgument->countChildren() > 1) $oChildren = $oArgument->getChildren();
          else $oChildren = $oArgument->getFirst();
          
          $mResult->add($this->buildArgument($oChildren));
        }
      }
      
    } else if ($oArgument instanceof XML_NodeList) {
      
      $sAction = 'List';
      $oContainer = new XML_Element();
      foreach ($oArgument as $oChild) $oContainer->add($this->buildArgument($oChild));
      
      $mResult = $oContainer->getChildren();
      
    } else if ($oArgument->isText()) {
      
      $sAction = 'Text';
      //$mResult = (string) $oArgument;
      $mResult = $oArgument->getValue();
      
      if ($oArgument->getParent()->testAttribute('parse-variables', false, SYLMA_NS_EXECUTION))
        $mResult = $this->replaceVariables($mResult, true);
      
    } else if ($oArgument instanceof XML_CData) {
      
      $sAction = 'CData';
      $mResult = clone $oArgument;
      
      if ($oArgument->getParent()->testAttribute('parse-variables', false, SYLMA_NS_EXECUTION))
        $mResult = $this->replaceVariables($mResult, true);
      
    } else if ($oArgument instanceof XML_Comment) {
      
      $sAction = 'Comment';
      $mResult = clone $oArgument; // TODO : generate DOMComment
    }
    
    // msg
    
    if (Controler::useStatut('action/report')) {
      
      dspm(array(xt('Build [%s] : ', new HTML_Strong($sAction)), view($mResult, false)), 'action/report');
    }
    
    return $mResult;
  }
  
  private function replaceAttributesVariables($oElement) {
    
    // Check attributes variables calls. Format : [$myvar]
    
    foreach ($oElement->getAttributes() as $oAttribute) {
      
      if ($sValue = $this->replaceVariables($oAttribute->getValue())) $oAttribute->set($sValue);
    }
  }
  
  private function replaceVariables($sTest, $bReturn = false) {
    
    //$sValue = unxmlize($sTest);
    $sValue = $sTest;
    preg_match_all('/\[\$([\w-]+)\]/', $sValue, $aResults, PREG_OFFSET_CAPTURE);
    
    if ($aResults && $aResults[0]) {
      
      $iSeek = 0;
      
      foreach ($aResults[1] as $aResult) {
        
        $iVarLength = strlen($aResult[0]) + 3;
        $sVarValue = (string) $this->getVariable($aResult[0]);
        
        $sValue = substr($sValue, 0, $aResult[1] + $iSeek - 2) . $sVarValue . substr($sValue, $aResult[1] + $iSeek - 2 + $iVarLength);
        
        $iSeek = strlen($sVarValue) - $iVarLength;
      }
      
      //return xmlize($sValue);
      return $sValue;
    }
    
    if ($bReturn) return $sTest;
    else return null;
  }
  
  private function runProcessor($oElement, $oProcessor) {
    
    $mResult = $oProcessor->loadElement($oElement, $this);
    
    /*if ($oElement->hasElementChildren()) {
      
      if ($oProcessor->useInterface()) list($mSubResult, $bSubReturn) = $this->runInterfaceList($mResult, $oElement);
      else $mSubResult = $this->buildArgument($oElement->getChildren());
      
      if ($mResult) $mResult->add($mSubResult);
      else $mResult = $mSubResult;
    }*/
    
    // $oProcessor->unloadElement();
    
    return $mResult;
  }
  
  private function getProcessor($sUri) {
    
    return array_key_exists($sUri, $this->aProcessors) ? $this->aProcessors[$sUri] : null;
  }
  
  private function parseBaseType($sName, $oElement) {
    
    $mResult = null;
    
    switch ($sName) {
      
      case 'array' :
        
        if ($oElement->getChildren()->length == 1 && $oElement->getFirst()->isText()) {
          
          // 1 child text
          
          if (!$sSeparator = $oElement->getAttribute('separator')) $sSeparator = ',';
          $mResult = explode($sSeparator, $oElement->read());
          
        } else {
          
          // 0..n child(ren) element
          
          $mResult = array();
          
          foreach ($oElement->getChildren() as $oChild) {
            
            $mArgument = $this->buildArgument($oChild);
            
            if ($sKey = $oChild->getAttribute('key')) $mResult[$sKey] = $mArgument;
            else $mResult[] = $mArgument;
          }
        }
        
      break;
      
      case 'bool' :
      case 'boolean' :
        
        $mResult = $this->buildArgument($oElement->getFirst());
        
        if (is_string($mResult)) $mResult = strtobool($mResult, true);
        else $mResult = (bool) $mResult;
        
      break;
      case 'int' : $mResult = intval($this->buildArgument($oElement->getFirst())); break;
      case 'integer' : $mResult = intval($this->buildArgument($oElement->getFirst())); break;
      
      case 'string' :
        
        $aArguments = array();
        
        if (!$sSeparator = $oElement->getAttribute('separator')) $sSeparator = '';
        
        foreach ($oElement->getChildren() as $oChild) $aArguments[] = $this->buildArgument($oChild);
        
        if (strtobool($oElement->getAttribute('quote'))) $aArguments = addQuote($aArguments);
        
        if (count($aArguments) > 1) $mResult = implode($sSeparator, $aArguments);
        else if ($aArguments) $mResult = (string) $aArguments[0];
        
      break;
      
      case 'null' : $mResult = null; break;
      
      default : dspm(array(xt('Type \'%s\' de base inconnu !', $sName), $oElement->messageParse()), 'action/error'); break;
    }
    
    return $mResult;
  }
  
  private function parseArguments($oMethod, $aSourceArguments, $bRedirect = false) {
    
    $bAssoc = false;
    
    if ($bRedirect) $aArguments = array_merge($this->getPath()->getArgument('index'), $this->getPath()->getArgument('assoc'));
    else $aArguments = array_merge(array_val('index', $aSourceArguments, array()), array_val('assoc', $aSourceArguments, array()));
	
	// merge $_POST values
	if ($oMethod->testAttribute('use-post')) $aArguments = array_merge($aArguments, $_POST);
    
    // CALL argument
    
    $oChildren = $oMethod->getChildren();
    
    $aResultArguments = array();
    $bError = false;
    
    if ($oChildren->length == 1 && $oChildren->item(0)->getName() == 'multiple-arguments') {
      
      $oArguments = $oChildren->item(0);
      
      // Multiple arguments (undefined number)
      
      $iRequired = intval($oArguments->getAttribute('required-count'));
      
      if (!$iRequired || count($aArguments) >= $iRequired) {
        
        $aFormats = array();
        foreach($oArguments->getChildren() as $oFormat) $aFormats[] = $oFormat->read();
        
        foreach ($aArguments as $iArgument => $mArgument) {
          
          if ($mArgument !== null) {
            
            if ($this->validArgumentType($mArgument, $aFormats, $oMethod)) {
              
              $aResultArguments[] = $mArgument;
              
              // remove oRedirect argument
              if ($bRedirect) $this->getPath()->getIndex();
            }
          }
        }
        
      } else {
        
        dspm(xt('Pas assez d\'arguments dans %s!', new HTML_Strong($oMethod->getName(false))), 'action/warning');
        $bError = true;
      }
      
    } else {
      
      // Normal arguments (defined number)
      
      foreach($oChildren as $iArgument => $oChild) {
        
        $sName = $oChild->getAttribute('name');
        $bAssoc = $bExist = false;
        
        if ($sName && array_key_exists($sName, $aArguments)) {
          
          $mArgument = $aArguments[$sName];
          $bAssoc = $bExist = true;
          
        } else if (array_key_exists($iArgument, $aArguments)) {
          
          $mArgument = $aArguments[$iArgument];
          $bExist = true;
        }
        
        if ($bExist) {
          
          if ($bRedirect) {
            
            if ($bAssoc) $this->getPath()->getAssoc($sName);
            else $this->getPath()->getIndex();
          }
          
          $aFormats = array();
          
          if ($oChild->hasChildren()) foreach ($oChild->getChildren() as $oFormat) $aFormats[] = $oFormat->read();
          else if ($sFormat = $oChild->getAttribute('format')) $aFormats[] = $sFormat;
          
          $bError = !$this->validArgumentType($mArgument, $aFormats, $oMethod);
          
          if (!$bError) $aResultArguments[] = $mArgument;
          
        } else if ($oChild->testAttribute('required') !== false) {
          
          dspm(xt('L\'argument requis %s est absent dans %s',
            new HTML_Strong($oChild->getAttribute('name')),
            $this->getPath()->parse()), 'action/warning');
          
          $bError = true;
        }
      }
    }
    
    if (!$bError) {
      
      $aEvalArguments = array();
      
      // TODO array in array no more necessary due to use of Reflection
      
      foreach ($aResultArguments as $mIndex => $mArgument) $aEvalArguments[] = "\$aArguments['arguments']['$mIndex']";
      $sArguments = implode(', ', $aEvalArguments);
      
      return array(
        'string' => $sArguments,
        'arguments' => $aResultArguments,
      );
      
    }
    
    return false;
  }
  
  private function runMethod($mObject, $sMethodName, $aArguments = array(), $bStatic = false) {
    
    // Contrôle de l'existence de la méthode
    
    if (method_exists($mObject, $sMethodName) || method_exists($mObject, '__call')) {
      
      // Lancement de l'action
      $oResult = null;
      
      $sCaller = $bStatic ? '::' : '->';
      $sObject = $bStatic ? $mObject : '$mObject';
      $sArguments = $aArguments ? $aArguments['string'] : '';
      
      // TODO
      eval("\$oResult = $sObject$sCaller\$sMethodName($sArguments);");
      
      if (Controler::useStatut('action/report')) {
        
        $aDspArguments = array();
        foreach ($aArguments['arguments'] as $mArgument) $aDspArguments[] = Controler::formatResource($mArgument, false);
        
        $oArguments = new XML_NodeList($aDspArguments);
        
        if (!$bStatic) {
          
          eval("\$oObject = $sObject;");
          $mObject = Controler::formatResource($oObject);
          
        } else $mObject = $sObject;
        
        dspm(array(
        t('Evaluation : '),
        Controler::formatResource($oResult),
        " = ",
        $mObject,
        "$sCaller$sMethodName(",
        $oArguments->implode(', '),
        ");"), 'action/report');
      }
      
      return $oResult;
      
    } else dspm(xt('La méthode "%s" n\'existe pas dans la classe "%s" !', new HTML_Strong($sMethodName.'()'), get_class($mObject)), 'action/error');
    
    return null;
  }
  
  private function buildClass($sClassName, $sFile = '', $aArguments = array()) {
    
    if ($aArguments) $aArguments = $aArguments['arguments'];
    
    if ($oObject = Controler::buildClass($sClassName, $sFile, $aArguments)) {
      
      if (Controler::useStatut('action/report')) {
        
        if ($aArguments) {
          
          $aDspArguments = array();
          foreach ($aArguments as $mArgument) $aDspArguments[] = Controler::formatResource($mArgument);
          
          $oArguments = new XML_NodeList($aDspArguments);
          $sArguments = $oArguments->implode(', ');
          
        } else $sArguments = '';
        
        dspm(array(
        t('Evaluation : ')."\$oAction = new $sClassName(",
        $sArguments,
        ");"), 'action/report');
      }
      
      return $oObject;
    }
  }
  
  public function setRedirect($oRedirect) {
    
    $this->oRedirect = $oRedirect;
  }
  
  public function getRedirect() {
    
    return $this->oRedirect;
  }
  
  private function validateArgument($oChild, $iArgument) {
    
    $bRequired = ($oChild->testAttribute('required') !== false);
    $bAssoc = false;
    $bResult = true;
    //$mResult = null;
    
    if ($mKey = $oChild->getAttribute('name')) {
      
      $bAssoc = true;
      $mArgument = $this->getPath()->getAssoc($mKey, true);
      
    } else {
      
      if (!$mKey = $oChild->getAttribute('index')) $mKey = $iArgument;
      $mArgument = $this->getPath()->getIndex($mKey, true);
    }
    
    if ($bRequired && $mArgument === null) { // TODO : check if && !$mArgument is required
      
      dspm(xt('L\'argument "%s" est manquant dans %s !', new HTML_Strong($mKey), $this->getPath()->parse()), 'error');
      $bResult = false;
      
    } else {
      
      $bReplace = false;
      
      // Argument is here
      
      if ($mArgument) {
        
        // Argument has value
        
        $aFormats = array();
        
        if ($sFormat = $oChild->getAttribute('format')) {
          
          $aFormats[] = $sFormat; // TODO NS BUGS
          
        } else if ((!$aFormats = $oChild->query('xhtml:format', array('xhtml' => SYLMA_NS_XHTML))->toArray()) &&
          ($oFormat = $oChild->get('le:formats', 'le', SYLMA_NS_EXECUTION))) {
          
          $aFormats = $oFormat->getChildren()->toArray();
        }
        
        if (!$this->validArgumentType($mArgument, $aFormats, $oChild)) {
          
          dspm(xt('L\'argument "%s" est au mauvais format dans %s !', new HTML_Strong($mKey), $this->getPath()->parse()), 'error');
          $bResult = false;
          
        } else {
          
          // Argument is good format
          
          /* Validation */
          
          if (($oValidate = $oChild->getByName('validate', SYLMA_NS_EXECUTION)) && $oValidate->hasChildren()) {
            
            // pre-set argument result for variable
            
            $this->setVariableElement($oChild, $mArgument);
            
            if (!$mResult = $this->buildArgument($oValidate->getFirst())) {
              
              if ($oValidate->testAttribute('required', true)) {
                
                dspm(xt('L\'argument "%s" est invalide dans %s !', new HTML_Strong($mKey), $this->getPath()->parse()), 'action/error');
              }
              
              $bResult = false;
              
            } else {
              
              if ($oValidate->testAttribute('return')) $bReplace = true;
              
              if (Controler::useStatut('action/report')) {
                
                $sArgumentType = $bAssoc ? 'assoc' : 'index';
                dspm(xt('Argument : %s [%s]', Controler::formatResource($mArgument), new HTML_Em($sArgumentType)), 'action/report');
              }
            }
          }
        }
      }
      
      /* Default value */
      
      if (($mArgument === null || !$bResult) && ($oDefault = $oChild->get('le:default', 'le', SYLMA_NS_EXECUTION)) && $oDefault->hasChildren()) {
        
        // Argument has no value and is required
        
        if ((!$mResult = $this->buildArgument($oDefault->getFirst())) && $oDefault->testAttribute('required')) {
          
          dspm(xt('Argument "%s" valeur par défaut invalide dans %s !', new HTML_Strong($mKey), $this->getPath()->parse()), 'action/error');
          $bResult = false;
          
        } else {
          
          if ($oDefault->testAttribute('replace', true)) $bReplace = true;
          $bResult = true;
        }
      }
      
      /* Hypothetical replacement */
      
      if ($bReplace) {
        
        $bResult = true;
        $mTemp = $mArgument;
        $mArgument = $mResult;
        
        if ($bAssoc) $this->getPath()->setAssoc($mKey, $mResult);
        else $this->getPath()->setIndex($mKey, $mResult);
        
        if (Controler::useStatut('action/report')) {
          
          $sArgumentType = $bAssoc ? 'assoc' : 'index';
          dspm(xt('Argument redéfini : %s &gt; %s [%s:%s]', view($mTemp), view($mResult), $sArgumentType, $mKey), 'action/report');
        }
      }
    }
    
    return array($bResult, $mArgument);
  }
  
  private function validArgumentType(&$mArgument, $aFormats, $oElement) {
    
    if (!$aFormats) return true;
    
    if (is_object($mArgument)) {
      
      $sActualFormat = get_class($mArgument);
      foreach ($aFormats as $sFormat) if ($mArgument instanceof $sFormat) return true;
      
    } else {
      
      if (is_numeric($mArgument)) {
        
        if (is_integer($mArgument) || ctype_digit($mArgument)) {
          
          $sActualFormat = 'php-integer';
          $mArgument = intval($mArgument);
          
        } else {
          
          $sActualFormat = 'php-float';
          $mArgument = floatval($mArgument);
        }
        
      } else $sActualFormat = 'php-'.strtolower(gettype($mArgument));
      
      if (in_array($sActualFormat, $aFormats)) return true;
    }
    
    dspm(xt('L\'argument %s / %s n\'est pas du type : %s dans %s : %s',
      
      Controler::formatResource($mArgument),
      new HTML_em($sActualFormat),
      new HTML_Strong(implode(', ', $aFormats)),
      $this->getPath()->parse(),
      view($oElement)), 'action/warning');
    
    return false;
  }
  
  public function loadSettings($oSettings) {
    
    $bResult = true;
    $iArgument = 0;
    
    if ($oSettings && $oSettings->hasChildren()) {
      
      foreach ($oSettings->getChildren() as $oChild) {
        
        switch ($oChild->getName(true)) {
          
          case 'name' : break;
          case 'argument' :
            
            list($bResult, $mResult) = $this->validateArgument($oChild, $iArgument);
            
            $this->setVariableElement($oChild, $mResult);
            
            $iArgument++;
            
          break;
          case 'processor' :
            
            if ($sNamespace = $oChild->getAttribute('namespace')) {
              
              if ($sSource = $oChild->getAttribute('source')) {
                
                $oSource = new XML_Document($this->getAbsolutePath($sSource));
                $sPath = $oSource->read('//le:settings/le:processor/@path', $this->aNS);
                
              } else $sPath = $oChild->getAttribute('path');
              
              if ($sPath) {
                
                if (!array_key_exists($sNamespace, $this->aProcessors)) {
                  
                  $oAction = new XML_Element(
                    'le:action',
                    new XML_Element('le:self', null, array('return' => 'true'), SYLMA_NS_EXECUTION),
                    array('path' => $sPath), SYLMA_NS_EXECUTION);
                  
                  if (!$oResult = $this->buildArgument($oAction)) dspm(xt('Processeur %s introuvable dans %s', $sNamespace, $this->getPath()->parse()), 'action/error');
                  else {
                    
                    $oResult->startAction($this);
                    $this->aProcessors[$sNamespace] = $oResult;
                  }
                }
                
              } else dspm(xt('Processor [%s]: chemin introuvable ! %s', new HTML_Strong(t('namespace')), $oChild->messageParse()), 'action/error');
              
            } else dspm(xt('Processor : attribut %s manquant %s', new HTML_Strong(t('namespace')), $oChild->messageParse()), 'action/error');
            
          break;
          
          case 'setting' : // do nothing
          break;
          
          default :
            
            // Just run the element, used for set-variable
            if ($oChild->testAttribute('run', true)) $this->buildArgument($oChild);
        }
        
        if (!$bResult) break;
      }
      
      $oSettings->remove();
    }
    
    return $bResult;
  }
  
  /*** Infos ***/
  
  /**
   * Build action's first element for the infos box
   */
  
  public function getResume() {
    
    if (!$this->oResume) $this->oResume = new XML_Element('action', null, array('path' => $this->getPath()));
    
    return $this->oResume;
  }
  
  public function resumeQuery($sQuery) {
    
    if (array_key_exists($sQuery, $this->aQueries)) $this->aQueries[$sQuery]++;
    else $this->aQueries[$sQuery] = 1;
  }
  
  /**
   * Add a file to this action for infos box
   */
  
  public function resumeFile($oFile, $bFirstTime) {
    
    if (!$oFiles = $this->getResume()->getByName('files')) $oFiles = $this->getResume()->addNode('files');
    
    $oResume = $oFile->parseXML();
    if ($bFirstTime) $oResume->setAttribute('first-time' , 1);
    
    $oFiles->add($oResume);
  }
  
  /**
   * Add a sub-action to this action in infos box
   */
  
  public function resumeAction($oAction) {
    
    $this->aSubActions[] = $oAction->viewResume();
  }
  
  /**
   * Get the stats resume for infos box
   */
  
  public function viewResume() {
    
    $oAction = $this->getResume();
    $oStats = $oAction->addNode('stats');
    $oArguments = $oAction->addNode('arguments');
    $oVariables = $oAction->addNode('variables');
    $oQueries = $oAction->addNode('queries');
    
    // build stats
    
    $aStats = array();
    
    foreach ($this->aStats as $sName => $fValue) {
      
      $fValue = float_format($fValue);
      
      $aStats[$sName] = $oStats->addNode('stat', null, array(
        'name' => $sName,
        'value' => $fValue,
        'sub-value' => $fValue));
    }
    
    // build variables
    
    foreach ($this->aVariables as $sKey => $mVar) {
      
      $oVariables->addNode('variable', view($mVar, false), array('name' => $sKey));
    }
    
    // build arguments
    
    if ($this->oPathResume) $oAction->add($this->oPathResume);
    
    // build queries
    
    foreach ($this->aQueries as $sQuery => $iQueries) $oQueries->addNode('query', $sQuery, array('count' => $iQueries));
    
    // build sub-actions
    
    if ($this->aSubActions) {
      
      // add children
      $oSubActions = $oAction->addNode('sub-actions');
      
      foreach ($this->aSubActions as $oSubAction) $oSubActions->add($oSubAction);
      
      // evaluate stat weight with children's
      foreach ($this->aStats as $sName => $fValue) {
        
        $oSubStats = $oSubActions->query("action/stats/stat[@name='$sName']");
        
        $fResultValue = $fValue;
        
        if ($fValue) $fWeight = 1;
        else $fWeight = 0;
        
        foreach ($oSubStats as $oStat) {
          
          $fSubValue = $oStat->getAttribute('sub-value');
          
          if ($fValue) $fSubWeight = (1 / $fValue) * $fSubValue;
          else $fSubWeight = 0;
          
          $oStat->addAttributes(array(
            'weight-color' => inter_color($fSubWeight),
            'total-value' => float_format($fValue, 2)));
          
          $fWeight -= $fSubWeight;
          $fResultValue -= $fSubValue;
        }
        
        $aStats[$sName]->addAttributes(array(
          'sub-weight-color' => inter_color($fWeight),
          'value' => float_format($fResultValue)));
      }
    }
    
    return $oAction;
  }
  
  public function parse($aStats = array(), $bMessage = true) {
    
    $oResult = null;
    $bStats = false;
    
    // Load stats
    
    if (SYLMA_ACTION_STATS && Controler::getUser()->isMember('0')) {
      
      $bStats = true;
      
      if (!$aStats) {
        
        $aStats = XML_Controler::getStats();
        $aStats['time'] = microtime(true);
      }
      
      Controler::infosOpenAction($this);
      
      // add arguments
      
      if ($this->getPath()) $this->oPathResume = $this->getPath()->viewResume();
    }
    
    // begin check & parsing
    
    if ($this->isEmpty()) {
      
      if ($bMessage) dspm(xt('Action %s : document vide !', $this->getPath()), 'action/error');
      
    } else {
      
      $oRoot = $this->getRoot();
      $oDocument = new XML_Document($oRoot);
      
      if (Controler::useStatut('action/report')) {
        
        $oSeek = new HTML_Span(t('>>> Début'), array('style' => 'color: green;'));
        dspm(array(xt('%s de l\'exécution du fichier %s', $oSeek, $this->getPath()->parse()), new HTML_Hr), 'action/report');
      }
      
      switch ($oRoot->getNamespace()) {
        
        /* Execution */
        
        case SYLMA_NS_EXECUTION :
          
          switch ($oRoot->getName(true)) {
            
            // action
            
            case 'action' :
              
              if ($this->loadSettings($oDocument->getByName('settings', SYLMA_NS_EXECUTION))) {
                
                $oResult = new XML_Document('temp');
                
                $oMethod = new XML_Element('li:add', $oDocument->getRoot()->getChildren(), null, SYLMA_NS_INTERFACE);
                $this->runInterfaceMethod($oResult, $oMethod, Action_Controler::getInterface($oResult, $this->getRedirect()));
                
                if (!$oResult->isEmpty()) $oResult = $oResult->getRoot()->getChildren();
                else dspm(xt('Aucune valeur retournée pour l\'action %s', $this->getPath()), 'action/warning');
                
              } else {
                
                $this->setStatut('error');
                $this->dspm(xt('L\'action n\'a pas été exécuté', $this->getPath()->parse()), 'action/error');
              }
              
            break;
            
            case 'interface' :
              
              if (!$oSettings = $this->getByName('settings', SYLMA_NS_EXECUTION)) {
                
                $this->dspm(xt('Action %s invalide, aucuns paramètres !', new HTML_Strong($this->getPath())), 'action/warning');
                
              } else {
                
                if ($oAction = $oSettings->getByName('use-action')) {
                  
                  if (!$sPath = $oAction->getAttribute('path')) $this->dspm(xt('Chemin manquant sur %s', $oAction), 'action/error');
                  else {
                    
                    $oInterface = new XML_Document($sPath);
                    Action_Builder::buildInterface($oInterface);
                  }
                  
                } else if ($sClass = $oSettings->readByName('class', SYLMA_NS_EXECUTION)) {
                  
                  $oInterface = Action_Controler::getInterface($sClass);
                }
                
                $oSettings->remove();
                
                if ($oRoot->hasChildren()) {
                  
                  $aArguments = $this->loadElementArguments($oRoot);
                  $this->getPath()->pushIndex($aArguments['index']);
                  $this->getPath()->mergeAssoc($aArguments['assoc']);
                }
                
                if ($oInterface) {
                  
                  $oResult = $this->loadInterface($oInterface);
                  list($oSubResult, $bSubReturn) = $this->runInterfaceList($oResult, $oRoot);
                }
              }
              
            break;
            
            default :
              
              dspm(xt('L\'élément racine %s n\'est pas un élément racine valide du fichier d\'action %s ', new HTML_Strong($oRoot->getName(false)), new HTML_Strong($this->getPath())), 'action/warning');
              
            break;
          }
          
        break;
        
        /* Interface */
        
        case SYLMA_NS_INTERFACE :
          
          $oResult = $this->loadInterface($oRoot);
          
        break;
        
        default :
          
          dspm(xt('Espace de nom incorrect pour l\'action %s', new HTML_Strong($this->getPath())), 'action/warning');
          
        break;
        
      }
      
      if (Controler::useStatut('action/report')) {
        
        $oSeek = new HTML_Span(t('<<< Fin'), array('style' => 'color: red;'));
        dspm(array(xt('%s de l\'exécution du fichier %s', $oSeek,$this->getPath()->parse()), new HTML_Hr), 'action/report');
      }
      
      if (!$this->getStatut()) $this->setStatut('success');
      if (is_object($oResult) && $oResult instanceof Redirect) {
        
        $this->setStatut('redirect');
        $this->setRedirect($oResult);
      }
    }
    
    /* Processors */
    
    if ($this->aProcessors) foreach ($this->aProcessors as $oProcessor) $oProcessor->stopAction();
    
    // save stats
    
    if ($bStats) {
      
      $this->aStats['time'] = microtime(true) - $aStats['time'];
      
      foreach (XML_Controler::getStats() as $sKey => $iValue) {
        
        if (!array_key_exists($sKey, $aStats)) $aStats[$sKey] = 0;
        $this->aStats[$sKey] = $iValue - $aStats[$sKey];
      }
      
      Controler::infosCloseAction($this);
    }
    
    /* Final */
    
    switch ($this->getStatut()) {
      
      case 'redirect' :
        
        return $this->getRedirect();
        
      break;
      
      case 'success' : // Success
        
        return $oResult;
        
      break;
      
      case 'error' : // Error
        
        //dspm(xt('Action "%s" impossible, argument(s) invalide(s) !', new HTML_Strong($this->getPath())), 'error');
        if (SYLMA_ACTION_ERROR_REDIRECT) Controler::errorRedirect();
        
      break;
      
      default : // Pas de document (404)
        
        if ($this->getPath()) dspm(xt('Action "%s" impossible, document inexistant ou invalide !', $this->getPath()->parse()), 'action/warning');
        
      break;
    }
    
    return null;
  }
  
  public function dspm($mMessage, $sStatut = SYLMA_MESSAGES_DEFAULT_STAT) {
    
    return dspm(array($this->getPath(), new HTML_Tag('hr'), $mMessage), $sStatut);
  }
}

class Action_Array {
  
  private $aArray = array();
  public $length;
  protected $iIndex = 0;
  
  public function __construct($aArray) {
    
    $this->aArray = $aArray;
    $this->length = count($aArray);
  }
  
  public function item($mKey) {
    
    if (array_key_exists($mKey, $this->aArray)) return $this->aArray[$mKey];
    else return null;
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
    
    return $this->aArray[$this->iIndex];
  }
  
  public function valid() {
    
    return ($this->iIndex < count($this->aArray));
  }
}

class XML_Path {
  
  private $aArguments = array('index' => array(), 'assoc' => array());
  
  private $sPath = '';
  private $sOriginalPath = '';
  private $sSimplePath = '';
  
  private $sExtension = '';
  
  private $oFile = null;
  
  /**
   * @param string $sPath Path to look for an action
   * @param array $aArguments List of any arguments to add to the path
   * @param boolean $bParse Look for the correct file path through directories
   * @param $bArguments Use of indexed arguments (file/argument1/argument2)
   */
  
  public function __construct($sPath, $aArguments = null, $bParse = true, $bArguments = true) {
    
    // Remove arguments following '?' of type ..?arg1=val&arg2=val..
    
    $sPath = str_replace('__', '..', $sPath); // tmp until parseGet ^ available
    
    if ($iAssoc = strpos($sPath, '?')) {
      
      $sAssoc = substr($sPath, $iAssoc + 1);
      $sPath = substr($sPath, 0, $iAssoc);
      
      $aAssoc = explode('&', $sAssoc);
      
      foreach ($aAssoc as $sArgument) {
        
        $aArgument = explode('=', $sArgument);
        
        if (count($aArgument) == 1) $aArguments[] = $aArgument[0]; // index : only name
        else $aArguments[$aArgument[0]] = $aArgument[1]; // assoc : name and value
      }
    }
    
    // add assoc and index arguments
    
    if ($aArguments) {
      
      foreach ($aArguments as $sKey => $mArgument) {
        
        if (!$mArgument) {
          
          $mArgument = $sKey;
          $sKey = 0;
        }
        
        if (is_integer($sKey)) $this->aArguments['index'][] = $mArgument;
        else $this->aArguments['assoc'][$sKey] = $mArgument;
      }
    }
    
    $this->sOriginalPath = $sPath;
    $this->setPath($sPath);
    $this->sSimplePath = $sPath;
    
    if ($bParse) $this->parsePath($bArguments);
    
    // echo $sPath;
    // dsp($this->aArguments);
  }
  
  public function parsePath($bArguments = true) {
    
    global $aActionExtensions;
    
    $sResultPath = '';
    $bError = false;
    $bUseIndex = false;
    
    $oDirectory = Controler::getDirectory();
    $oFile = null;
    
    if ($this->getPath() == '/') $aPath = array();
    else {
      
      $aPath = explode('/', $this->getPath());
      array_shift($aPath);
    }
    
    do {
      
      $sSubPath = $aPath ? $aPath[0] : '.';
      
      if (!$oSubDirectory = $oDirectory->getDirectory($sSubPath)) {
        
        if (!$bArguments) $bError = true;
        
        // look for executable files with $aActionExtensions array of executable extensions
        
        foreach ($aActionExtensions as $sExtension) {
          
          if ($oFile = $oDirectory->getFile($sSubPath.$sExtension, false)) {
            
            $bError = false;
            break;
          }
        }
        
        if ($bError) dspm(xt('Aucun fichier correspondant à %s', new HTML_Strong($sSubPath)), 'action/warning');
        
      } else $oDirectory = $oSubDirectory;
      
      if (!$oFile && (!$aPath || !$oSubDirectory)) {
        
        if (($oFile = $oDirectory->getFile('index.eml')) || ($oFile = $oDirectory->getFile('index.iml'))) $bUseIndex = true;
        else if ($oDirectory->checkRights(MODE_EXECUTION)) {
          
          $bError = true;
          dspm(xt('Pas de fichier index dans "%s"', new HTML_Strong((string) $oDirectory)), 'action/warning');
          
        } else {
          
          $bError = true;
          dspm(xt('Le répertoire "%s" ne peut pas être listé, droits insuffisants', new HTML_Strong($oDirectory)), 'action/warning');
        }
        
      } else array_shift($aPath);
      
    } while (!$oFile && !$bError);
    
    if (!$bError) {
      
      if ($bUseIndex) $this->sOriginalPath = (string) $oFile->getParent();
      else $this->sOriginalPath = (string) $oFile;
      
      // if ($sExtension = $this->getExtension()) $this->sOriginalPath .= '.'.$sExtension;
      
      // remove empty arguments
      
      $aTempPath = $aPath;
      $aPath = array();
      
      foreach ($aTempPath as $sValue) if ($sValue) $aPath[] = $sValue;
      
      // push final values
      
      $this->setFile($oFile);
      $this->pushIndex($aPath);
      $this->setPath($oFile);
      
      $this->sSimplePath = $oFile->getActionPath().'/'.$this->getStringIndex(false); // TODO add assoc
      
    } else $this->setPath('');
  }
  
  public function parseExtension($bRemove) {
    
    $sPath = $this->getPath();
    
    preg_match('/\.(\w+)$/', $sPath, $aResult, PREG_OFFSET_CAPTURE);
    
    if (count($aResult) == 2 && ($sExtension = $aResult[1][0])) {
      
      $iExtension = $aResult[1][1];
      if ($bRemove) $this->setPath(substr($sPath, 0, $iExtension - 1).substr($sPath, $iExtension + strlen($sExtension)));
      
      $this->sExtension = $sExtension;
    }
    
    return $this->getExtension();
  }
  
  public function getDirectory() {
    
    if ($this->getFile()) return $this->getFile()->getParent();
    else return null;
  }
  
  public function getFile() {
    
    return $this->oFile;
  }
  
  public function setFile(XML_File $oFile) {
    
    $this->oFile = $oFile;
  }
  
  public function setPath($mPath) {
    
    $this->sPath = (string) $mPath;
  }
  
  public function getActionPath() {
    
    return $this->getFile()->getActionPath();
  }
  
  public function getSimplePath() {
    
    return $this->sSimplePath;
  }
  
  public function getOriginalPath() {
    
    return $this->sOriginalPath;
  }
  
  public function isValid() {
    
    return (bool) $this->getPath();
  }
  
  public function getPath() {
    
    return $this->sPath;
  }
  
  public function getExtension() {
    
    return $this->sExtension;
  }
  
  public function setArgument($sArgument, $aArgument = array()) {
    
    if (is_array($aArgument)) $this->aArguments[$sArgument] = $aArgument;
    else dspm(xt('Liste d\'argument invalide, ce n\'est pas un tableau'), 'action/error');
  }
  
  public function getAllArguments() {
    
    return $this->aArguments;
  }
  
  public function getArgument($sArgument) {
    
    if (array_key_exists($sArgument, $this->aArguments)) return $this->aArguments[$sArgument];
    else return null;
  }
  
  public function shiftIndex($mArguments) {
    
    if (is_array($mArguments)) $this->aArguments['index'] = array_merge($mArguments, $this->aArguments['index']);
    else array_unshift($mArguments, $this->aArguments['index']);
  }
  
  public function pushIndex($mArguments) {
    
    if (is_array($mArguments)) $this->aArguments['index'] = array_merge($this->aArguments['index'], $mArguments);
    else array_push($this->aArguments['index'], $mArguments);
  }
  
  private function setKey($sArray, $sKey, $mValue) {
    
    $this->aArguments[$sArray][$sKey] = $mValue;
    //else if (array_key_exists($sKey, $this->aArguments[$sArray])) unset($this->aArguments[$sArray][$sKey]);
  }
  
  public function setIndex($iKey, $mValue = '') {
    
    $this->setKey('index', $iKey, $mValue);
    if ($mValue) $this->aArguments['index'] = array_values($this->aArguments['index']);
  }
  
  public function setAssoc($sKey, $mValue = '') {
    
    $this->setKey('assoc', $sKey, $mValue);
  }
  
  public function mergeAssoc($aArguments) {
    
    $this->aArguments['assoc'] = array_merge($this->aArguments['assoc'], $aArguments);
  }
  
  public function getStringIndex($bRemove = true) {
    
    $aIndex = $this->aArguments['index'];
    if ($bRemove) $this->aArguments['index'] = array();
    
    return implode('/', $aIndex);
  }
  
  public function getIndex($iKey = 0, $bKeep = false) {
    
    $mResult = $this->getKey('index', $iKey, $bKeep);
    if ($mResult !== null) $this->aArguments['index'] = array_merge($this->aArguments['index']);
    
    return $mResult;
  }
  
  public function hasAssoc($sKey) {
    
    return array_key_exists($sKey, $this->aArguments['assoc']);
  }
  
  public function getAssoc($sKey, $bKeep = false) {
    
    return $this->getKey('assoc', $sKey, $bKeep);
  }
  
  private function getKey($sArray, $mKey, $bKeep) {
    
    if (array_key_exists($mKey, $this->aArguments[$sArray])) {
      
      $mResult = $this->aArguments[$sArray][$mKey];
      if (!$bKeep) unset($this->aArguments[$sArray][$mKey]);
      
      return $mResult;
    }
    
    return null;
  }
  
  public function viewResume() {
    
    $nPath = new XML_Element('path');
    
    if ($this->aArguments['index']) {
      
      foreach ($this->aArguments['index'] as $iKey => $mArgument) $nPath->addNode('argument', view($mArgument, false), array('index' => $iKey));
    }
    
    if ($this->aArguments['assoc']) {
      
      foreach ($this->aArguments['assoc'] as $sKey => $mArgument) $nPath->addNode('argument', view($mArgument, false), array('name' => $sKey));
    }
    
    return $nPath;
  }
  
  public function parse() {
    
    $sPath = (string) $this;
    return new HTML_A(SYLMA_PATH_EDITOR.'?path='.$sPath, $sPath);
  }
  
  public function __toString() {
    
    return $this->getPath();
  }
}

