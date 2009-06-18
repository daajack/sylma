<?php

class XML_Action extends XML_Document {
  
  private $sPath = '';
  private $sName = '';
  private $oRedirect = null;
  private $iStatut = 0;
  
  public function __construct($mPath, $oRedirect = null) {
    
    if ($mPath instanceof XML_Path) $this->oPath = $mPath;
    else $this->oPath = new XML_Path($mPath, false);
    
    if (!$oRedirect) $oRedirect = new Redirect;
    
    $this->setRedirect($oRedirect);
    
    parent::__construct((string) $this->getPath(), MODE_EXECUTION);
  }
  
  private function getDirectory() {
    
    $sParent = $this->getPath()->getFile()->getParent();
    
    $sParent = ($sParent == '/') ? $sParent : $sParent.'/';
    
    return $sParent;
  }
  
  public function getPath() {
    
    return $this->oPath;
  }
  
  private function getStatut() {
    
    return $this->iStatut;
  }
  
  private function setStatut($iStatut) {
    
    $this->iStatut = $iStatut;
  }
  
  private function getAbsolutePath($sPath) {
    
    if ($sPath{0} == '/') return $sPath;
    else return $this->getDirectory().$sPath;
  }
  
  private function loadInterface($oInterface) {
    
    $oResult = null;
    $sMethod = '';
    $aArguments = array();
    
    $sClassName = $oInterface->read('ns:name');
    $sFile = $this->getAbsolutePath($oInterface->read('ns:file'));
    
    if ($oConstruct = $oInterface->get('ns:method-construct')) {
      
      if ($oConstruct->hasChildren()) {
        
        if ($this->getPath()->getArgument('index')) $aArguments = $this->parseArguments($oConstruct, array(), 'index');
        
        if (!$aArguments && ($oConstruct->query('ns:argument[@required="false"]')->length != $oConstruct->query('ns:argument')->length)) {
          
          Action_Controler::addMessage('Erreur dans les arguments, impossible de construire l\'objet', 'warning');
          return null;
        }
      }
    }
    
    $oObject = $this->buildClass($sClassName, $sFile, $aArguments);
    
    if (($sMethod = $this->getPath()->getIndex()) && is_string($sMethod)) {
      
      $oElement = new XML_Element('li:'.$sMethod, null, array('get-redirect' => 'index'), NS_INTERFACE);
      list($oSubResult, $bSubResult) = $this->runInterfaceMethod($oObject, $oElement, $this);
      
      if ($bSubResult) $oResult = $oSubResult;
      else $oResult = $oObject;
      
    } else $oResult = $oObject;
    
    return $oResult;
  }
  
  private function runInterfaceList($mObject, $oElement, $bStatic = false) {
    
    $mResult = null;
    $aResults = array();
    
    if (is_array($mObject)) $mObject = new Action_Array($mObject);
    
    if (is_object($mObject) || $bStatic) $oInterface = Action_Controler::getInterface($mObject);
    else $oInterface = null;
    
    foreach ($oElement->getChildren() as $oChild) {
      
      if ($oChild->isElement()) {
        
        if ($oChild->getNamespace() != NS_INTERFACE) Action_Controler::addMessage(xt('runInterfaceList() : Cet élément n\'est pas permis : %s', $oElement->viewResume()), 'warning');
        else {
          
          list($mResult, $bReturn) = $this->runInterfaceMethod($mObject, $oChild, $oInterface, $bStatic);
          if ($bReturn) $aResults[] = $mResult;
        }
        
      } else Action_Controler::addMessage(xt('runInterfaceList() : Noeud texte impossible ici : "%s"', new HTML_Strong($oChild)), 'warning');
    }
    
    if ($aResults) {
      if (count($aResults) == 1) $mResult = $aResults[0];
      else $mResult = new XML_NodeList($aResults);
    }
    
    return array($mResult, ($aResults));
  }
  
  private function runInterfaceMethod($mObject, $oElement, $oInterface, $bStatic = false) {
    
    $oResult = null;
    $bReturn = false;
    $sActionMethod = $oElement->getName(true);
    
    if (!$oInterface) {
      
      $aArguments = array();
      
      if ($sActionMethod == 'if') {
        
        if ($mObject) foreach ($oElement->getChildren() as $oChild)
          $aArguments[] = $this->buildArgument($oChild);
        
      } else if ($sActionMethod == 'if-not') {
        
        if (!$mObject) foreach ($oElement->getChildren() as $oChild)
          $aArguments[] = $this->buildArgument($oChild);
      }
      
      if ($oElement->testAttribute('return')) {
        
        foreach ($aArguments as $mArgument) if ($mArgument) $oResult = $mArgument;
        if ($oResult) $bReturn = true;
      }
      
      
    } else {
      
      if (!$oMethod = $oInterface->get("ns:method[@path='$sActionMethod']")) {
        
        Action_Controler::addMessage(xt('Méthode "%s" inexistante dans l\'interface "%s"', new HTML_Strong($oElement->getName(true)), new HTML_Strong($oInterface->read('ns:name'))), 'warning');
        
      } else {
        
        $bReturn = $oElement->testAttribute('return');
        if ($bReturn === null) $bReturn = $oMethod->testAttribute('return-default');
        if ($bReturn === null) $bReturn = false;
        
        $aArguments = $this->loadElementArguments($oElement);
        
        if (!$sMethod = $oMethod->getAttribute('name')) {
          
          Action_Controler::addMessage('Interface invalide, attribut \'nom\' manquant', 'error');
          
        } else {
          
          $aArgumentsPatch = $this->parseArguments($oMethod, $aArguments, $oElement->getAttribute('get-redirect'));
          
          if ($aArgumentsPatch) $oResult = $this->runMethod($mObject, $sMethod, $aArgumentsPatch, $bStatic);
          else Action_Controler::addMessage(xt('Arguments invalides pour la méthode "%s"', new HTML_Strong($oElement->getName(true))), 'notice');
          
          // if (!isset($oResult) && Action_Controler::useStatut('report')) Action_Controler::addMessage(xt('Aucun résultat pour l\'élément : %s', $oElement->viewResume()), 'report');
          
          $bSubReturn = false;
          
          if ($oElement->hasChildren()) list($oSubResult, $bSubReturn) = $this->runInterfaceList($oResult, $oElement);
          
          if ($bSubReturn) return array($oSubResult, true);
          else return array($oResult, $bReturn);
        }
      }
    }
    
    return array($oResult, $bReturn);
  }
  
  private function loadElementArguments($oElement) {
    
    // Load arguments and remove 'em from oElement
    
    $aArguments = array(
      'assoc' => array(),
      'index' => array(),
      'all' => array(),
      'element' =>  array());
    
    foreach ($oElement->getChildren() as $oChild) {
      
      // !li || li:argument
      
      if ($oChild->isElement()) {
        
        if (!$oChild->useNamespace(NS_INTERFACE) || $oChild->getName(true) == 'argument') {
          
          $mResult = $this->buildArgument($oChild);
          
          if ($sName = $oChild->getAttribute('name', NS_INTERFACE)) $aArguments['assoc'][$sName] = $mResult;
          else $aArguments['index'][] = $mResult;
          
          $aArguments['element'][] = $oChild->remove();
          $aArguments['all'][] = $mResult;
        }
        
      } else {
        
        $aArguments['index'][] = $aArguments['all'][] = (string) $oChild;
        $aArguments['element'][] = $oChild->remove();
      }
    }
    
    return $aArguments;
  }
  
  private function buildArgument($oElement) {
    
    $mResult = null;
    $mSubResult = null;
    
    $bSubReturn = false;
    
    if ($oElement->isText()) {
      
      $mResult = (string) $oElement;
      
    } else { // XML_Element
      
      if ($oElement->useNamespace(NS_EXECUTION)) {
        
        switch ($oElement->getName(true)) {
          
          case 'direct-action' :
            
            $bParse = false;
            
          case 'action' :
            
            if (!isset($bDirect)) $bParse = true;
            
            if (!$sPath = $oElement->getAttribute('path')) {
              
              if (!$oElement->hasChildren()) {
                
                Action_Controler::addMessage(xt('Aucun chemin spécifié pour l\'action dans %s.', new HTML_Strong($this->getPath())), 'warning');
                
              } else {
                
                $sPath = (string) $this->buildArgument($oElement->getFirst());
                $oElement->getFirst()->remove();
              }
            }
            
            if ($sPath) {
              
              $oPath = new XML_Path($this->getAbsolutePath($sPath), $bParse);
              
              $oRedirect = clone $this->getRedirect();
              
              if ($oElement->hasChildren()) {
                
                $aArguments = $this->loadElementArguments($oElement);
                $oPath->pushIndex($aArguments['index']);
                $oPath->mergeAssoc($aArguments['assoc']);
              }
              
              $oAction = new XML_Action($oPath, $oRedirect);
              $mResult = $oAction->parse();
              
              if ($mResult instanceof Redirect) {
                
                $this->setStatut(1);
                $this->setRedirect($mResult);
                $mResult = null;
              }
              
              if ($oElement->hasChildren()) list($mSubResult, $bSubReturn) = $this->runInterfaceList($mResult, $oElement);
            }
            //runInterfaceMethod($mObject, new XML_Element('method-construct', $oElement->getChildren(), Action_Controler::getInterface($mObject);, $oRedirect, $bStatic = false)
            // TODO relative path
            
          break;
          
          case 'file' : 
            
            $mResult = new XML_Document($this->getAbsolutePath($oElement->getAttribute('path')), MODE_EXECUTION);
            
            // TODO relative path
            list($mSubResult, $bSubReturn) = $this->runInterfaceList($mResult, $oElement);
            
          break;
          
          case 'php' :
          case 'special' : 
            
            $sSpecialName = $oElement->getAttribute('name');
            
          default :
            
            if (!isset($sSpecialName)) $sSpecialName = $oElement->getName(true);
            $aPhp = array('array', 'string', 'null', 'integer');
            
            if (in_array($sSpecialName, $aPhp)) $mResult = $this->parseBaseType($sSpecialName, $oElement);
            else if ($mSpecial = Action_Controler::getSpecial($sSpecialName, $this, $this->getRedirect())) {
              
              if (!$oElement->testAttribute('return')) $mResult = '';
              else $mResult = $mSpecial;
              
              list($mSubResult, $bSubReturn) = $this->runInterfaceList($mSpecial, $oElement, is_string($mSpecial));
            }
            
          break;
          
        }
      } else if ($oElement->useNamespace(NS_INTERFACE) && $oElement->getName(true) == 'argument') {
        
        $mResult = $this->buildArgument($oElement->getFirst());
        
      } else {
        
        foreach ($oElement->getChildren() as $oChild) {
          
          if ($oChild->isElement()) {
            
            $oResult = $this->buildArgument($oChild);
            $oChild->replace($oResult);
          }
        }
        
        $mResult = $oElement;
      }
    }
    
    if ($bSubReturn) return $mSubResult;
    return $mResult;
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
      
      case 'boolean' : $mArgument = ($this->buildArgument($oElement->getFirst())); break;
      case 'integer' : $mArgument = intval($this->buildArgument($oElement->getFirst())); break;
      
      case 'string' :
        
        $aArguments = array();
        
        if (!$sSeparator = $oElement->getAttribute('separator')) $sSeparator = '';
        
        foreach ($oElement->getChildren() as $oChild) $aArguments[] = $this->buildArgument($oChild);
        
        if (count($aArguments) > 1) $mResult = implode($sSeparator, $aArguments);
        else if ($aArguments) $mResult = (string) $aArguments[0];
        
      break;
      
      case 'null' :
      default : $mResult = null;
      break;
    }
    
    return $mResult;
  }
  
  private function parseArguments($oMethod, $aArguments, $sRedirect) {
    
    $bAssoc = false;
    
    // Mix Redirect Arguments with Executable arguments
    
    if ($sRedirect) {
      
      if (in_array($sRedirect, array('assoc', 'index'))) {
        
        if ($sRedirect == 'assoc') $bAssoc = true;
        $aArguments[$sRedirect] = $this->getPath()->getArgument($sRedirect);
        
      } else {
        
        Action_Controler::addMessage(xt('Type %s incorrect pour la récupération d\'argument Redirect dans %s', new HTML_Strong($sRedirect), new HTML_Em($oMethod->viewResume())), 'warning');
        $sRedirect = '';
      }
    }
    
    // CALL argument
    
    $oChildren = $oMethod->getChildren();
    
    $aResultArguments = array();
    
    if ($bAssoc) $aArguments = $aArguments['assoc'];
    else $aArguments = array_values($aArguments['index']);
    
    $bError = false;
    
    if ($oChildren->length == 1 && $oChildren->item(0)->getName() == 'multiple-arguments') {
      
      $oArguments = $oChildren->item(0);
      
      // Multiple arguments (undefined number)
      
      $iRequired = intval($oArguments->getAttribute('required-count'));
      
      if (count($aArguments) >= $iRequired) {
        
        $aFormats = array();
        foreach($oArguments->getChildren() as $oFormat) $aFormats[] = $oFormat->read();
        
        foreach ($aArguments as $iArgument => $mArgument) {
          
          if ($mArgument !== null) {
            
            if ($this->validArgumentType($mArgument, $aFormats, $oMethod)) {
              
              $aResultArguments[] = $mArgument;
              
              // remove oRedirect argument
              if ($sRedirect == 'index') $this->getPath()->getIndex();
            }
          }
        }
        
      } else {
        
        Action_Controler::addMessage(xt('Pas assez d\'arguments dans %s!', new HTML_Strong($oMethod->getName())), 'warning');
        $bError = true;
      }
      
    } else {
      
      // Normal arguments (defined number)
      
      foreach($oChildren as $mIndex => $oChild) {
        
        $sName = $oChild->getAttribute('name');
        
        if ($bAssoc) $sKey = $sName;
        else $sKey = $mIndex;
        
        if (array_key_exists($sKey, $aArguments)) {
          
          $mArgument = $aArguments[$sKey];
          
          $aFormats = array();
          
          if ($oChild->hasChildren()) foreach ($oChild->getChildren() as $oFormat) $aFormats[] = $oFormat->read();
          else $aFormats[] = $oChild->getAttribute('format');
          
          $bError = !$this->validArgumentType($mArgument, $aFormats, $oChild);
          
          if (!$bError) {
            
            $aResultArguments[] = $mArgument;
            
            if ($sRedirect) {
              
              if ($bAssoc) $this->getPath()->getAssoc($sKey);
              else $this->getPath()->getIndex();
            }
          }
          
        } else if ($oChild->testAttribute('required') !== false) {
          
          Action_Controler::addMessage(xt('L\'argument requis %s est absent',
            new HTML_Strong($oChild->getAttribute('name'))), 'warning');
          
          $bError = true;
        }
      }
    }
    
    if (!$bError) {
      
      $aEvalArguments = array();
      
      foreach ($aResultArguments as $mIndex => $mArgument) $aEvalArguments[] = "\$aArguments['arguments']['$mIndex']";
      $sArguments = implode(', ', $aEvalArguments);
      
      return array(
        'string' => $sArguments,
        'arguments' => $aResultArguments,
      );
      
    }
    
    return false;
  }
  
  private function validArgumentType($mArgument, $aFormats, $oElement) {
    
    if (is_object($mArgument)) {
      
      $sActualFormat = get_class($mArgument);
      foreach ($aFormats as $sFormat) if ($mArgument instanceof $sFormat) return true;
      
    } else {
      
      if (is_numeric($mArgument)) {
        
        if (ctype_digit($mArgument)) $sActualFormat = 'php-integer';
        else $sActualFormat = 'php-float';
        
      } else $sActualFormat = 'php-'.strtolower(gettype($mArgument));
      
      if (in_array($sActualFormat, $aFormats)) return true;
    }
    
    Action_Controler::addMessage(array(
      xt('L\'argument "%s" n\'est pas dans la liste "%s"',
        new HTML_Strong($sActualFormat),
        new HTML_Strong(implode(', ', $aFormats))),
        new HTML_Tag('p', array(
        new HTML_Strong(t('Méthode').' : '),
        new HTML_Em($oElement->getParent()->viewResume(150, true)),
        new HTML_br,
        new HTML_Strong(t('Argument').' : '),
        new HTML_Em($oElement->viewResume(150, true))))), 'warning');
    
    return false;
  }
  
  private function runMethod($mObject, $sMethodName, $aArguments = array(), $bStatic = false) {
    
    // Contrôle de l'existence de la méthode
    
    if (method_exists($mObject, $sMethodName)) {
      
      // Lancement de l'action
      $oResult = null;
      
      $sCaller = $bStatic ? '::' : '->';
      $sObject = $bStatic ? $mObject : '$mObject';
      $sArguments = $aArguments ? $aArguments['string'] : '';
      
      eval("\$oResult = $sObject$sCaller\$sMethodName($sArguments);");
      if (Action_Controler::useStatut('report')) Action_Controler::addMessage(t('Evaluation : ')."$sObject$sCaller$sMethodName(".count($aArguments['arguments']).");", 'report');
      
      return $oResult;
      
    } else Action_Controler::addMessage(xt('La méthode "%s" n\'existe pas dans la classe "%s" !', new HTML_Strong($sMethodName.'()'), get_class($mObject)), 'error');
    
    return null;
  }
  
  private function buildClass($sClassName, $sFile = '', $aArguments = array()) {
    
    if ($sFile) {
      
      // Include du fichier
      
      $sFile = MAIN_DIRECTORY.$sFile;
      
      if (file_exists($sFile)) require_once($sFile);
      else Action_Controler::addMessage(xt('Fichier "%s" introuvable !', new HTML_Strong($sFile)), 'warning');
    }
    
    // Contrôle de l'existence de la classe
    
    if (Controler::isAdmin()) $sError = xt('Action impossible (la classe "%s" n\'existe pas) !', new HTML_Strong($sClassName));
    else $sError = t('Page introuvable, veuillez corriger l\'adresse !');
    
    // if (!class_exists($sClassName)) Controler::errorRedirect($sError);
    if (class_exists($sClassName)) {
      
      $sAction = $aArguments ? $aArguments['string'] : '';
      $iArguments = $aArguments ? count($aArguments['arguments']) : 0;
      // Création de la classe
      
      eval("\$oAction = new \$sClassName($sAction);");
      if (Action_Controler::useStatut('report')) Action_Controler::addMessage(t('Evaluation : ')."\$oAction = new $sClassName($iArguments);", 'report');
      
      return $oAction;
      
    } else Action_Controler::addMessage($sError, 'notice');
  }
  
  public function setRedirect($oRedirect) {
    
    $this->oRedirect = $oRedirect;
  }
  
  public function getRedirect() {
    
    return $this->oRedirect;
  }
  
  public function parse() {
    
    $oResult = null;
    
    if ($this && !$this->isEmpty()) {
      
      $oRoot = $this->getRoot();
      $oDocument = new XML_Document($oRoot);
      
      switch ($oRoot->getNamespace()) {
        
        /* Execution */
        
        case NS_EXECUTION : 
          
          switch ($oRoot->getName(true)) {
            
            // action
            
            case 'action' :
              
              $oResult = new XML_Document('temp');
              
              if ($oSettings = $oDocument->get('le:settings', 'le', NS_EXECUTION)) $oSettings->remove();
              
              $oMethod = new XML_Element('li:add', $oDocument->getRoot()->getChildren(), null, NS_INTERFACE);
              
              $this->runInterfaceMethod($oResult, $oMethod, Action_Controler::getInterface($oResult, $this->getRedirect()));
              
              if (!$oResult->isEmpty()) $oResult = $oResult->getRoot()->getChildren();
              
            break;
            
            case 'interface' :
              
              if (!$oSettings = $this->get('le:settings', 'le', NS_EXECUTION)) {
                
                Action_Controler::addMessage(xt('Action %s invalide, aucuns paramètres !', new HTML_Strong($this->getPath())), 'warning');
                
              } else {
                
                $sClass = $oSettings->read('le:class', 'le', NS_EXECUTION);
                $oSettings->remove();
                
                if ($oRoot->hasChildren()) {
                  
                  $aArguments = $this->loadElementArguments($oRoot);
                  $this->getPath()->pushIndex($aArguments['index']);
                  $this->getPath()->mergeAssoc($aArguments['assoc']);
                }
                
                if ($oInterface = Action_Controler::getInterface($sClass)) {
                  
                  $oResult = $this->loadInterface($oInterface);
                  list($oSubResult, $bSubReturn) = $this->runInterfaceList($oResult, $oRoot);
                }
              }
              
            break;
            
            default :
              
              Action_Controler::addMessage(xt('L\'élément racine %s n\'est pas un élément racine valide du fichier d\'action %s ', new HTML_Strong($oRoot->getName()), new HTML_Strong($this->getPath())), 'warning');
              
            break;
          }
          
        break;
        
        /* Interface */
        
        case NS_INTERFACE :
          
          $oResult = $this->loadInterface($oRoot);
          
        break;
        
        default :
          
          Action_Controler::addMessage(xt('Ceci n\'est pas un interface valide %s', new HTML_Strong($oRoot->getName())), 'warning');
          
        break;

      }
      
      if (!$this->getStatut()) $this->setStatut(2);
    }
    
    switch ($this->getStatut()) {
      
      case 1 : // Redirect
        
        return $this->getRedirect();
        
      break;
      
      case 2 : // Success
        
        return $oResult;
        
      break;
      
      case 0 : // Pas de document (404)
      default :
        
        Action_Controler::addMessage(xt('Action "%s" impossible, pas de document !', new HTML_Strong($this->getPath())), 'warning');
        return 'Pas de document !!!';
        
      break;
    }
  }
}

class HTML_Action extends HTML_Tag {
  
  public function __construct() {
    
    parent::__construct('div');
    $this->addClass('action');
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
  
  private $sExtension = '';
  private $sOriginalPath = '';
  private $oFile = null;
  
  public function __construct($sPath, $bParse = true, $aArguments = array()) {
    
    // Remove arguments following '?' of type ..?arg1=val&arg2=val..
    
    if ($iAssoc = strpos($sPath, '?')) {
      
      $sAssoc = substr($sPath, $iAssoc + 1);
      $sPath = substr($sPath, 0, $iAssoc);
      
      $aAssoc = explode('&', $sAssoc);
      
      foreach ($aAssoc as $sArgument) {
        
        $aArgument = explode('=', $sArgument);
        
        if (count($aArgument) == 1) $aArguments[] = $aArgument[0]; // only name
        else $aArguments[$aArgument[0]] = $aArgument[1]; // name and value
      }
    }
    
    foreach ($aArguments as $sKey => $sArgument) {
      
      $aArgument = explode('=', $sArgument);
      
      if (!$sArgument) {
        
        $sArgument = $sKey;
        $sKey = 0;
      }
      
      if (is_integer($sKey)) $this->aArguments['index'][] = $sArgument;
      else $this->aArguments['assoc'][$sKey] = $sArgument;
    }
    
    $this->sOriginalPath = $sPath;
    $this->setPath($sPath);
    if ($bParse) $this->parsePath();
  }
  
  public function parsePath() {
    
    global $aActionExtensions;
    
    $sResultPath = '';
    $bError = false;
    $bUseIndex = true;
    
    $oDirectory = Controler::getDirectory();
    $oFile = null;
    
    $aPath = explode('/', $this->getPath());
    
    array_shift($aPath);
    
    do {
      
      $sSubPath = $aPath ? $aPath[0] : '.';
      
      if (!$oSubDirectory = $oDirectory->getDirectory($sSubPath)) {
        
        foreach ($aActionExtensions as $sExtension) if ($oFile = $oDirectory->getFile($sSubPath.$sExtension, false)) break;
        
      } else $oDirectory = $oSubDirectory;
      
      if (!$oFile && (!$aPath || !$oSubDirectory)) {
        
        if ($oFile = $oDirectory->getFile('index.eml')) $bUseIndex = true;
        else if ($oDirectory->checkRights(1)) {
          
          $bError = true;
          Action_Controler::addMessage(xt('Le listing de répertoire n\'est pas encore possible :| : "%s"', new HTML_Strong($oDirectory)), 'warning');
          
        } else {
          
          $bError = true;
          Action_Controler::addMessage(xt('Le répertoire "%s" ne peut pas être listé, droits insuffisants', new HTML_Strong($oDirectory)), 'warning');
        }
        
      } else array_shift($aPath);
      
    } while (!$oFile && !$bError);
    
    if (!$bError) {
      
      if ($bUseIndex) $this->sOriginalPath = (string) $oFile->getParent();
      else $this->sOriginalPath = (string) $oFile;
      
      if ($sExtension = $this->getExtension()) $this->sOriginalPath .= '.'.$sExtension;
      
      $this->setFile($oFile);
      $this->pushIndex($aPath);
      $this->setPath($oFile);
      
    } else $this->setPath('');
  }
  
  public function parseExtension($bRemove) {
    
    $sPath = $this->getPath();
    
    preg_match('/\.(\w+)/', $sPath, $aResult, PREG_OFFSET_CAPTURE);
    
    if (count($aResult) == 2 && ($sExtension = $aResult[1][0])) {
      
      $iExtension = $aResult[1][1];
      if ($bRemove) $this->setPath(substr($sPath, 0, $iExtension - 1).substr($sPath, $iExtension + strlen($sExtension)));
      
      $this->sExtension = $sExtension;
    }
    
    return $this->getExtension();
  }
  
  public function getFile() {
    
    return $this->oFile;
  }
  
  public function setFile($oFile) {
    
    $this->oFile = $oFile;
  }
  
  public function setPath($sPath) {
    
    $this->sPath = (string) $sPath;
  }
  
  public function getOriginalPath() {
    
    return $this->sOriginalPath;
  }
  
  public function getPath() {
    
    return $this->sPath;
  }
  
  public function getExtension() {
    
    return $this->sExtension;
  }
  
  public function setArgument($sArgument, $aArgument = array()) {
    
    if (is_array($aArgument)) $this->aArguments[$sArgument] = $aArgument;
    else Action_Controler::addMessage(xt('Liste d\'argument invalide, ce n\'est pas un tableau'), 'error');
  }
  
  public function getArgument($sArgument = null) {
    
    if (!$sArgument) return $this->aArguments;
    else {
      
      if (!array_key_exists($sArgument, $this->aArguments)) $this->aArguments[$sArgument] = array();
      return $this->aArguments[$sArgument];
    }
  }
  
  public function shiftIndex($mArguments) {
    
    if (is_array($mArguments)) $this->aArguments['index'] = array_merge($mArguments, $this->aArguments['index']);
    else array_unshift($mArguments, $this->aArguments['index']);
  }
  
  public function pushIndex($mArguments) {
    
    if (is_array($mArguments)) $this->aArguments['index'] = array_merge($this->aArguments['index'], $mArguments);
    else array_push($this->aArguments['index'], $mArguments);
  }
  
  public function setAssoc($sKey, $sName = '') {
    
    if ($sName) $this->aArguments['assoc'][$sKey] = $sName;
    else if (array_key_exists($sKey, $this->aArguments['assoc'])) unset($this->aArguments['assoc'][$sKey]);
  }
  
  public function mergeAssoc($aArguments) {
    
    array_merge($this->aArguments['assoc'], $aArguments);
  }
  
  public function getAllIndex($bRemove = true) {
    
    $aIndex = $this->aArguments['index'];
    if ($bRemove) $this->aArguments['index'] = array();
    
    return implode('/', $aIndex);
  }
  
  public function getIndex($iKey = 0, $bRemove = true) {
    
    $mResult = $this->getKey('index', $iKey, $bRemove);
    if ($mResult !== null) $this->aArguments['index'] = array_values($this->aArguments['index']);
    //echo Controler::getBacktrace();
    return $mResult;
  }
  
  public function getAssoc($sKey, $bRemove = true) {
    
    return $this->getKey('assoc', $sKey, $bRemove);
  }
  
  private function getKey($sArray, $mKey, $bRemove) {
    
    if (array_key_exists($mKey, $this->aArguments[$sArray])) {
      
      $mResult = $this->aArguments[$sArray][$mKey];
      if ($bRemove) unset($this->aArguments[$sArray][$mKey]);
      
      return $mResult;
    }
    
    return null;
  }
  
  public function parse() {
    
    $sPath = (string) $this;
    return new HTML_A($sPath, $sPath);
  }
  
  public function __toString() {
    
    return $this->getPath();
  }
}

