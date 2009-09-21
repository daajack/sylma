<?php

/**
 * Contrôleur général du framwork
 */
class Controler {
  
  private static $bReady = false;
  private static $iStartTime = 0;
  
  private static $oMessages = null;
  private static $oUser = null;
  private static $oDirectory = null;
  
  private static $oWindow;
  private static $oWindowSettings;
  private static $oSettings;
  
  private static $oPath = null;      // Chemin complet du fichier. Ex: /utilisateur/edit/1
  private static $aPaths = array(); // Liste des précédents chemins redirigés, ajoutés dans oRedirect
  private static $sAction = '';     // Chemin de l'action. Ex: /utilisateur/edit
  
  public static function trickMe() {
    
    global $aDefaultInitMessages;
    
    self::$iStartTime = microtime(true);
    
    self::$oMessages = new Messages();
    self::$oDirectory = new XML_Directory('', '', array('owner' => 'root', 'group' => '0', 'mode' => '700', 'user-mode' => null));
    
    // Authentification : récupération du cookie User
    
    self::setUser(self::loadUser());
    self::setLevelReport();
    
    // Loading general parameters
    
    self::loadSettings();
    
    // Parse of the url
    
    self::loadContext();
    
    // Creation of the window
    
    $sWindow = ucfirst(self::getWindowType());
    self::setWindow(new $sWindow);
    
    if (($sExtension = Controler::getPath()->getExtension()) &&
     (!in_array($sExtension, array('eml', 'iml')) || strtobool(self::getPath()->getAssoc('no-action'))) && 
     ($oFile = self::getFile(Controler::getPath().'.'.$sExtension)) &&
     $oFile->checkRights(MODE_READ)) {
      
      /* A file */
      
      self::getWindow()->loadAction($oFile);
      
    } else if (in_array(self::getPath()->getExtension(), array('', 'eml', 'htm', 'html', 'xml', 'txt', 'popup', 'action'))) {
      
      /* An action */
      
      // Récupération du cookie Redirect qui indique qu'une redirection a été effectuée
      
      $oRedirect = self::loadRedirect();
      
      // Load file of the interface's paths
      
      Action_Controler::loadInterfaces();
      
      // Get then send the action
      
      self::getPath()->parsePath();
      $oResult = self::getWindow()->loadAction(new XML_Action(self::getPath(), $oRedirect));
      
      if (is_object($oResult) && $oResult instanceof Redirect) {
        
        // Redirection
        
        if (self::isWindowType('html') || self::isWindowType('redirection')) self::doHTTPRedirect($oResult);
        else self::doAJAXRedirect($oResult);
      }
    }
    
    return self::getWindow();
  }
  
  private static function setLevelReport() {
    
    if (self::isAdmin()) {
      
      // debug or not debug..
      if (DEBUG) error_reporting(E_ALL);
      else error_reporting(ERROR_LEVEL);
      
    } else error_reporting(0);
  }
  
  private static function loadSettings() {
    
    self::$oSettings = new XML_Document(PATH_SETTINGS, MODE_EXECUTION);
    
    $oAllowed = new XML_Document(self::getSettings('messages/allowed/@path'));
    
    $aMessages = self::getMessages()->getMessages();
    self::$oMessages = new Messages($oAllowed, $aMessages);
    
    self::$bReady = true;
  }
  
  public static function getSettings($sQuery = '') {
    
    if ($sQuery) return self::$oSettings->read($sQuery);
    return self::$oSettings;
  }
  
  private static function loadContext() {
    
    if (isset($_GET['q']) && $_GET['q']) {
      
      $sPath = $_GET['q'];
      unset($_GET['q']);
      
    } else $sPath = '/';
    
    // L'extension (si elle est correct) indique le type de fenêtre
    
    $oPath = new XML_Path('/'.$sPath, false, $_GET);
    if (!$sExtension = $oPath->parseExtension(true)) $sExtension = 'html';
    $sExtension = strtolower($sExtension);
    
    if (self::isAdmin() && $oPath->getIndex(0, true) == 'show-report') {
      
      $oPath->getIndex();
      self::getMessages()->get('action')->addNode('report');
    }
    
    self::$oPath = $oPath;
    
    $oWindow = self::getSettings()->get("window/*[extensions[contains(text(), '$sExtension')]]");
    if (!$oWindow) $oWindow = new XML_Element('any');
    //$oWindow = self::getSettings()->get('window/html');
    
    self::$oWindowSettings = $oWindow;
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
        
        $oMessages = $oRedirect->getDocument('messages');
        // $oMessages = new XML_Document;
        // $oMessages->loadText($oRedirect->getArgument('messages'));
        $aMessages = $oMessages->query('//lm:message', 'lm', NS_MESSAGES);
        
        $oRedirect->resetMessages($aMessages);
        
        if ($aMessages->length) self::getMessages()->addMessages($aMessages);
        
      } else {
        
        $oRedirect = new Redirect();
        self::addMessage(t('Session Redirect perdu !'), 'warning');
      }
    }
    
    return $oRedirect;
  }
  
  private static function getMime($sExtension) {
    
    switch (strtolower($sExtension)) {
      
      case 'jpg' : $sExtension = 'jpeg';
      case 'jpeg' :
      case 'png' :
      case 'gif' : return 'image/'.$sExtension;
      
      case 'js' : return 'application/javascript';
      case 'css' : return 'text/css';
      case 'xml' :
      case 'xsl' : return 'text/xml';
      
      default : return 'plain/text';
    }
  }
  
  public static function setContentType($sExtension) {
    
    header('Content-type: '.self::getMime($sExtension));
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
    else $oView->addMultiItem($oMessage, new HTML_Tag('em', t('- aucune -')));
    
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
  
  public static function error404() {
    
    header('HTTP/1.0 404 Not Found');
    echo 'Erreur 404 :\'(';
    exit;
  }
  
  private static function doHTTPRedirect($oRedirect) {
    
    self::doRedirect($oRedirect);
    
    // Redirection
    
    $sPath = (string) $oRedirect;
    
    if ($sPath) {
      
      header("Location: $sPath");
      exit;
      
    } else self::errorRedirect('Redirection incorrect !');
    
  }
  
  private static function doRedirect($oRedirect) {
    
    // Récupération et ajout dans le Redirect des messages en attente
    
    $oRedirect->getMessages()->addMessages(self::getMessages()->getMessages());
    
    // Ajout des messages requêtes si admin
    
    // if (self::isAdmin()) $oRedirect->getMessages()->addMessages(db::getQueries('old')->getMessages());
    
    $oRedirect->setDocument('messages', $oRedirect->getMessages());
    $oRedirect->setSource(Controler::getPath());
    
    // Redirection
    
    $_SESSION['redirect'] = serialize($oRedirect);
  }
  
  public static function formatResource($mArgument, $bDecode = false, $iMaxLength = 120, $bElementDisplay = false) {
    
    if (FORMAT_MESSAGES) {
      
      if (is_string($mArgument))
        $aValue = array("'".stringResume(htmlspecialchars($mArgument), $iMaxLength)."'", '#999');
      else if (is_bool($mArgument))
        $aValue = $mArgument ? array('TRUE', 'green') :  array('FALSE', 'red');
      else if (is_numeric($mArgument))
        $aValue = array($mArgument, 'green');
      else if (is_array($mArgument))
        $aValue = array(xt('array(%s)', new HTML_Strong(count($mArgument))), 'orange');
      else if (is_object($mArgument)) {
        
        // Objects
        
        if ($mArgument instanceof XML_Element) {
          
          if ($bElementDisplay) $oContainer = $mArgument->view(true, true, $bDecode);
          else $oContainer = new HTML_Span();
          
          $oContainer->addClass('hidden');
          
          $aValue = array(new HTML_Div(array(
            strtoupper($mArgument->getName()),
            $oContainer), array('class' => 'element')), 'blue');
          
        } else if ($mArgument instanceof XML_NodeList) {
          
          $aValue = array(xt('XML_NodeList(%s)', new HTML_Strong($mArgument->length)), 'green');
          
        } else {
          
          $sValue = get_class($mArgument);
          if (in_array($sValue, array('XML_Directory', 'XML_File'))) $sValue = stringResume($mArgument, 150);
          
          $aValue = array($sValue, 'red');
        }
        
      } else if ($mArgument === null) $aValue = array('NULL', 'magenta');
      else $aValue = array('undefined', 'orange');
      
      return new HTML_Div($aValue[0], array('style' => 'display: inline; color: '.$aValue[1].';'));
      
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
          
          $aArguments[] = self::formatResource($mArgument, false, $iMaxLength);
          $aArguments[] = new HTML_Strong(', ');
        }
        
        if ($aArguments) array_pop($aArguments);
        
        $oArguments = new HTML_Span($aArguments);
      }
      
      if (FORMAT_MESSAGES) {
        
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
    
    Controler::addMessage($mMessages, $sStatut);
    //$oRedirect = new Redirect(PATH_ERROR, new Message($aMessages, $sStatut));
    //echo Controler::getBacktrace();
    self::doHTTPRedirect(new Redirect(PATH_ERROR));
    // else echo 'Aucun message'.new HTML_Br;
    
    // self::doHTTPRedirect(new Redirect(PATH_ERROR, new Message($aMessages, $sStatut)));
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
  
  public static function getAbsolutePath($sTarget, $sSource) {
    
    if ($sTarget{0} == '/') return $sTarget;
    else return $sSource.$sTarget;
  }
  
  public static function setWindow($oWindow) {
    
    self::$oWindow = $oWindow;
  }
  
  public static function getWindowSettings() {
    
    return self::$oWindowSettings;
  }
  
  public static function getWindow() {
    
    return self::$oWindow;
  }
  
  public static function getWindowType() {
    
    return self::getWindowSettings()->getName(true);
  }
  
  public static function isWindowType($sWindowType) {
    
    return (self::getWindowType() == $sWindowType);
  }
  
  public static function getMessages() {
    
    return self::$oMessages;
  }
  
  public static function addMessage($mMessage = '- message vide -', $sPath = 'notice', $aArgs = array()) {
    
    // if (in_array($sPath, array('action/error', 'file/error'))) $mMessage = array($mMessage, Controler::getBacktrace());
    
    self::getMessages()->addMessage(new Message($mMessage, $sPath, $aArgs));
  }
  
  public static function useStatut($sStatut) {
    
    return self::getMessages()->useStatut($sStatut);
  }
  
  public static function buildSpecials() {
    
    if (!$oDirectory = self::getDirectory(PATH_INTERFACES)) {
      
      self::addMessage(xt('Le répértoire des interfaces "%s" n\'existe pas !', new HTML_Strong(PATH_INTERFACES)), 'action/warning');
      
    } else {
      
      $oInterfaces = $oDirectory->browse(array('iml'));
      
      if (!$aInterfaces = $oInterfaces->query('//file')) {
        
        self::addMessage(xt('Aucun fichier d\'interface à l\'emplacement "%s" indiqué !', new HTML_Strong(PATH_INTERFACES)), 'action/warning');
        
      } else {
        
        $oIndex = new XML_Document('interfaces');
        
        foreach ($aInterfaces as $oFile) {
          
          $sPath = $oFile->getAttribute('full-path');
          $oInterface = new XML_Document($sPath, MODE_EXECUTION);
          
          if ($oInterface->isEmpty()) {
            
            self::addMessage(xt('Fichier d\'interface "%s" vide', new HTML_Strong($sPath)), 'action/warning');
            
          } else {
            
            if (!$sName = $oInterface->read('ns:name')) {
              
              self::addMessage(xt('Fichier d\'interface "%s" invalide, aucune classe n\'est indiquée !', new HTML_Strong($sPath)), 'action/warning');
              
            } else {
              
              $oIndex->addNode('interface', $sPath, array('class' => $sName));
            }
          }
        }
        
        $sPath = PATH_INTERFACES.'/../interfaces.cml';
        $oPath = new XML_Path($sPath, false);
        
        $oIndex->save($oPath);
        self::addMessage(xt('Interface d\'actions %s regénéré !', $oPath->parse()), 'success');
      }
    }
  }
  
  public static function getAction() {
    
    return self::$sAction;
  }
  
  public static function getPath() {
    //echo self::getBacktrace();
    return self::$oPath;
  }
  
  public static function browseDirectory($aAllowedExt = array(), $aExcludedPath = array(), $iMaxLevel = null, $sOriginPath = '') {
    
    $oDocument = new XML_Document(self::getDirectory()->browse($aAllowedExt, $aExcludedPath, $iMaxLevel));
    
    if ($oDocument && !$oDocument->isEmpty()) $oDocument->getRoot()->setAttribute('path_to', $sOriginPath);
    
    return $oDocument;
  }
  
  public static function getDirectory($sPath = '') {
    
    if ($sPath && $sPath != '/') {
      
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
  private $sWindowType = 'html';
  private $bIsReal = false; // Défini si le cookie a été redirigé ou non
  
  private $aArguments = array();
  private $aDocuments = array();
  private $oMessages;
  
  public function __construct($sPath = '', $mMessages = array(), $aArguments = array()) {
    
    $this->resetMessages($mMessages);
    
    if ($sPath) $this->setPath($sPath);
    
    $this->aArguments = $aArguments;
    $this->setArgument('post', $_POST);
    $this->setWindowType(Controler::getWindowType());
  }
  
  public function getArgument($sKey) {
    
    return (array_key_exists($sKey, $this->aArguments)) ? $this->aArguments[$sKey] : null;
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
  
  public function getDocument($sKey) {
    
    return (array_key_exists($sKey, $this->aDocuments)) ? $this->aDocuments[$sKey] : null;
  }
  
  public function setDocument($sKey, $oDocument) {
    
    $this->aDocuments[$sKey] = $oDocument;
  }
  
  public function resetMessages($mMessages = array()) {
    
    $this->oMessages = new Messages(new XML_Document(Controler::getSettings('messages/allowed/@path')), $mMessages);
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
  
  public function __sleep() {
    
    foreach ($this->aDocuments as $sKey => $oDocument) $this->aDocuments[$sKey] = (string) $oDocument;
    return array_keys(get_object_vars(&$this));
  }
  
  public function __wakeup() {
    
    foreach ($this->aDocuments as $sKey => $sDocument) $this->aDocuments[$sKey] = new XML_Document($sDocument);
  }
  
  public function __toString() {
    
    return (string) $this->oPath;
  }
}
