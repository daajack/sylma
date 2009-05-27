<?php


class File_Controler {
}

class XML_Directory {
  
  private $aDirectories = array();
  private $aFiles = array();
  private $sPath = '';
  private $sName = '';
  private $sFullPath = '';
  
  private $sOwner = '';
  private $sGroup = '';
  private $sMode = '';
  
  private $bExist = false;
  private $iMode = null;
  
  public function __construct($sPath, $sName) {
    
    // if ($sPath{0} == '/') $this->sPath = .'/'.
    // else $this->sPath = $sPath;
    //echo $sPath.' :: '.$sName.'<br/>';
    $this->sName = $sName;
    $this->sPath = $sPath;
    $this->sFullPath = $sName ? $sPath.'/'.$sName : $sPath;
    
    if (is_dir(MAIN_DIRECTORY.$this->sFullPath)) {
      
      $this->bExist = true;
      $this->loadRights();
      
    } else Action_Controler::addMessage(xt('Fichier ou répertoire "%s" introuvable dans "%s"!', new HTML_Strong($sActualPath), new HTML_Strong($sPath)), 'error');
  }
  
  public function doExist() {
    
    return $this->bExist;
  }
  
  public function getDirectory($sName, $iMode = 7) {
    
    if ($this->checkRights($iMode)) {
      
      if (array_key_exists($sName, $this->aDirectories)) return $this->aDirectories[$sName];
      else {
        
        $oDirectory = new XML_Directory($this->sFullPath, $sName);
        
        if ($oDirectory->doExist()) $this->aDirectories[$sName] = $oDirectory;
        else $this->aDirectories[$sName] = null;
        
        return $this->aDirectories[$sName];
      }
    }
    
    return false;
  }
  
  private function loadRights() {
    
    $iMode = null;
    
    if ($this->getMode() === null) {
      
      if (Controler::getUser()) {
        
        if (file_exists(MAIN_DIRECTORY.$this->sFullPath.'/directory.sml')) {
          
          $oDirectory = new XML_Document($this->sFullPath.'/directory.sml');
          
          if ($oSecurity = $oDirectory->get('/*/security')) {
            
            if (Action_Controler::useStatut('report')) Action_Controler::addMessage(xt('Répertoire "%s" sécurisé', new HTML_Strong($this->sFullPath)), 'report');
            
            $iMode = Controler::getUser()->getMode(
              $oSecurity->read('ls:owner', 'ls', NS_SECURITY),
              $oSecurity->read('ls:group', 'ls', NS_SECURITY),
              $oSecurity->read('ls:mode', 'ls', NS_SECURITY),
              $oSecurity);
          }
          
        } else $iMode = 7;
        
      } else if (Action_Controler::useStatut('report')) Action_Controler::addMessage(xt('Sécurisation suspendue dans "%s"', new HTML_Strong($this->sFullPath)), 'report');
    }
    
    $this->iMode = $iMode;
  }
  
  public function getMode() {
    
    return $this->iMode;
  }
  
  public function checkRights($iMode) {
    
    $this->loadRights();
    
    if ($this->getMode() === null || ($iMode & $this->getMode())) return true;
    else Action_Controler::addMessage(xt('Répertoire "%s" non authorisé !', new HTML_Strong()), 'warning');
    
    return false;
  }
}