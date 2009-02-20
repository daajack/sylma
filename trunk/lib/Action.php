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
    
    if (!$oDocument->isEmpty()) return $this->loadActionSettings($oDocument, $oRedirect);
  }
  
  public function loadActionSettings($oElement, $oRedirect = null) {
    
    $sClass = $oElement->read('class/name');
    $sFile = $oElement->read('class/file');
    $sMethod = $oElement->read('method');
    $bRedirect = $oElement->test("@redirect='true'");
    
    if ($bRedirect) $oRedirect = null;
    
    return $this->runAction($sClass, $sMethod, $sFile, $oRedirect);
  }
  
  public function runAction($sClassName, $sMethodName, $sFile = '', $oRedirect = null) {
    
    if ($sFile) {
      
      // Include du fichier
      $sFile = Controler::getDirectory().$sFile;
      
      if (file_exists($sFile)) require_once($sFile);
      else if (Controler::isAdmin()) Controler::addMessage(sprintf(t('Fichier "%s" introuvable !'), $sFile));
    }
    
    // Contrôle de l'existence de la classe et de l'opération
    
    if (Controler::isAdmin()) $sClassError = sprintf(t('Action impossible (la classe "%s" n\'existe pas) !'), new HTML_Strong($sClassName));
    else $sClassError = t('Page introuvable, veuillez corriger l\'adresse !');
    
    if (!class_exists($sClassName)) Controler::errorRedirect($sClassError);
    
    // Création de la classe
    
    $oAction = new $sClassName($oRedirect);
    
    if ($sMethodName) {
      
      // Création de la méthode
      
      if (Controler::isAdmin()) $sOperationError = sprintf(t('Action impossible (la méthode "%s" n\'existe pas) !'), new HTML_Strong(Controler::getClassName().'::'.$sMethodName.'()'));
      else $sOperationError = t('Page introuvable, veuillez corriger l\'adresse !');
      
      if (!method_exists($oAction, $sMethodName)) Controler::errorRedirect($sOperationError);
      else $oResult = $oAction->$sMethodName($oRedirect);
      
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
    
    $oDocument = new XML_Document($this->get('document/*'));
    $oTemplate = new XML_Document($this->get('template/*'));
    
    if (!$oDocument->isEmpty()) {
      
      if (!$oTemplate->isEmpty()) $oResult = $oDocument->parseXSL($oTemplate);
      else $oResult = $oDocument;
      
      return $oResult;
      
    } else return $this; //$oResult = new XML_Document;
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
  
  protected function checkRequest($aSchema = array()) {
    
    $aMsg = array();
    
    if (!$aSchema) $aMsg[] = new Message(t('Le contrôle des champs n\'a pu s\'effectuer correctement. Impossible d\'enregistrer !', 'error'));
    
    foreach ($aSchema as $sKey => $aField) {
      
      // Si le paramètre 'deco' est à true, la valeur n'est pas contrôlée
      
      if (isset($aField['deco']) && $aField['deco']) continue;
      
      $oTitle = new HTML_Tag('strong');
      $oTitle->addChild($aField['title']);
      
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

