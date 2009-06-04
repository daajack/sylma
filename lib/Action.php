<?php

class XML_Action extends XML_Document {
  
  private $sPath = '';
  private $sName = '';
  private $sFullPath = '';
  private $oRedirect = null;
  
  public function __construct($sPath, $oRedirect = null) {
    
    if (!$oRedirect) $oRedirect = new Redirect;
    
    $this->setRedirect($oRedirect);
    
    $this->sFullPath = $sPath;
    //$this->sPath = $sPath;
    
    parent::__construct($this->sFullPath);
  }
  
  private function loadInterface($oInterface, $oRedirect = null) {
    
    $oResult = null;
    $sMethod = '';
    $aArguments = array();
    
    $sClassName = $oInterface->read('ns:name');
    $sFile = $oInterface->read('ns:file');
    
    // if ($oRedirect->getPath()->getArgument())
    // $oConstruct->get('*[@required="true"]')
    // if ($oInterface->
    
    if ($oConstruct = $oInterface->get('ns:method-construct')) {
      
      if ($oConstruct->hasChildren()) {
        if ($oRedirect) $aArguments = $this->parseArguments($oConstruct, array(), 'index');
        if (!$aArguments) {
          
          Action_Controler::addMessage('Erreur dans les arguments, impossible de construire l\'objet', 'warning');
          return null;
        }
      }
    }
    
    $oObject = $this->buildClass($sClassName, $sFile, $aArguments);
    
    if ($sMethod = $oRedirect->getPath()->getIndex()) {
      
      $oElement = new XML_Element('li:'.$sMethod, null, array('get-redirect' => 'index'), NS_INTERFACE);
      list($oSubResult, $bSubResult) = $this->runInterfaceMethod($oObject, $oElement, $this);
      
      if ($bSubResult) $oResult = $oSubResult;
      else $oResult = $oObject;
      
    } else $oResult = $oObject;
    
    return $oResult;
  }
  
  private function runInterfaceList($mObject, $oElement) {
    
    if (is_array($mObject)) $mObject = new Action_Array($mObject);
    
    $oInterface = Action_Controler::getInterface($mObject);
    
    $aResult = array(null, false);
    
    if ($oInterface) {
      
      $bStatic = is_string($mObject);
      
      foreach ($oElement->getChildren() as $oChild) {
        
        if ($oChild->isElement()) {
          
          if ($oChild->getNamespace() != NS_INTERFACE) Action_Controler::addMessage(xt('runInterfaceList() : Cet élément n\'est pas permis : %s', $oElement->viewResume()), 'error');
          else $aResult = $this->runInterfaceMethod($mObject, $oChild, $oInterface, $bStatic);
          
        } else Action_Controler::addMessage(xt('runInterfaceList() : Noeud texte impossible ici : "%s"', new HTML_Strong($oChild)), 'error');
      }
    }
    
    return $aResult;
  }
  
  private function runInterfaceMethod($mObject, $oElement, $oInterface, $bStatic = false) {
    
    $bReturn = true;
    
    if (!$oMethod = $oInterface->get("ns:method[@path='{$oElement->getName(true)}']")) {
      
      Action_Controler::addMessage(xt('Méthode "%s" inexistante dans l\'interface', new HTML_Strong($oElement->getName(true))), 'warning');
      
    } else {
      
      if ($oElement->testAttribute('return') || $oMethod->testAttribute('return-default')) $bReturn = true;
      else $bReturn = false;
      
      $aArguments = $this->loadElementArguments($oElement);
      
      if (!$sMethod = $oMethod->getAttribute('name')) Action_Controler::addMessage('Interface invalide, attribut \'nom\' manquant', 'error');
      else {
        
        $aArgumentsPatch = $this->parseArguments($oMethod, $aArguments, $oElement->getAttribute('get-redirect'));
        
        if ($aArgumentsPatch) $oResult = $this->runMethod($mObject, $sMethod, $aArgumentsPatch, $bStatic);
        else Action_Controler::addMessage(xt('Arguments invalides pour la méthode "%s"', new HTML_Strong($oElement->getName(true))), 'notice');
        
        if (!isset($oResult)) {
          
          if (Action_Controler::useStatut('report')) Action_Controler::addMessage(xt('Aucun résultat pour l\'élément : %s', $oElement->viewResume()), 'report');
          
        } else {
          
          $bSubReturn = false;
          
          if ($oElement->hasChildren()) list($oSubResult, $bSubReturn) = $this->runInterfaceList($oResult, $oElement);
          
          if ($bSubReturn) return array($oSubResult, true);
          else return array($oResult, $bReturn);
        }
      }
    }
    
    return array(null, $bReturn);
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
      
      switch ($oElement->getName()) {
        
        case 'le:special' : 
          
          $mSpecial = Action_Controler::getSpecial($oElement->getAttribute('name'), $this->getRedirect());
          $mResult = '';
          
          list($mSubResult, $bSubReturn) = $this->runInterfaceList($mSpecial, $oElement);
          
        break;
        case 'le:action' :
          
          $mResult = new Action($oElement->getAttribute('path'), $this->getRedirect());
          // TODO relative path
          
        break;
        case 'le:direct-action' :
          
          if ($sPath = $oElement->getAttribute('path')) {
            
            if ($oElement->hasChildren()) {
              
              $aArguments = $this->loadElementArguments($oElement);
            }
            
            $oSubRedirect = clone $this->getRedirect();
            $oSubRedirect->setPath($sPath)->shiftIndex($aArguments['index']);
            $oSubRedirect->setPath($sPath)->shiftAssoc($aArguments['assoc']);
            
            $mResult = new XML_Action($sPath, $oSubRedirect);
          }
          //runInterfaceMethod($mObject, new XML_Element('method-construct', $oElement->getChildren(), Action_Controler::getInterface($mObject);, $oRedirect, $bStatic = false)
          // TODO relative path
          
        break;
        case 'le:php' : 
          
          switch ($oElement->getAttribute('name')) {
            
            case 'array' :
              
              if ($oElement->getChildren()->length == 1 && $oElement->getFirst()->isText()) {
                
                // 1 child text
                
                if (!$sSep = $oElement->getAttribute('separator')) $sSep = ',';
                $mResult = explode($sSep, $oElement->read());
                
              } else {
                
                // 0..n child(ren) element
                
                $mResult = array();
                
                foreach ($oElement->getChildren() as $oChild) $mResult[] = $this->buildArgument($oChild);
              }
              
            break;
            
            default : $mResult = null; break;
          }
          
        break;
        case 'le:file' : 
          
          $mResult = new XML_Document($oElement->getAttribute('path'));
          // TODO relative path
          list($mSubResult, $bSubReturn) = $this->runInterfaceList($mResult, $oElement);
          
        break;
        
        case 'li:argument' :
          
          $mResult = $this->buildArgument($oElement->getFirst());
          
        break;
          
        default :
          
          foreach ($oElement->getChildren() as $oChild) {
            
            if ($oChild->isElement()) {
              
              $oResult = $this->buildArgument($oChild);
              $oChild->replace($oResult);
            }
          }
          
          $mResult = $oElement;
          
        break;
      }
    }
    
    if ($bSubReturn) return $mSubResult;
    return $mResult;
  }
  
  private function parseArguments($oMethod, $aArguments, $sRedirect) {
    
    $bAssoc = false;
    
    // Mix Redirect Arguments with Executable arguments
    
    if ($sRedirect) {
      
      if (in_array($sRedirect, array('assoc', 'index'))) {
        
        if ($sRedirect == 'assoc') $bAssoc = true;
        $aArguments[$sRedirect] = $this->getRedirect()->getPath()->getArgument($sRedirect);
        
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
              if ($sRedirect == 'index') $this->getRedirect()->getPath->getIndex();
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
            
            if ($bAssoc) $this->getRedirect()->getPath()->getAssoc($sKey);
            else $this->getRedirect()->getPath()->getIndex($sKey);
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
      
      $sFormat = get_class($mArgument);
      foreach ($aFormats as $sFormat) if ($mArgument instanceof $sFormat) return true;
      
    } else {
      
      if (is_numeric($mArgument)) {
        
        if (ctype_digit($mArgument)) $sFormat = 'php-integer';
        else $sFormat = 'php-float';
        
      } else $sFormat = 'php-'.strtolower(gettype($mArgument));
      
      if (in_array($sFormat, $aFormats)) return true;
    }
    
    Action_Controler::addMessage(array(
      xt('L\'argument "%s" n\'est pas dans la liste "%s"',
        new HTML_Strong($sFormat),
        implode(', ', $aFormats)),
        new HTML_Div(array(
        new HTML_br,
        new HTML_Strong(t('Méthode').' : '),
        new HTML_Em($oElement->getParent()->viewResume(150, true)),
        new HTML_br,
        new HTML_Strong(t('Argument').' : '),
        new HTML_Em($oElement->viewResume(150, true))))), 'error');
    
    // dsp($mArgument);
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
      // dsp($aArguments['arguments']);
      return $oResult;
      
    } else Action_Controler::addMessage(xt('La méthode "%s" n\'existe pas dans la classe "%s" !', new HTML_Strong($sMethodName.'()'), get_class($oObject)), 'error');
  }
  
  private function buildClass($sClassName, $sFile = '', $aArguments = array()) {
    
    if ($sFile) {
      
      // Include du fichier
      
      $sFile = MAIN_DIRECTORY.'/'.$sFile;
      
      if (file_exists($sFile)) require_once($sFile);
      else if (Controler::isAdmin()) Controler::addMessage(sprintf(t('Fichier "%s" introuvable !'), $sFile));
    }
    
    // Contrôle de l'existence de la classe
    
    if (Controler::isAdmin()) $sError = sprintf(t('Action impossible (la classe "%s" n\'existe pas) !'), new HTML_Strong($sClassName));
    else $sError = t('Page introuvable, veuillez corriger l\'adresse !');
    
    if (!class_exists($sClassName)) Controler::errorRedirect($sError);
    
    // Création de la classe
    
    eval("\$oAction = new \$sClassName({$aArguments['string']});");
    if (Action_Controler::useStatut('report')) Action_Controler::addMessage(t('Evaluation : ')."\$oAction = new $sClassName(".count($aArguments['arguments']).");", 'report');
    
    return $oAction;
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
              
              $oResult = new XML_Document(new HTML_Div());
              
              $oSettings = $oDocument->get('le:settings')->remove();
              $oMethod = new XML_Element('li:add', $oDocument->getRoot()->getChildren(), null, NS_INTERFACE);
              
              $this->runInterfaceMethod($oResult, $oMethod, Action_Controler::getInterface($oResult, $this->getRedirect()));
            
            break;
            
            // object (interface)
            
            case 'object' :
              
              if (!$sInterface = $oRoot->getAttribute('path')) Action_Controler::addMessage(xt('Aucun interface désigné pour l\'action %s', new HTML_Strong($this->sFullPath)), 'warning');
              else {
                
                $this->runInterfaceList(new XML_Action($sInterface), $oRoot, $this->getRedirect());
              }
              
            break;
            
            default :
            
            break;
          }
          
        break;
        
        /* Interface */
        
        case NS_INTERFACE :
          
          $oResult = $this->loadInterface($oRoot, $this->getRedirect());
          
        break;
      }
    }
    
    return $oResult;
  }
}

class HTML_Action extends HTML_Tag {
  
  public function __construct() {
    
    parent::__construct('div');
    $this->addClass('action');
  }
}

class Action extends HTML_Action {
  
  public function __construct($sPath, $oRedirect = null) {
    
    parent::__construct();
    
    // if (!$oRedirect) 
    $oRedirect = new Redirect($sPath);
    // else $oRedirect->addPath($sPath);
    
    if ($sRealPath = $this->parsePath($oRedirect)) {
      
      $oAction = new XML_Action($sRealPath, $oRedirect);
      $oResult = $oAction->parse();
      
      if ($oResult) {
        
        // if ($oResult instanceof XML_Document) $this->add($oResult->query('content/*'));
        $this->add($oResult);
      }
      
      // $oRedirect->removePath()
    }
  }
  
  private function parsePath($oRedirect) {
    
    $oRedirect->setArgument('get_assoc', array());
    $oRedirect->setArgument('get_index', array());
    $oRedirect->setArgument('get_all', array());
    
    $sResultPath = '';
    $bError = false;
    
    $oSubDirectory = Controler::getDirectory();
    $oPath = $oRedirect->getPath();
    
    do {
      
      $sSubPath = $oPath->getIndex();
      
      if ($oFile = $oSubDirectory->getFile($sSubPath, false)) $sResultPath = $oFile->getFullPath();
      else if ($oFile = $oSubDirectory->getFile($sSubPath.'.iml', false)) $sResultPath = $oFile->getFullPath();
      else if ($oFile = $oSubDirectory->getFile($sSubPath.'.eml', false)) $sResultPath = $oFile->getFullPath();
      else if ($oFile = $oSubDirectory->getFile($sSubPath.'.dml', false)) $sResultPath = $oFile->getFullPath();
      else if ($oSubDirectory = $oSubDirectory->getDirectory($sSubPath, 1)) {
        
        if (!$oPath->getArgument('index')) {
          
          if ($oFile = $oSubDirectory->getFile('index.eml')) $sResultPath = $oFile->getFullPath();
          else {
            
            $bError = true;
            Action_Controler::addMessage(xt('Le listing de répertoire n\'est pas encore possible :| - "%s"', new HTML_Strong($sActualPath)), 'warning');
          }
        }
        
      } else $bError = true;
      
    } while (!$oFile && !$bError);
    
    if (!$bError) {
      
      $oPath->setFile($oFile);
      $oRedirect->setArgument('get_assoc', $oPath->aAssoc);
      $oRedirect->setArgument('get_index', $oPath->aIndex);
      $oRedirect->setArgument('get_all', array_merge($oPath->aIndex, array_values($oPath->aAssoc)));
      
      return $sResultPath;
    }
    
    return '';
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
  
  public $aIndex = array();
  public $aAssoc = array();
  public $aAll = array();
  
  private $sExtension = '';
  private $oFile = null;
  
  public function __construct($sPath) {
    
    // Remove arguments following '?' of type ..?arg1=val&arg2=val..
    
    if ($iAssoc = strpos($sPath, '?')) {
      
      $sAssoc = substr($sPath, $iAssoc);
      $sPath = substr($sPath, 0, $iAssoc);
      
      $aAssoc = explode('&', substr($sAssoc, 1));
      
      foreach ($aAssoc as $sArgument) {
        
        $aArgument = explode('=', $sArgument);
        
        if (count($aArgument) == 1) $this->aIndex[] = $aArgument[0]; // only name
        else $this->aAssoc[$aArgument[0]] = $aArgument[1]; // name and value
      }
    }
    
    $this->aIndex += explode('/', $sPath);
    
    if ($this->aIndex) $this->parseExtension($this->aIndex[count($this->aIndex) - 1]);
  }
  
  private function parseExtension($sName) {
    
    if ($sName && ($iExtension = strrpos($sName, '.')))
      $this->sExtension = substr($sName, $iExtension);
  }
  
  public function getFile($oFile) {
    
    return $this->oFile;
  }
  
  public function setFile($oFile) {
    
    $this->oFile = $oFile;
  }
  
  public function getPath() {
    
    return $this->sPath;
  }
  
  public function getExtension() {
    
    return $this->sExtension;
  }
  
  public function getArgument($sArray = 'index') {
    
    switch ($sArray) {
      
      case 'assoc' : return $this->aAssoc; break;
      
      case 'index' :
      default : return $this->aIndex; break;
    }
  }
  
  public function shiftIndex($aArguments) {
    
    $this->aIndex = array_values($aArguments + $this->aIndex);
  }
  
  public function shiftAssoc($aArguments) {
    
    $this->aAssoc += $aArguments;
  }
  
  public function getIndex($iKey = 0, $bRemove = true) {
    
    $mResult = $this->getKey($this->aIndex, $iKey, $bRemove);
    if ($mResult !== null) $this->aIndex = array_values($this->aIndex);
    
    return $mResult;
  }
  
  public function getAssoc($sKey, $bRemove = true) {
    
    return $this->getKey($this->aAssoc, $sKey, $bRemove);
  }
  
  public function getAll($mKey = 0, $bRemove = true) {
    
    return $this->getKey($this->aAll, $mKey, $bRemove);
  }
  
  private function getKey(&$aArray, $mKey, $bRemove) {
    
    if (array_key_exists($mKey, $aArray)) {
      
      $mResult = $aArray[$mKey];
      if ($bRemove) unset($aArray[$mKey]);
      
      return $mResult;
    }
    
    return null;
  }
  
  public function __toString() {
    
    return $this->sPath;
  }
}

class Old_Action extends XML_Tag {
  
  private $aSchemas = array();
  private $sMode = 'normal';
  
  public function __construct() {
    
    parent::__construct('div');
    $this->setAttribute('id', 'action');
  }
  
  protected function setMode($sMode = '') {
    
    if ($sMode) $this->sMode = $sMode;
  }
  
  protected function getMode() {
    
    return $this->sMode;
  }
  
  protected function isMode($sMode) {
    
    return ($this->getMode() == $sMode);
  }
  
  protected function getArgument($iKey = 0) {
    
    return Controler::getArgument($iKey);
  }
  
  protected function getId($iKey = 0) {
    
    $iId = $this->getArgument($iKey);
    
    if ($iId && is_numeric($iId)) return $iId;
    else return false;
  }
  
  protected function errorRedirect($sMessage = '', $sRedirect = '/error/view') {
    
    return new Redirect($sRedirect, new Message($sMessage, 'error'));
  }
  
  public function getSchema($sSchema = '') {
    
    if (array_key_exists($sSchema, $this->aSchemas)) return $this->aSchemas[$sSchema];
    else return array();
  }
  
  public function getSchemas() {
    
    $aSchemas = array();
    foreach (func_get_args() as $sArg) $aSchemas += $this->getSchema($sArg);
    
    return $aSchemas;
  }
  
  public function setSchemas($aSchemas) {
    
    $this->aSchemas = $aSchemas;
  }
  
  public function addSchemas($aSchemas) {
    
    $this->aSchemas += $aSchemas;
  }
  
  protected function checkRequest($aSchema = array()) {
    
    $aMsg = array();
    
    if (!$aSchema) $aMsg[] = new Message(t('Le contrôle des champs n\'a pu s\'effectuer correctement. Impossible d\'enregistrer !', 'error'));
    
    foreach ($aSchema as $sKey => $aField) {
      
      // Si le paramètre 'deco' est à true, la valeur n'est pas contrôlée
      
      if (isset($aField['deco']) && $aField['deco']) continue;
      
      $oTitle = new HTML_Tag('strong');
      $oTitle->add($aField['title']);
      
      if (!array_key_exists($sKey, $_POST) || !$_POST[$sKey]) {
        
        // Si le champs est requis
        
        if (isset($aField['required']) && $aField['required']) {
          
          $sMessage = sprintf(t('Le champ "%s" est obligatoire.'), $oTitle);
          $aMsg[] = new Message($sMessage, 'warning', array('field' => $sKey));
        }
        
      } else {
        
        // Test des types
        
        $mValue = $_POST[$sKey];
        
        switch ($aField['type']) {
          
          // Integer
          
          case 'key' :
          case 'integer' :
            
            $fValue = floatval($mValue); $iValue = intval($mValue);
            
            if (!is_numeric($mValue) || $fValue != $iValue) {
              
              $sMessage = sprintf(t('Le champ "%s" doit être un nombre entier.'), $oTitle);
              $aMsg[] = new Message($sMessage, 'warning', array('field' => $sKey));
            }
            
          break;
          
          // Float
          
          case 'float' :
            
            if (!is_numeric($mValue)) {
              
              $sMessage = sprintf(t('Le champ "%s" doit être un nombre.'), $oTitle);
              $aMsg[] = new Message($sMessage, 'warning', array('field' => $sKey));
            }
            
          break;
          
          // Date
          
          case 'date' :
            
            
            
          break;
          
          // E-mail
          
          case 'email' :
            
            $sAtom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';   // caractères autorisés avant l'arobase
            $sDomain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)'; // caractères autorisés après l'arobase (nom de domaine)
            
            $sRegex = '/^'.$sAtom.'+(\.'.$sAtom.'+)*@('.$sDomain.'{1,63}\.)+'.$sDomain.'{2,63}$/i';
            
            if (!preg_match($sRegex, $mValue)) {
              
              $sMessage = sprintf(t('Le champ "%s" n\'est pas une adresse mail valide.'), $oTitle);
              $aMsg[] = new Message($sMessage, 'warning', array('field' => $sKey));
            }
            
          break;
        }
        
        // Si une taille minimum est requise
        
        if (isset($aField['min-size']) && strlen($mValue) < $aField['min-size']) {
          
          $sMessage = sprintf(t('Le champ "%s" doit faire au moins %s caractères'), $oTitle, new HTML_Strong($aField['min-size']));
          $aMsg[] = new Message($sMessage, 'warning', array('field' => $sKey));
        }
      }
    }
    
    return $aMsg;
  }
  
  public function importPost($aSchema) {
    
    $aFields = array();
    
    foreach ($aSchema as $sField => $aField) {
      
      // Si le filtre 'deco' est activé, le champs n'est pas inséré dans la base
      
      if (isset($aField['deco']) && $aField['deco']) continue;
      
      $sType = array_val('type', $aField);
      $mValue = false;
      
      if (array_key_exists($sField, $_POST)) {
        
        $sValue = $_POST[$sField];
        
        if ($sType == 'date') {
          
          // Date
          
          $mValue = db::buildDate($sValue);
          
          // if (!$sValue) $sValue = 'NULL';
          // else $sValue = db::buildString($sValue);
          
        } else {
          
          // Autres
          
          $mValue = db::buildString($sValue); 
        }
        
      } else {
        
        // Booléen
        
        if ($sType == 'bool') $mValue = 0;
      }
      
      if ($mValue !== false) $aFields[$sField] = $mValue;
    }
    
    return $aFields;
  }
}

