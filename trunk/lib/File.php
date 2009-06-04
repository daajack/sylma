<?php


class XML_Resource {
  
  protected $aRights = array();
  
  protected $sPath = '';
  protected $sName = '';
  protected $sFullPath = '';
  
  private $bExist = false;
  private $iUserMode = null;
  private $oParent = null;
  
  public function doExist($bExist = null) {
    
    if ($bExist !== null) $this->bExist = $bExist;
    return $this->bExist;
  }
  
  public function getRights() {
    
    return $this->aRights;
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
  
  protected function getParent() {
    
    return $this->oParent;
  }
  
  protected function setUserMode($iMode) {
    
    $this->iUserMode = $iMode;
  }
  
  protected function getUserMode() {
    
    return $this->iUserMode;
  }
}

class XML_Directory extends XML_Resource {
  
  private $aDirectories = array();
  private $aFiles = array();
  
  public function __construct($sPath, $sName, $aRights = array(), $oParent = null) {
    
    $this->sFullPath = $sName ? $sPath.'/'.$sName : $sPath;
    
    if (is_dir(MAIN_DIRECTORY.$this->sFullPath)) {
      
      $this->aRights = $aRights;
      $this->sName = $sName;
      $this->sPath = $sPath;
      $this->oParent = $oParent;
      
      $this->doExist(true);
      $this->loadRights();
      
    } else Action_Controler::addMessage(xt('Fichier ou répertoire "%s" introuvable dans "%s"!', new HTML_Strong($sActualPath), new HTML_Strong($sPath)), 'error');
  }
  
  public function getFile($sPath, $bDebug = true) {
    
    return $this->getDistantFile(array($sPath), $bDebug);
  }
  
  public function getDistantFile($aPath, $bDebug = true) {
    
    if ($aPath) {
      
      if (count($aPath) == 1) {
        
        if ($this->checkRights(4)) {
          
          $sName = $aPath[0];
          
          if (!array_key_exists($sName, $this->aFiles)) {
            
            $oFile = new XML_File($this->sFullPath, $sName, $this->getRights(), $this, $bDebug);
            
            if ($oFile->doExist()) $this->aFiles[$sName] = $oFile;
            else $this->aFiles[$sName] = null;
          }
          
          return $this->aFiles[$sName];
        }
        
      } else {
        
        $sName = array_shift($aPath);
        $oSubDirectory = $this->getDirectory($sName, 4);
        
        if ($oSubDirectory) return $oSubDirectory->getDistantFile($aPath);
      }
    }
    
    return null;
  }
  
  public function getDirectory($sName, $iMode = 7) {
    
    if ($this->checkRights($iMode)) {
      
      if ($sName == '.') return $this;
      else if ($sName == '..') return $this->getParent();
      else {
        
        if (!array_key_exists($sName, $this->aDirectories)) {
          
          $oDirectory = new XML_Directory($this->sFullPath, $sName, $this->getRights(), $this);
          
          if ($oDirectory->doExist()) $this->aDirectories[$sName] = $oDirectory;
          else $this->aDirectories[$sName] = null;
        }
        
        return $this->aDirectories[$sName];
      }
    }
    
    return false;
  }
  
  private function loadRights() {
    
    $iMode = $this->getUserMode();
    
    if ($iMode === null) {
      
      if (Controler::getUser()) {
        
        if (file_exists(MAIN_DIRECTORY.$this->sFullPath.'/directory.sml')) {
          
          $oDirectory = new XML_Document();
          $oDirectory->loadFreeFile($this->sFullPath.'/directory.sml');
          
          if ($oSecurity = $oDirectory->get('/*/security')) {
            
            if (Action_Controler::useStatut('report')) Action_Controler::addMessage(xt('Répertoire "%s" sécurisé ', new HTML_Strong($this->sFullPath)), 'report');
            
            $sOwner = $oSecurity->read('ls:owner', 'ls', NS_SECURITY);
            $sGroup = $oSecurity->read('ls:group', 'ls', NS_SECURITY);
            $sMode = $oSecurity->read('ls:mode', 'ls', NS_SECURITY);
            
            $iMode = Controler::getUser()->getMode($sOwner, $sGroup, $sMode, $oSecurity);
            if ($iMode !== null) $this->aRights = array('owner' => $sOwner, 'group' => $sGroup, 'mode' => $sMode);
          }
        }
        
        if ($iMode === null) $iMode = Controler::getUser()->getMode($this->getOwner(), $this->getGroup(), $this->getMode());
        
      } else if (Action_Controler::useStatut('report')) Action_Controler::addMessage(xt('Sécurisation suspendue dans "%s"', new HTML_Strong($this->sFullPath)), 'report');
    }
    
    $this->setUserMode($iMode);
  }
  
  public function checkRights($iMode) {
    
    $this->loadRights();
    
    if ($this->getUserMode() === null || ($iMode & $this->getUserMode())) return true;
    else Action_Controler::addMessage(xt('Répertoire "%s" non authorisé !', new HTML_Strong()), 'warning');
    
    return false;
  }
}

class XML_File extends XML_Resource {
  
  private $oDocument = null;
  
  public function __construct($sPath, $sName, $aRights = array(), $oParent = null, $bDebug = true) {
    
    $this->sFullPath = $sName ? $sPath.'/'.$sName : $sPath;
    
    if (is_file(MAIN_DIRECTORY.$this->sFullPath)) {
      
      $this->aRights = $aRights;
      $this->sName = $sName;
      $this->sPath = $sPath;
      $this->oParent = $oParent;
      
      $this->doExist(true);
      
      if (Controler::getUser()) {
        
        $this->setUserMode(
          Controler::getUser()->getMode(
            $this->getOwner(),
            $this->getGroup(),
            $this->getMode()));
      }
      
    } else if ($bDebug) Action_Controler::addMessage(xt('Fichier "%s" introuvable dans "%s"!', new HTML_Strong($sName), new HTML_Strong($sPath)), 'notice');
  }
  
  public function getFullPath() {
    
    return $this->sFullPath;
  }
  
  public function getDocument() {
    
    return $this->oDocument;
  }
  
  public function setDocument($oDocument) {
    
    $this->oDocument = $oDocument;
  }
  
  public function checkRights($iMode) {
    
    if ($this->getUserMode() === null || ($iMode & $this->getUserMode())) return true;
    else Action_Controler::addMessage(xt('Répertoire "%s" non authorisé !', new HTML_Strong()), 'warning');
    
    return false;
  }
}

