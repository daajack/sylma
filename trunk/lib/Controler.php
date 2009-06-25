<?php

/**
 * Contrôleur général du framwork
 */
class Controler {
  
  private static $oMessages = null;
  private static $bReady = false;
  private static $oUser = null;
  private static $oDirectory = null;
  private static $aArguments = array();
  private static $aRights = array();
  
  private static $aAllowedWindowType = array();
  private static $sWindowType = 'html';
  
  private static $oWindow;
  private static $oSettings;
  
  private static $oPath = null;      // Chemin complet du fichier. Ex: /utilisateur/edit/1
  private static $aPaths = array(); // Liste des précédents chemins redirigés, ajoutés dans oRedirect
  private static $sAction = '';     // Chemin de l'action. Ex: /utilisateur/edit
  
  private static $iStartTime = 0;
  
  public static function trickMe() {
    
    global $aDefaultInitMessages;
    
    self::$iStartTime = microtime(true);
    
    self::$oMessages = new Messages();
    self::$oDirectory = new XML_Directory('', '', array('owner' => 'root', 'group' => '0', 'mode' => '700'));
    
    // Récupération du cookie User : authentification
    
    self::setUser(self::loadUser());
    
    $aAllowedMessages = self::loadSettings();
    
    // Formatage de l'adresse
    
    self::loadContext();
    
    // Création du type de fenêtre
    
    $sWindow = ucfirst(self::getWindowType());
    self::setWindow(new $sWindow);
    
    if (!self::isWindowType('img')) {
      
      // Récupération du cookie Redirect qui indique qu'une redirection a été effectuée
      
      $oRedirect = self::loadRedirect();
      
      // DEBUG
      
      if (self::isAdmin()) {
        
        if (DEBUG) error_reporting(E_ALL);
        else error_reporting(ERROR_LEVEL);
        
      } else error_reporting(0);
      
      // Chargement des interfaces
      Action_Controler::loadInterfaces();
      
      // Include de la classe d'action
      
      self::getPath()->parsePath();
      $oResult = self::getWindow()->loadAction(new XML_Action(self::getPath(), $oRedirect));
      
      /*** Lancement de l'action, récupuration du contenu / redirect ***/
      
      // Redirection ou ajout du contenu
      
      if (is_object($oResult) && $oResult instanceof Redirect) {
        
        // Redirection
        
        if (self::isWindowType('html') || self::isWindowType('redirection')) self::doHTTPRedirect($oResult);
        else self::doAJAXRedirect($oResult);
      }
    }
    
    return self::getWindow();
  }
  
  // Ajout des infos système
  
  private static function loadSettings() {
    
    self::$oSettings = new XML_Document('/xml/root.xml', MODE_EXECUTION);
    //self::$oSettings->dsp();
    // $oSettings->add(new XML_Document('/xml/actions.xml', 'file'));
    // $oSettings->addNode('users', implode(',', array('root', 'john', 'serge', 'daajack')));
    // $oSettings->addNode('groups', implode(',', array('0', 'lemon', 'team', 'dev')));
    
    // $aAllowed = explode(',', self::$oSettings->read('//messages/allowed'));
    
    $oAllowed = new XML_Document(self::getSettings('messages/allowed/@path'));
    
    $aMessages = self::getMessages()->getMessages();
    self::$oMessages = new Messages($oAllowed, $aMessages);
    
    self::$aAllowedWindowType = self::$oSettings->query('//window/*')->toArray('name');
    
    // self::setArgument('settings', $oSettings);
    self::setReady();
    
    return $oAllowed;
  }
  
  private static function loadContext() {
    
    $sPath = (isset($_GET['q']) && $_GET['q']) ? $_GET['q'] : '/';
    array_shift($_GET);
    
    // L'extension (si elle est correct) indique le type de fenêtre
    
    $oPath = new XML_Path('/'.$sPath, false, $_GET);
    $sExtension = $oPath->parseExtension(true);
    
    self::$oPath = $oPath;
    
    if (in_array($sExtension, self::$aAllowedWindowType)) self::setWindowType($sExtension);
  }
  
  private static function loadRedirect() {
    
    $oRedirect = new Redirect();
    
    // Une redirection a été effectuée
    
    if (array_key_exists('redirect', $_SESSION)) {
      
      $oRedirect = unserialize($_SESSION['redirect']);
      unset($_SESSION['redirect']);
      
      // Récupération des messages du Redirect et suppression
      
      if (get_class($oRedirect) == 'Redirect') {
        
        $oRedirect->setReal();
        
        $oMessages = new XML_Document;
        $oMessages->loadText($oRedirect->getArgument('messages'));
        $aMessages = $oMessages->query('//message');
        
        $oRedirect->resetMessages($aMessages);
        
        if ($aMessages->length) self::getMessages()->addMessages($aMessages);
        
      } else {
        
        $oRedirect = new Redirect();
        self::addMessage(t('Session Redirect perdu !'), 'warning');
      }
    }
    
    return $oRedirect;
  }
  
  public static function checkAuthentication($sPath = null) {
    
    if (!$sPath) $sPath = self::getAction();
    
    $aAskRights = self::getActionRights($sPath);
    
    $aExceptions = array(PATH_LOGIN, PATH_LOGIN_DO, PATH_ERROR, PATH_ACCESS);
    
    if (in_array($sPath, $aExceptions)) return true;
    
    if (self::getUser()->isMember('0')) return true;
    
    if (in_array('*', $aAskRights)) return true;
    if (self::getUser()->isReal() && in_array('#', $aAskRights)) return true;
    
    foreach ($aAskRights as $sRight) if (self::getUser()->isMember($sRight)) return true;
    
    return false;
  }
  
  private static function getActionRights($sPath = null) {
    
    if (!$sPath) $sPath = self::getAction();
    
    $aActionRights = isset(self::$aRights[$sPath]['rights']) ? self::$aRights[$sPath]['rights'] : array();
    
    if (!is_array($aActionRights)) $aActionRights = array($aActionRights);
    
    return $aActionRights;
  }
  
  private static function getContent($oAction, $oRedirect) {
    
    $sOperationName = self::getOperationName();
    
    // Création de la méthode
    
    if (self::isAdmin()) $sOperationError = sprintf(t('Action impossible (la méthode "%s" n\'existe pas) !'), new HTML_Strong(self::getClassName().'::'.$sOperationName.'()'));
    else $sOperationError = t('Page introuvable, veuillez corriger l\'adresse !');
    
    if (!method_exists($oAction, $sOperationName)) self::errorRedirect($sOperationError);
    else $oResult = $oAction->$sOperationName($oRedirect);
    
    return $oResult;
  }
  
  public static function getSystemInfos($oRedirect) {
    
    $oView = new HTML_Ul(null, array('id' => 'system'));
    
    $oMessage = new HTML_Strong(t('Authentification').' : ');
    
    if (self::getUser()->isReal()) $oView->addMultiItem($oMessage, self::getUser()->getArgument('full_name'));
    else $oView->addMultiItem($oMessage, new HTML_Tag('em', t('- aucun -')));
    
    if (self::getUser()->isReal()) {
      
      $oMessage = new HTML_Strong(t('Groupe(s)').' : ');
      
      if (self::getUser()->getGroups()) $oView->addMultiItem($oMessage, implode(', ', self::getUser()->getGroups()));
      else $oView->addMultiItem($oMessage, new HTML_Tag('em', t('- aucun -')));
    }
    
    $oView->addMultiItem(new HTML_Strong(t('Adresse').' : '), self::getPath());
    
    $oMessage = new HTML_Strong(t('Redirection').' : ');
    
    if ($oRedirect->isReal()) $oView->addMultiItem($oMessage, $oRedirect->getSource()->getOriginalPath());
    else $oView->addMultiItem($oMessage, new HTML_Tag('em', t('- aucun -')));
    
    $oView->addMultiItem(new HTML_Strong(t('Fenêtre').' : '), self::getWindowType());
    $oView->addMultiItem(new HTML_Strong(t('Date & heure').' : '), date('j M Y').' - '.date('H:i'));
    $oView->addMultiItem(new HTML_Strong(t('Statistiques XML').' : '), XML_Controler::viewStats());
    $oView->addMultiItem(new HTML_Strong(t('Temps d\'exécution').' : '), number_format(microtime(true) - self::$iStartTime, 3).' s');
    
    return $oView;
  }
  
  private static function doAJAXRedirect($oRedirect) {
    
    self::doRedirect($oRedirect);
    self::getWindow()->setRedirect($oRedirect);
  }
  
  private static function doHTTPRedirect($oRedirect) {
    
    self::doRedirect($oRedirect);
    
    // Redirection
    header("Location: $oRedirect");
    
    exit;
  }
  
  private static function doRedirect($oRedirect) {
    
    // Récupération et ajout dans le Redirect des messages en attente
    
    $oRedirect->getMessages()->addMessages(self::getMessages()->getMessages());
    
    // Suppression des infos système
    
    if ($oSystem = $oRedirect->getMessages()->get('system')) $oSystem->remove();
    
    // Ajout des messages requêtes si admin
    
    // if (self::isAdmin()) $oRedirect->getMessages()->addMessages(db::getQueries('old')->getMessages());
    
    $oRedirect->setArgument('messages', $oRedirect->getMessages()->saveXML());
    
    $oRedirect->setSource(Controler::getPath());
    
    // Redirection
    
    $_SESSION['redirect'] = serialize($oRedirect);
  }
  
  public static function formatResource($mArgument, $iMaxLength = 120, $bFormat = true) {
    
    if (FORMAT_MESSAGES && $bFormat) {
      
      if (is_string($mArgument))
        $aValue = array("'".stringResume($mArgument, $iMaxLength)."'", '#999');
      else if (is_array($mArgument))
        $aValue = array(xt('array(%s)', new HTML_Strong(count($mArgument))), 'black');
      else if (is_object($mArgument)) {
        
        if ($mArgument instanceof XML_Element)
          $aValue = array($mArgument->viewResume($iMaxLength, true), 'blue');
        else if ($mArgument instanceof XML_NodeList)
          $aValue = array(xt('XML_NodeList(%s)', new HTML_Strong($mArgument->length)), 'green');
        else 
          $aValue = array(get_class($mArgument), 'red');
        
      } else if ($mArgument === null) $aValue = array('NULL', 'magenta');
      else $aValue = array('undefined', 'orange');
      
      return new HTML_Span($aValue[0], array('style' => 'color: '.$aValue[1].';'));
      
    } else {
      
      if (is_string($mArgument))
        $sValue = "'".stringResume($mArgument, $iMaxLength)."'";
      else if (is_array($mArgument))
        $sValue = 'array('.count($mArgument).')';
      else if (is_object($mArgument)) {

        if ($mArgument instanceof XML_NodeList)
          $sValue = 'XML_NodeList('.$mArgument->length.')';
        else 
          $sValue = array(get_class($mArgument), 'red');
      } else if ($mArgument === null) $sValue = 'NULL';
      else $sValue = 'undefined';
      
      return $sValue;
    }
  }
  
  public static function getBacktrace($bFormat = true) {
    
    $aResult = array(); $aLines = array(); $i = 0;
    
    $aBackTrace = debug_backtrace();
    array_shift($aBackTrace);
    
    // if (DEBUG) dsp($aBackTrace);
    // return null;
    
    foreach ($aBackTrace as $aLine) {
      
      if (isset($aLine['line'])) $aLines[] = $aLine['line'];
      else $aLines[] = 'k';
    }
    
    $aLines[] = 'x';
    
    foreach ($aBackTrace as $aTrace) {
      
      if (isset($aTrace['file'])) $sFile = new HTML_Tag('u', strrchr($aTrace['file'], DIRECTORY_SEPARATOR));
      else $sFile = 'xxx';
      
      if (isset($aTrace['class'])) $sClass = "::{$aTrace['class']}";
      else $sClass = '';
      
      // Arguments
      
      $oArguments = null;
      
      if (isset($aTrace['args']) && $aTrace['args']) {
        
        $aArguments = array();
        $iMaxLength = 120 / count($aTrace['args']);
        
        foreach ($aTrace['args'] as $mArgument) {
          
          $aArguments[] = self::formatResource($mArgument, $iMaxLength, $bFormat);
          $aArguments[] = new HTML_Strong(', ');
        }
        
        if ($aArguments) array_pop($aArguments);
        
        $oArguments = new HTML_Span($aArguments);
      }
      
      if (FORMAT_MESSAGES && $bFormat) {
        
        $aResult[] = new HTML_Div(array(
          '[',
          new HTML_Span($aLines[$i], array('style' => 'color: blue; font-weight: bold;')),
          '] ',
          $sFile,
          $sClass,
          '::',
          new HTML_Strong($aTrace['function']),
          '(',  $oArguments, ')'));
          
      } else {
        
        $aResult[] = '['.$aLines[$i].'] '.$sFile.$sClass.'::'.$aTrace['function'].'(no display)'.new HTML_Br;
      }
      
      $i++;
    }
    
    return new HTML_Div(array_reverse($aResult), array('style' => 'margin: 3px; padding: 3px; border: 1px solid white; border-width: 1px 0 1px 0; font-size: 0.8em'));
    // self::addMessage(new HTML_Strong(t('Backtrace').' : ').implode('<br/>', $aResult), $sStatut);
    // return new XML_NodeList($aResult);
  }
  
  /* *** */
  
  public static function accessRedirect($mMessages = '', $sPath = PATH_LOGIN, $sStatut = 'warning') {
    
    if (!$mMessages) $mMessages = sprintf(t('Vous n\'avez pas les droits pour accéder à cette page "%s" !'), self::getPath());
    
    if (Controler::getUser()->isReal()) $sPath = PATH_ACCESS;
    
    if (is_string($mMessages)) $mMessages = new Message($mMessages, $sStatut);
    
    self::doHTTPRedirect(new Redirect($sPath, $mMessages));
  }
  
  public static function errorRedirect($mMessages = null, $sStatut = 'error') {
    
    $aMessages = array($mMessages);
    if (self::isAdmin()) $aMessages[] = self::getBacktrace();
    
    self::doHTTPRedirect(new Redirect(PATH_ERROR, new Message($aMessages, $sStatut)));
  }
  
  public static function setUser($oUser = null) {
    
    if (is_object($oUser) && get_class($oUser) == 'User') self::$oUser = $oUser;
  }
  
  private static function loadUser() {
    
    // Une redirection a été effectuée
    
    $oAnonymous = new User('anonymous', array('web', 'famous'), array('full_name' => 'Anonymous'));
    
    if (array_key_exists('user', $_SESSION)) {
      
      // self::addMessage(t('Session existante'), 'report');
      
      $oUser = unserialize($_SESSION['user']);
      
      // Récupération des messages du Redirect et suppression
      
      if (!($oUser instanceof User)) {
        
        $oUser = $oAnonymous;
        
        unset($_SESSION['user']);
        self::addMessage(t('Session utilisateur perdue !'), 'warning');
      }
      
    } else $oUser = $oAnonymous;
    
    return $oUser;
  }
  
  public static function getUser() {
    
    return self::$oUser;
  }
  
  public static function getMessages() {
    
    return self::$oMessages;
  }
  
  public static function addMessage($mMessage = '- message vide -', $sCategory = 'notice', $aArgs = array()) {
    
    //if ($sStatut == 'error' && in_array($sCategory, array('action', 'xml'))) $mMessage = array($mMessage, Controler::getBacktrace());
    
    self::getMessages()->addMessage(new Message($mMessage, $sCategory, $aArgs));
  }
  
  public static function useStatut($sStatut) {
    
    return self::getMessages()->useStatut($sStatut);
  }
  
  public static function setWindow($oWindow) {
    
    self::$oWindow = $oWindow;
  }
  
  public static function getSettings($sQuery = '') {
    
    if ($sQuery) return self::$oSettings->read($sQuery);
    return self::$oSettings;
  }
  
  public static function getWindow() {
    
    return self::$oWindow;
  }
  
  public static function getWindowType() {
    
    return self::$sWindowType;
  }
  
  public static function isWindowType($sWindowType) {
    
    return (self::$sWindowType == $sWindowType);
  }
  
  public static function setWindowType($sWindowType) {
    
    self::$sWindowType = $sWindowType;
  }
  
  public static function getAction() {
    
    return self::$sAction;
  }
  
  public static function getArgument($sKey = 0) {
    
    return isset(self::$aArguments[$sKey]) ? self::$aArguments[$sKey] : null;
  }
  
  public static function getArguments() {
    
    return self::$aArguments;
  }
  
  public static function setArgument($sKey, $oValue) {
    
    self::$aArguments[$sKey] = $oValue;
  }
  
  public static function addArguments($aArguments) {
    
    self::$aArguments += $aArguments;
  }
  
  public static function setArguments($aArguments) {
    
    self::$aArguments = $aArguments;
  }
  
  public static function getPath() {
    //echo self::getBacktrace();
    return self::$oPath;
  }
  
  public static function browseDirectory($aAllowedExt = array(), $aExcludedPath = array(), $iMaxLevel = null, $sOriginPath = '') {
    
    $oDocument = new XML_Document(self::getDirectory()->browse($aAllowedExt, $aExcludedPath, $iMaxLevel));
    $oDocument->getRoot()->setAttribute('path_to', $sOriginPath);
    
    return $oDocument;
  }
  
  public static function getDirectory($sPath = '') {
    
    if ($sPath) {
      
      $aPath = explode('/', $sPath);
      array_shift($aPath);
      
      return self::$oDirectory->getDistantDirectory($aPath);
      
    } else return self::$oDirectory;
  }
  
  public static function getFile($sPath, $bDebug = false) {
    
    $aPath = explode('/', $sPath);
    array_shift($aPath);
    
    return self::getDirectory()->getDistantFile($aPath, $bDebug);
  }
  
  public static function isAdmin() {
    
    if (DEBUG) return true;
    else if (self::getUser()) return self::getUser()->isMember('0');
    else return false;
  }
  
  public static function getClassName() {
    
    return self::$sClassName;
  }
  
  public static function getOperationName() {
    
    return self::$sOperationName;
  }
  
  public static function setReady($bValue = true) {
    
    self::$bReady = $bValue;
  }
  
  public static function isReady() {
    
    return self::$bReady;
  }
  
  public static function getYAML($sFilePath) {
    
    return Spyc::YAMLLoad(MAIN_DIRECTORY.'/'.$sFilePath);
  }
}

class Redirect {
  
  private $sPath = '/'; // URL cible
  private $oPath = null; // URL cible
  private $oSource = null; // URL de provenance
  private $sWindowType = 'window';
  private $bIsReal = false; // Défini si le cookie a été redirigé ou non
  
  private $aArguments = array();
  private $oMessages;
  
  public function __construct($sPath = '', $mMessages = array(), $aArguments = array()) {
    
    $this->resetMessages($mMessages);
    
    if ($sPath) $this->setPath($sPath);
    $this->aArguments = $aArguments;
    $this->setArgument('post', $_POST);
    $this->setWindowType(Controler::getWindowType());
  }
  
  public function getArgument($sKey) {
    
    return (array_key_exists($sKey, $this->aArguments)) ? $this->aArguments[$sKey] : false;
  }
  
  public function setArgumentKey($sArgument, $sKey, $mValue = '') {
    
    $mArgument = $this->getArgument($sArgument);
    
    if (is_array($mArgument)) {
      
      if ($mValue) {
        
        $mArgument[$sKey] = $mValue;
        $this->setArgument($sArgument, $mArgument);
        
      } else unset($this->aArguments[$sArgument][$sKey]);
    }
  }
  
  public function setArgument($sKey, $mValue) {
    
    $this->aArguments[$sKey] = $mValue;
  }
  
  public function getArguments() {
    
    return $this->aArguments;
  }
  
  public function resetMessages($mMessages = array()) {
    
    $this->oMessages = new Messages(new XML_Document(Controler::getSettings('messages/allowed')), $mMessages);
    // $this->oMessages = new Messages(Controler::getMessages()->getAllowedMessages(), $mMessages); TODO
  }
  
  public function getMessages($sStatut = null) {
    
    if ($sStatut) return $this->oMessages->getMessages($sStatut);
    else return $this->oMessages;
  }
  
  public function addMessage($sMessage = '- message vide -', $sStatut = 'notice', $aArguments = array()) {
    
    $this->oMessages->addStringMessage($sMessage, $sStatut, $aArguments);
  }
  
  public function getPath() {
    
    return $this->oPath;
  }
  
  public function setPath($oPath) {
    
    $this->oPath = $oPath;
    return $oPath;
    // if ($sPath == '/' || $sPath != Controler::getPath()) $this->sPath = $sPath;
    // else Controler::errorRedirect(t('Un problème de redirection à été détecté !'));
  }
  
  public function getSource() {
    
    return $this->oSource;
  }
  
  public function setSource($oSource) {
    
    $this->oSource = $oSource;
    return $oSource;
  }
  
  public function isSource($sSource) {
    
    return ((string) $this->oSource == $sSource);
  }
  
  public function getWindowType() {
    
    return $this->sWindowType;
  }
  
  public function setWindowType($sWindowType) {
    
    $this->sWindowType = $sWindowType;
  }
  
  public function setReal($bValue = 'true') {
    
    $this->bIsReal = (bool) $bValue;
  }
  
  public function isReal() {
    
    return $this->bIsReal;
  }
  
  public function __toString() {
    
    return (string) $this->oPath;
  }
}
