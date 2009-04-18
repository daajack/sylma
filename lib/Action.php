<?php

class XML_Action extends XML_Document {
  
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
    
    $oDocument = new XML_Document($sPath, $sSource);
    
    if (!$oDocument->isEmpty()) {
      
      //if ($oDocument->getRoot()->getNamespace() == '')
      switch ($oDocument->getRoot()->getNamespace()) {
        
        case NS_EXECUTION : $oAction = $this->_loadExecutable($oDocument->getRoot(), $oRedirect); break;
        case NS_INTERFACE : $oAction = $this->_loadInterface($oDocument); break;
      }
      
    } else $oAction = null;
    
    return $oAction;
  }
  
  private function _parseArguments($oChildren) {
    
    // Load IML
    
    foreach ($oChildren as $oChild) {
      
      // if 
    }
    
    $sFile = $oSettings->read('class/file');
    $sClass = $oSettings->read('class/name');
    $sMethod = $oSettings->read('method');
    $bRedirect = $oSettings->test("@redirect='true'");
  }
  
  private function _loadInterface($oObject, $oElement, $oRedirect = null) {
    
    
  }
  
  private function _loadExecutable($oElement, $oRedirect = null) {
    
    $oResult = new XML_Document;
    
    foreach ($oElement->getChildren() as $oChild) {
      
      switch ($oChild->getNamespace()) {
        
        case NS_EXECUTION :
          
          switch ($oChild->getName(true)) {
            
            case 'settings' :
              
              // ?
              
            break;
            
            default :
              
              $oResult->add($this->_loadSubExecutable($oChild, $oRedirect));
              
            break;
          }
          
        break;
        
        case NS_INTERFACE :
        
        break;
        
        default:
          
          $oResult->add($oElement);
          
        break;
      }
    }
    
    return $oResult;
  }
  
  private function _loadSubExecutable($oElement, $oRedirect = null) {
    
    switch ($oElement->getName(true)) {
      
      case 'action' :
        
        if ($sPath = $oElement->read('@path')) {
          
          // @path
          
          $oResult = $this->loadAction($sPath);
          
        } else if ($sCall = $oElement->read('@call')) {
          
          // @call
          
          $oObjectElement = $oStatics->get("//*[@name='$sCall']");
          
          try { eval('$oObject = '.$oObjectElement->read("call").';'); }
          catch (Exception $e) { Controler::errorRedirect(xt('L\'objet "%s" n\'existe pas !', $oObjectElement->read('call'))); }
          
          $sInterface = $oStatics->read("interface");
          
          // $oResult = $this->load
        }
        
        // $oAction = $this->_buildAction($sClass, $sFile, $aArguments, $sArguments);
        // $oResult = $this->_loadBloc($oAction, $oElement, $oRedirect);
        
      break;
      
      case 'file' :
      
      break;
    }
    
    // if ($oObject instanceof XML_Document) $sIml = '/users/web/xml_document.iml';
    // else if ($oObject instanceof XML_Element) $sIml = '/users/web/xml_element.iml';
    
    $oStatics = new XML_Document('/users/web/statics.cml');
    
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
      
      $oResult = $this->_runAction($oAction, $sMethod, $aArguments, $sArguments);
      return $oResult;
      
    } else return $oAction;
  }
  
  private function _buildAction($sClassName, $sFile = '', $aArguments = null, $sArguments = '') {
    
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
    
    eval("\$oAction = new \$sClassName($sArguments)");
    
    return $oAction;
  }
  
  private function _runAction($oAction, $sMethodName, $oRedirect = null, $sArguments = '') {
    
    // Contrôle de l'existence de la méthode
    
    if (Controler::isAdmin()) $sError = sprintf(t('Action impossible (la méthode "%s" n\'existe pas) !'), new HTML_Strong(Controler::getClassName().'::'.$sMethodName.'()'));
    else $sError = t('Page introuvable, veuillez corriger l\'adresse !');
    
    if (!method_exists($oAction, $sMethodName)) Controler::errorRedirect($sError);
    
    // Lancement de l'action
    
    eval("\$oResult = \$oAction->\$sMethodName($sArguments);");
    
    return $oResult;
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
    
    if ($oWindow = $this->get('window')) {
      
      foreach ($oWindow->getChildren() as $oChild) {
        
        switch ($oChild->getName()) {
          
          case 'add-js' : Controler::getWindow()->addJS($oChild->getValue()); break;
          case 'add-css' : Controler::getWindow()->addJS($oChild->getValue()); break;
          case 'content-title' : Controler::getWindow()->getBloc('content-title')->add(t($oChild->getValue())); break;
        }
      }
    }
    
    $oDocument = new XML_Document($this->get('document/*'));
    $oTemplate = new XML_Document($this->get('template/*'));
    
    if (!$oDocument->isEmpty()) {
      
      if (!$oTemplate->isEmpty()) $oResult = $oDocument->parseXSL($oTemplate);
      else $oResult = $oDocument;
      
      return $oResult;
      
    } else return $this; //$oResult = new XML_Document;
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

class Action extends XML_Tag {
  
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
}

