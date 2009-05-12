<?php

class Action_Controler {
  
  private static $aInterfaces = array();
  private static $oMessages = null;
  private static $aStats = array();
  
  public static function init() {
    
    self::$oMessages = new Messages(array('error', 'warning'));
    
    if ($oInterfaces = new XML_Document(PATH_INTERFACES)) {
      
      if ($oClasses = $oInterfaces->query('class')) {
        
        foreach ($oInterfaces->query('class') as $oClass) {
          
          $sName = $oClass->read('@name');
          $sPath = $oClass->read('@path');
          $oInterface = new XML_Document($sPath);
          
          if (!$oInterface->isEmpty()) self::$aInterfaces[$sName] = $oInterface;
          else self::addMessage(xt('Fichier d\'interface "%s" vide ou introuvable', $sPath), 'error');
        }
        
      } else self::addMessage(xt('Fichier des interfaces "%s" invalide, aucune classe trouvée !', new HTML_Strong(PATH_INTERFACES)), 'error');
    } else self::addMessage(xt('Impossible de charger le fichier des interfaces à l\'adresse "%s"', new HTML_Strong(PATH_INTERFACES)), 'error');
  }
  
  public static function getInterface($oObject) {
    
    if (is_object($oObject)) {
      $sClass = get_class($oObject);
      
      if (array_key_exists($sClass, self::$aInterfaces)) return self::$aInterfaces[$sClass];
      else {
        
        $sPrevClass = $sClass;
        
        do {
          
          $sTempClass = $sPrevClass;
          $sPrevClass = get_parent_class($sPrevClass);
          
        } while ($sTempClass != $sPrevClass && !array_key_exists($sPrevClass, self::$aInterfaces));
        
        if (array_key_exists($sPrevClass, self::$aInterfaces)) return self::$aInterfaces[$sPrevClass];
        else self::addMessage(xt('Interface de classe "%s" introuvable !', new HTML_Strong($sClass)), 'error');
      }
      
    } else self::addMessage(t('Demande d\'interface impossible, objet invalide !'), 'error');
    
    return false;
  }
  
  public static function getSpecial($sName, $oRedirect) {
    
    $oSpecials = new XML_Document(PATH_SPECIALS);
    
    if ($oSpecial = $oSpecials->get("object[@name='$sName']")) {
      
      if ($sCall = $oSpecial->read('@call')) {
        
        try { eval('$oObject = '.$sCall.';'); }
        catch (Exception $e) { self::addMessage(xt('L\'objet "%s" n\'existe pas !', $sCall), 'error'); }
        
        if (isset($oObject)) return $oObject;
        else self::addMessage(xt('L\'objet "%s" est nul !', new HTML_Strong($sCall)), 'error');
        
      } else self::addMessage(xt('Pas de référence dans le fichier "%s",  !', new HTML_Strong(PATH_SPECIALS)), 'error');
      
    } else self::addMessage(xt('La variable spéciale "%s" n\'existe pas !', new HTML_Strong($sName)), 'error');
    
    return false;
  }
  
  public static function getMessages() {
    
    return self::$oMessages;
  }
  
  public static function viewStats() {
    
    // self::addStat('load', 1); // precog ;)
    
    $oResult = new XML_Document('statistics');
    
    $oResult->addArray(self::$aStats, 'category');
    return $oResult->parseXSL(new XML_Document('/users/controler/stats.xsl'));
  }
  
  public static function addStat($sKey, $iWeight = 1) {
    
    if (!array_key_exists($sKey, self::$aStats)) self::$aStats[$sKey] = $iWeight;
    else self::$aStats[$sKey] += $iWeight;
  }
  
  public static function addMessage($mValue, $sStatut = 'notice', $aArguments = array()) {
    
    // if (Controler::isAdmin()) {
      
      $aMessage = array(
        new HTML_Strong('Action', array('style' => 'text-decoration: underline;')),
        ' : ',
        $mValue);
      
      if ($sStatut == 'error') $aMessage = array_merge($aMessage, array(new HTML_Br, Controler::getBacktrace()));
      
      self::getMessages()->addMessage(new Message($aMessage, $sStatut, $aArguments));
      // if (Controler::isReady()) Controler::addMessage($aMessage, $sStatut, $aArguments);
      // else self::getMessage()->addMessage($aMessage, $sStatut, $aArguments);
      // echo new HTML_Tag('pre', $aMessage, array('class' => 'message-'.$sStatut));
    // }
  }
}

class XML_Action extends XML_Document {
  
  private $sPath = '';
  
  protected function loadAction($sPath, $oRedirect = null) {
    
    if (!$oRedirect) $oRedirect = new Redirect;
    $this->parsePath($sPath, $oRedirect);
    
    if ($this->getPath()) {
      
      $oDocument = new XML_Document($this->getPath());
      
      if (!$oDocument->isEmpty()) {
        
        switch ($oDocument->getRoot()->getNamespace()) {
          
          case NS_EXECUTION : 
            
            $oSettings = $oDocument->get('//le:settings')->remove();
            $oMethod = new XML_Element('li:add', $oDocument->getRoot()->getChildren(), null, null, NS_INTERFACE);
            
            $oResult = $this->runInterfaceMethod($this, $oMethod, Action_Controler::getInterface($this), $oRedirect);
            
          break;
          case NS_INTERFACE : $oAction = $this->loadInterface($oElement, $aPath['arguments']); break;
        }
      }
    }
  }
  
  private function parsePath($sPath, $oRedirect) {
    
    $bValidPath = false;
    
    $sBasePath = MAIN_DIRECTORY.'/';
    $sResultPath = '';
    $bError = false;
    
    $sArguments = '';
    
    // Find file
    
    if ($sPath{0} == '/') {
      
      // absolute path
      
      $bAbsolute = true;
      $sPath = substr($sPath, 1);
      
    } else $bAbsolute = false; // relative path
    
    $sPreviousPath = '';
    $sNextPath = $sPath;
    
    // Remove arguments following '?'
    
    if ($iNextQuestion = strpos($sPath, '?')) {
      
      $sArgumentsAssoc = substr($sNextPath, $iNextQuestion);
      $sNextPath = substr($sNextPath, 0, $iNextQuestion);
      
    } else $sArgumentsAssoc = '';
    
    do {
      
      // only val/val?val not val?val/val
      if ($iNextSlash = strpos($sNextPath, '/')) {
        
        $sSubPath = substr($sNextPath, 0, $iNextSlash);
        $sNextPath = substr($sNextPath, $iNextSlash + 1);
        
      } else {
        
        $sSubPath = $sNextPath;
        $sNextPath = '';
      }
      
      $sActualPath = $sPreviousPath.$sSubPath;
      
      if (is_file($sBasePath.$sActualPath)) $sResultPath = $sActualPath;
      else if (is_file($sBasePath.$sActualPath.'.iml')) $sResultPath = $sActualPath.'.iml';
      else if (is_file($sBasePath.$sActualPath.'.eml')) $sResultPath = $sActualPath.'.eml';
      else if (is_file($sBasePath.$sActualPath.'.dml')) $sResultPath = $sActualPath.'.dml';
      else if (is_dir($sBasePath.$sActualPath)) {
        
        // directory
        
        if ($sNextPath) $sPreviousPath .= $sSubPath.'/';
        else {
          
          if (file_exists($sBasePath.$sActualPath.'/index.eml')) $sResultPath = $sPath.'/index.eml';
          else {
            
            $bError = true;
            Controler::addMessage(t('Action : Le listing de répertoire n\'est point encore possible :|'), 'warning');
            break;
          }
        }
        
      } else {
        
        $bError = true;
        Action_Controler::addMessage(xt('Fichier ou répertoire "%s" introuvable dans "%s"!', new HTML_Strong($sActualPath), new HTML_Strong($sPath)), 'error');
        break;
      }
      
    } while (!$sResultPath && !$bError);
    
    // Get arguments of type ..?arg1=val&arg2=val..
    
    if (!$bError) {
      
      $aArgumentsAssoc = array(); // Associatives arguments exe?var1=val1&var2=val2
      $aArgumentsIndex = array(); // Indexed arguments exe/val1/val2
      
      if ($sNextPath) {
        
        $aArgumentsIndex = explode('/', $sNextPath);
      }
      
      if ($sArgumentsAssoc) {
        
        $aStringArguments = explode('&', substr($sArgumentsAssoc, 1));
        
        foreach ($aStringArguments as $sArgument) {
          
          $aArgument = explode('=', $sArgument);
          
          if (count($aArgument) == 1) $aArgumentsAssoc[$aArgument[0]] = true; // only name
          else $aArgumentsAssoc[$aArgument[0]] = $aArgument[1]; // name and value
        }
      }
      
      $this->setPath('/'.$sResultPath);
      $oRedirect->setArgument('get_assoc', $aArgumentsAssoc);
      $oRedirect->setArgument('get_index', $aArgumentsIndex);
      
      return true;
    }
    
    return false;
  }
  
  public function setPath($sPath) {
    
    $this->sPath = $sPath;
  }
  
  public function getPath() {
    
    return $this->sPath;
  }
  
  private function buildArgument($oElement, $oRedirect) {
    
    if ($oElement instanceof XML_Text) {
      
      return (string) $oElement;
      
    } else { // XML_Element
      
      switch ($oElement->getName()) {
        
        case 'le:special' : 
          
          $oSpecial = Action_Controler::getSpecial($oElement->read('@name'), $oRedirect);
          $oResult = $this->runInterfaceList($oSpecial, $oElement, $oRedirect);
          
        break;
        case 'le:action' :
          
          $oAction = new XML_Action();
          $oResult = $oAction->loadAction($oElement->read('@path'), $oRedirect);
          
        break;
        case 'le:file' : 
          
          $oResult = new XML_Document($oElement->read('@path'));// TODO relative path
          $oSubResult = $this->runInterfaceList($oResult, $oElement, $oRedirect);
          
        break;
        
        case 'li:argument' :
          
          $oResult = $this->buildArgument($oElement->getFirst(), $oRedirect);
          
        break;
          
        default :
          
          foreach ($oElement->getChildren() as $oChild)
            if ($oChild->nodeType == 1) $oChild->replace($this->buildArgument($oChild, $oRedirect));
          $oResult = $oElement;
          
        break;
      }
      
      return $oResult;
    }
  }
  
  private function getObjectType($mValue) {
    
    if (is_object($mValue)) $sType = get_class($mValue);
    else if (is_numeric($mValue)) {
      
      if (intval($mValue) == $mValue) $sType = 'php-integer'; // is_integer
      else $sType = 'php-float';
      
    } else if (is_string($mValue)) $sType = 'php-string';
    else if (is_array($mValue)) $sType = 'array';
    
    return $sType;
  }
  
  private function runInterfaceList($oObject, $oElement, $oRedirect) {
    
    $oInterface = Action_Controler::getInterface($oObject);
    $oResult = null;
    $bStatic = ($oElement->get("self[@static='true']"));
    
    foreach ($oElement->getChildren() as $oChild) {
      
      if ($oChild->getPrefix() != 'li') Action_Controler::addMessage(xt('Cet élément n\'est pas permis : %s', $oElement->viewResume()), 'error');
      else $oResult = $this->runInterfaceMethod($oObject, $oChild, $oInterface, $oRedirect, $bStatic);
    }
    
    return array($oResult, $oElement->get("self[@return='true']"));
  }
  
  private function runInterfaceMethod($oObject, $oElement, $oInterface, $oRedirect, $bStatic = false) {
    
    if ($oMethod = $oInterface->get("ns:method[@path='{$oElement->getName(true)}']")) {
      
      $aArguments = array();
      
      foreach ($oElement->getChildren() as $oChild) {
        
        if ($oChild->nodeType == 1) {
          
          if ($oChild->getNamespace() != NS_INTERFACE || ($oChild->getNamespace() == NS_INTERFACE && $oChild->getName(true) == 'argument')) {
            
            $mResult = $this->buildArgument($oChild, $oRedirect);
            
            if ($sName = $oChild->read('@name')) $aArguments['assoc'][$sName] = $mResult;
            $aArguments['index'][] = $mResult;
            $aArguments['element'] = $oChild->remove();
          }
          
        } else $aArguments['index'][] = (string) $oChild;
      }
      
      if (!$sMethod = $oMethod->read('@name')) Action_Controler::addMessage('Interface invalide, attribut \'nom\' manquant', 'error');
      else {
        
        $bReturnSub = false;
        
        if ($aArguments) $aArgumentsPatch = $this->parseArguments($oMethod->getChildren(), $aArguments);
        else $aArgumentsPatch = array();
        
        $oResult = $this->runMethod($oObject, $sMethod, $oRedirect, $aArgumentsPatch, $bStatic);
        
        if ($oResult) {
          
          if ($oElement->hasChildren()) list($oSubResult, $bReturnSub) = $this->runInterfaceList($oResult, $oElement, $oRedirect);
          
          if ($bReturnSub) return $oSubResult;
          else if ($bReturn = $this->test("self[@return='true']")) return $oResult;
          else return null;
          
        } else Action_Controler::addMessage(xt('Aucun résultat pour l\'élément : %s', $oElement->viewResume()), 'report');
      }
    } else Action_Controler::addMessage(xt('Méthode "%s" inexistante dans l\'interface', new HTML_Strong($oElement->getName(true))), 'warning');
    
    return false;
  }
  
  private function runMethod($oObject, $sMethodName, $oRedirect = null, $aArguments = array(), $bStatic = false) {
    
    // Contrôle de l'existence de la méthode
    
    if (method_exists($oObject, $sMethodName)) {
      
      // Lancement de l'action
      
      $sCaller = $bStatic ? '::' : '->';
      $sArguments = $aArguments ? $aArguments['string'] : '';
      
      eval("\$oResult = \$oObject$sCaller\$sMethodName($sArguments);");
      Action_Controler::addMessage(t('Evaluation : ')."\$oObject$sCaller$sMethodName(".count($aArguments['arguments']).");", 'report');
      // dsp($aArguments['arguments']);
      return $oResult;
      
    } else Action_Controler::addMessage(xt('La méthode "%s" n\'existe pas dans la classe "%s" !', new HTML_Strong($sMethodName.'()'), get_class($oObject)), 'error');
  }
  
  private function loadInterface($oElement, $oRedirect = null) {
    
    $oResult = null;
    $bError = false;
    
    if ($oConstruct = $oElement->get('ns:method-construct')) {
      
      $oArguments = $oConstruct->query('ns:argument');
      
      if ($oArguments->length) {
        
        if ($oRedirect) $aArguments = $this->parseArguments($oArguments, $oRedirect->getArgument('get_assoc'));
        if (!$aArguments) {
          
          Controler::addMessage('Action : Erreur dans les arguments, impossible de construire l\'objet', 'warning');
          $bError = true;
        }
      }
    }
  }
  
  private function parseArguments($oChildren, $aArguments) {
    
    // CALL argument
    
    $bAssoc = false;
    if ($bAssoc) $aArguments = $aArguments['assoc'];
    else $aArguments = $aArguments['index'];
    
    $bError = false;
    
    if (!$bAssoc) $aArguments = array_values($aArguments);
    
    if ($oChildren->length == 1 && $oChildren->item(0)->getName() == 'multiple-arguments') {
      
      $oArguments = $oChildren->item(0);
      
      // Multiple arguments (undefined number)
      
      $iRequired = intval($oArguments->read('@required-count'));
      
      if (count($aArguments) >= $iRequired) {
        
        $aFormats = array();
        foreach($oArguments->getChildren() as $oFormat) $aFormats[] = $oFormat->read();
        
        foreach ($aArguments as $mArgument) {
          
          if ($this->validArgumentType($mArgument, $aFormats)) $aResultArguments[] = $mArgument;
        }
        
      } else {
        
        Controler::addMessage(t('Action : Pas assez d\'arguments !', 'warning'));
        $bError = true;
      }
      
    } else {
      
      // Normal arguments (defined number)
      
      $aResultArguments = $aArguments;
      
      foreach($oChildren as $mIndex => $oChild) {
        
        $sName = $oChild->read('@name');
        
        if (($bAssoc && array_key_exists($sName, $aArguments)) || (!$bAssoc && array_key_exists($mIndex, $aArguments))) {
          
          if ($bAssoc) $mArgument = $aArguments[$sName];
          else $mArgument = $aArguments[$mIndex];
          
          $bError = !$this->validArgumentType($mArgument, array($oChild->read('@format')));
          
        } else if (!$oChild->read("self[@required='false']")) {
          
          Controler::addMessage(xt('Action : L\'argument requis %s est absent',
            new HTML_Strong($oChild->read('@name'))), 'warning');
          
          $bError = true;
        }
      }
    }
    
    if (!$bError) {
      
      $aEvalArguments = array();
      
      foreach ($aResultArguments as $mIndex => $mArgument) $aEvalArguments[] = "\$aArguments['arguments']['$mIndex']";
      $sArguments = implode(', ', $aEvalArguments);
      
      return array(
        'result' => true,
        'string' => $sArguments,
        'arguments' => $aArguments,
      );
      
    } else return false;
  }
  
  private function validArgumentType($mArgument, $aFormats = array()) {
    
    if (is_object($mArgument)) {
      
      $sFormat = get_class($mArgument);
      foreach ($aFormats as $sFormat) if ($mArgument instanceof $sFormat) return true;
      
    } else {
      
      $sFormat = 'php-'.gettype($mArgument);
      if (in_array($sFormat, $aFormats)) return true;
    }
    
    Action_Controler::addMessage(xt('L\'argument "%s" n\'est pas au format requis', new HTML_Strong($sFormat)), 'warning');
    
    return false;
  }
  
  private function buildClass($sClassName, $sFile = '', $aArguments = array()) {
    
    if ($sFile) {
      
      // Include du fichier
      
      $sFile = Controler::getDirectory().$sFile;
      
      if (file_exists($sFile)) require_once($sFile);
      else if (Controler::isAdmin()) Controler::addMessage(sprintf(t('Fichier "%s" introuvable !'), $sFile));
    }
    
    // Contrôle de l'existence de la classe
    
    if (Controler::isAdmin()) $sError = sprintf(t('Action impossible (la classe "%s" n\'existe pas) !'), new HTML_Strong($sClassName));
    else $sError = t('Page introuvable, veuillez corriger l\'adresse !');
    
    if (!class_exists($sClassName)) Controler::errorRedirect($sError);
    
    // Création de la classe
    
    eval("\$oAction = new \$sClassName({$aArguments['string']});");
    
    return $oAction;
  }
}

class Action extends XML_Action {
  
  public function __construct($sPath) {
    
    parent::__construct(new HTML_Div('', array('class' => 'action')));
    $this->loadAction($sPath);
  }
}

class Temp_Action extends XML_Document {
  
  private $sPath = '';
  private $oRedirect = null;
  private $aBlocs = array();
  
  /*
   * A charger : Settings, XML, XSD, XSL
   **/
  
  public function __construct($sPath = '', $oRedirect = null, $sSource = '') {
    
    parent::__construct($sPath, $sSource);
    
    $this->setRedirect($oRedirect);
    $this->setPath($sPath);
    $this->setSource($sSource);
  }
  
  public function addAction($sPath, $oRedirect = null, $sSource = '') {
    
    $this->getBloc('actions')->add($this->loadAction($sPath, $oRedirect = null, $sSource = ''));
  }
  
  public function loadAction($sPath, $oRedirect = null, $sSource = '') {
  }
  
  public function loadActionBloc($sPath, $oElement, $oRedirect = null) {
    
    $oAction = new XML_Document;
    $aPath = $this->_parsePath($sPath);
    
    // dsp($aPath);
    if ($aPath) {
      
      if (!$oDocument->isEmpty()) {
        
        //if ($oDocument->getRoot()->getNamespace() == '')
        switch ($oDocument->getRoot()->getNamespace()) {
          
          case NS_EXECUTION : $oAction = $this->_loadExecutable($oElement, $oRedirect); break;
          case NS_INTERFACE : $oAction = $this->loadInterface($oElement, $aPath['arguments']); break;
        }
      }
    }
    
    return $oAction;
  }
  
  private function _loadSubExecutable($oElement, $oArguments, $oRedirect = null) {
    
    $oStatics = new XML_Document('/users/web/statics.cml');
    
    switch ($oElement->getName(true)) {
      
      case 'action' :
        
        // @path
        
        $sPath = $oElement->read('@path');
        $oResult = $this->loadAction($sPath);
        
        // $oAction = $this->buildClass($sClass, $sFile, $aArguments, $sArguments);
        // $oResult = $this->_loadBloc($oAction, $oElement, $oRedirect);
        
      break;
      
      case 'static' :
        
        // @call
        
      break;
      
      case 'file' :
      
      break;
      
      default:
        
        $oResult->add($oElement);
        
      break;
    }
    
    // if ($oObject instanceof XML_Document) $sIml = '/users/web/xml_document.iml';
    // else if ($oObject instanceof XML_Element) $sIml = '/users/web/xml_element.iml';
    
    $sPrefix = $oElement->getPrefix();
    
    foreach ($oElement->getChildren() as $oChild) {
      
      switch ($oChild->getNamespace()) {
        
        case NS_EXECUTION :
          
          
        break;
        
        case NS_INTERFACE :
        
        break;
        
        default:
        
        break;
      }
    }
    
    // CALL argument
    
    $aEvalArguments = array();
    
    for ($i = 0; $i < count($aArguments); $i++) $aEvalArguments[] = "\$aArguments[$i]";
    $sArguments = implode(', ', $aEvalArguments);
    
    // CALL actions
    
    if ($sMethod) {
      
      $oResult = $this->_runMethod($oAction, $sMethod, $aArguments, $sArguments);
      return $oResult;
      
    } else return $oAction;
  }
  
  public function setRedirect($oRedirect = null) {
    
    $this->oRedirect = $oRedirect;
  }
  
  public function getRedirect() {
    
    return $this->oRedirect;
  }
  
  public function getPath() {
    
    return $this->sPath;
  }
  
  public function setPath($sPath = '') {
    
    $this->sPath = $sPath;
  }
  
  public function reload() {
    
    if ($this->getPath()) $this->loadDocument($this->getPath(), $this->getSource());
  }
  
  public function getSource() {
    
    return $this->sSource;
  }
  
  public function setSource($sSource = '') {
    
    $this->sSource = $sSource;
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
    
    return $this; //$oResult = new XML_Document;
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

