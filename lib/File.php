<?php

class XML_Resource {
  
  protected $aRights = array();
  
  protected $sPath = '';
  protected $sName = '';
  protected $sFullPath = '';
  protected $oParent = null;
  
  private $bExist = false;
  private $iUserMode = null;
  
  public function doExist($bExist = null) {
    
    if ($bExist !== null) $this->bExist = $bExist;
    return $this->bExist;
  }
  
  public function getOwner() {
    
    return $this->aRights['owner'];
  }
  
  public function getGroup() {
    
    return $this->aRights['group'];
  }
  
  public function getMode() {
    
    return $this->aRights['mode'];
  }
  
  public function getName() {
    
    return $this->sName;
  }
  
  public function getFullPath() {
    
    return $this->sFullPath;
  }
  
  public function getParent() {
    
    return $this->oParent;
  }
  
  protected function setUserMode($iMode) {
    
    $this->iUserMode = $iMode;
  }
  
  protected function getUserMode() {
    
    return $this->iUserMode;
  }
  
  protected function getRights() {
    
    return $this->aRights;
  }
  
  protected function loadElementRights($oElement) {
    
    $iMode = null;
    
    if ($oSecurity = $oElement->get('security')) {
      
      if (Controler::useStatut('report')) Controler::addMessage(xt('Ressource "%s" sécurisée ', new HTML_Strong($this->getFullPath())), 'file/report');
      
      $sOwner = $oSecurity->read('ls:owner', 'ls', NS_SECURITY);
      $sGroup = $oSecurity->read('ls:group', 'ls', NS_SECURITY);
      $sMode = $oSecurity->read('ls:mode', 'ls', NS_SECURITY);
      
      $iMode = Controler::getUser()->getMode($sOwner, $sGroup, $sMode, $oSecurity);
      if ($iMode !== null) $this->aRights = array('owner' => $sOwner, 'group' => $sGroup, 'mode' => $sMode);
    }
    
    if ($iMode === null) $iMode = Controler::getUser()->getMode($this->getOwner(), $this->getGroup(), $this->getMode());
    
    $this->setUserMode($iMode);
  }
  
  public function __toString() {
    
    return $this->getFullPath();
  }
}

class XML_Directory extends XML_Resource {
  
  private $aDirectories = array();
  private $aFiles = array();
  private $oSettings = null;
  private $bSettingsFiles = false;
  
  public function __construct($sPath, $sName, $aRights = array(), $oParent = null) {
    
    $this->sFullPath = $sName ? $sPath.'/'.$sName : $sPath;
    
    if (is_dir(MAIN_DIRECTORY.$this->getFullPath())) {
      
      $this->aRights = $aRights;
      $this->sName = $sName;
      $this->sPath = $sPath;
      $this->oParent = $oParent;
      
      $this->doExist(true);
      $this->loadRights();
    }
  }
  
  public function browse($aExtensions, $aPaths = array(), $iDepth = null) {
    
    $aFiles = scandir(MAIN_DIRECTORY.$this->getFullPath(), 0);
    $oElement = $this->parse();
    
    foreach ($aFiles as $sFile) {
      
      if ($sFile != '.' && $sFile != '..') {
        
        if (($oFile = $this->getFile($sFile)) && $oFile->getUserMode() != 0) {
          
          if (!in_array($oFile->getExtension(), $aExtensions)) $oFile = null;
          else $oElement->add($oFile->parseXML());
          
        } else if ($oTempDirectory = $this->getDirectory($sFile)) {
          
          if ($iDepth === null || $iDepth--) {
            
            $bValid = true;
            
            foreach ($aPaths as $sPath) {
              
              switch ($sPath{0}) {
                
                case '/' : if ($sPath == $oTempDirectory->getFullPath()) $bValid = false; break;
                default : if ($sPath == $oTempDirectory->getName()) $bValid = false; break;
              }
            }
            
            if ($bValid) $oElement->add($oTempDirectory->browse($aExtensions, $aPaths, $iDepth));
          }
        }
      }
    }
    
    if ($oElement->isEmpty() && $this->getUserMode() != 0) return null;
    else return $oElement;
  }
  
  public function browseTemp($aExtensions, $aPaths, $iDepth = null) {
    
    $aFiles = scandir(MAIN_DIRECTORY.$this->getFullPath(), 0);
    $oElement = $this->parse();
    
    foreach ($aFiles as $sFile) {
      
      if ($sFile != '.' && $sFile != '..') {
        
        if (is_dir(MAIN_DIRECTORY.$this.'/'.$sFile) && $oTempDirectory = $this->getDirectory($sFile)) {
          
          if ($iDepth === null || $iDepth--) {
            
            $bValid = true;
            
            foreach ($aPaths as $sPath) {
              
              switch ($sPath{0}) {
                
                case '/' : if ($sPath == $oTempDirectory->getFullPath()) $bValid = false; break;
                default : if ($sPath == $oTempDirectory->getName()) $bValid = false; break;
              }
            }
            
            if ($bValid) $oElement->add($oTempDirectory->browse($aExtensions, $aPaths, $iDepth));
          }
          
        } else if (($oFile = $this->getFile($sFile)) && $oFile->getUserMode() != 0) {
          
          if (!in_array($oFile->getExtension(), $aExtensions)) $oFile = null;
          else $oElement->add($oFile);
        }
      }
    }
    
    if ($oElement->isEmpty() && $this->getUserMode() != 0) return null;
    else return $oElement;
  }
  
  public function getFile($sName, $bDebug = false) {
    
    if ($sName && is_string($sName)) {
      
      if (!array_key_exists($sName, $this->aFiles)) {
        
        $oFile = new XML_File($this->getFullPath(), $sName, $this->getRights(), $this, $bDebug);
        // echo 'CF : '.$oFile.new HTML_Br;//.Controler::getBacktrace().new HTML_Br;
        
        if ($oFile->doExist()) {
          
          if ((($oSettings = $this->getSettings()) && $this->bSettingsFiles) && ($oFileSettings = $oSettings->get("file[@name='$sName']")))
            $oFile->loadElementRights($oFileSettings);
          
          // if (isset($oFileSettings)) echo $oFileSettings->view(true);
          
          if (Controler::getUser()) $this->aFiles[$sName] = $oFile;
          else return $oFile;
          
        } else $this->aFiles[$sName] = null;
      }
      
      return $this->aFiles[$sName];
    }
    
    return null;
  }
  
  public function getDistantDirectory($aPath) {
    
    if ($aPath) {
      
      $sName = array_shift($aPath);
      
      $oSubDirectory = $this->getDirectory($sName);
      
      if ($oSubDirectory) return $oSubDirectory->getDistantDirectory($aPath);
      
    } else return $this;
    
    return null;
  }
  
  public function getDistantFile($aPath, $bDebug = true) {
    
    if ($aPath) {
      
      if (count($aPath) == 1) {
        
        return $this->getFile($aPath[0], $bDebug);
        
      } else {
        
        $sName = array_shift($aPath);
        
        $oSubDirectory = $this->getDirectory($sName);
        
        if ($oSubDirectory) return $oSubDirectory->getDistantFile($aPath, $bDebug);
      }
    }
    
    return null;
  }
  
  public function getSettings() {
    
    return $this->oSettings;
  }
  
  public function getDirectory($sName) {
    
    $this->loadRights();
    
    if ($sName == '.') return $this;
    else if ($sName == '..') return $this->getParent();
    else {
      
      if (!array_key_exists($sName, $this->aDirectories)) {
        
        $oDirectory = new XML_Directory($this->getFullPath(), $sName, $this->getRights(), $this);
        // echo 'CD : '.$oDirectory.new HTML_Br;//.Controler::getBacktrace().new HTML_Br;
        if ($oDirectory->doExist()) $this->aDirectories[$sName] = $oDirectory;
        else $this->aDirectories[$sName] = null;
      }
      
      return $this->aDirectories[$sName];
    }
    
    return null;
  }
  
  private function loadRights() {
    
    $iMode = $this->getUserMode();
    
    if ($iMode === null) {
      
      if (Controler::getUser()) {
        
        $sSettings = $this->getFullPath().'/directory.sml';
        
        if (file_exists(MAIN_DIRECTORY.$sSettings)) {
          
          $oSettings = new XML_Document();
          $oSettings->loadFreeFile($sSettings);
          
          $this->oSettings = $oSettings;
          if ($oSettings && $oSettings->get('//file')) $this->bSettingsFiles = true;
          
          $this->loadElementRights($oSettings, $iMode);
        }
       
      } else if (Controler::useStatut('report')) Controler::addMessage(xt('Sécurisation suspendue dans "%s"', new HTML_Strong($this->getFullPath())), 'file/report');
    }
  }
  
  public function checkRights($iMode) {
    
    $this->loadRights();
    
    if ($this->getUserMode() === null || ($iMode & $this->getUserMode())) return true;
    else Controler::addMessage(xt('Répertoire "%s" non authorisé !', new HTML_Strong()), 'file/warning');
    
    return false;
  }
  
  public function parse() {
    
    return new XML_Element('directory', null, array(
      'full-path' => $this->getFullPath(),
      'name' => $this->getName()));
  }
  
  public function __destruct() {
    
    // echo 'DD : '.$this.new HTML_Br;
    // echo $this.new HTML_Br.Controler::getBacktrace();
  }
  
  public function __toString() {
    
    return $this->getFullPath();
  }
}

class XML_File extends XML_Resource {
  
  private $oDocument = null;
  private $bSecured = false;
  private $sExtension = '';
  
  public function __construct($sPath, $sName, $aRights = array(), $oParent = null, $bDebug = true) {
    
    $this->sFullPath = $sName ? $sPath.'/'.$sName : $sPath;
    
    if (is_file(MAIN_DIRECTORY.$this->getFullPath())) {
      
      $this->aRights = $aRights;
      $this->sName = $sName;
      $this->sPath = $sPath;
      $this->oParent = $oParent;
      
      if ($iExtension = strrpos($sName, '.')) $this->sExtension = substr($sName, $iExtension + 1);
      else $this->sExtension = '';
      
      $this->doExist(true);
      
      if (Controler::getUser()) {
        
        $this->setUserMode(
          Controler::getUser()->getMode(
            $this->getOwner(),
            $this->getGroup(),
            $this->getMode()));
      }
      
    } else if ($bDebug) Controler::addMessage(xt('Fichier "%s" introuvable dans "%s" !', new HTML_Strong($sName), new HTML_Strong($sPath)), 'file/notice');
  }
  
  public function getExtension() {
    
    return $this->sExtension;
  }
  
  public function getDocument() {
    
    return $this->oDocument;
  }
  
  public function isSecured($bSecured = null) {
    
    if ($bSecured === null) return $this->bSecured;
    else $this->bSecured = $bSecured;
  }
  
  public function setDocument($oDocument) {
    
    $this->oDocument = $oDocument;
  }
  
  public function checkRights($iMode) {
    
    if ($this->getUserMode() === null || ($iMode & $this->getUserMode())) return true;
    else if (Controler::isAdmin()) Controler::addMessage(xt('Fichier "%s" : accès interdit !', new HTML_Strong($this->getFullPath())), 'file/error');
    
    return false;
  }
  
  public function __destruct() {
    
    // echo 'DF : '.$this.new HTML_Br;
    // echo $this.new HTML_Br.Controler::getBacktrace();
  }
  
  public function parse() {
    
    $sPath = $this->getFullPath();
    return new HTML_A(PATH_EDITOR.'?path='.$sPath, $sPath);
  }
  
  public function parseXML() {
    
    return new XML_Element('file', null, array(
      'full-path' => $this->getFullPath(),
      'name' => $this->getName(),
      'extension' => $this->getExtension()));
  }
}

