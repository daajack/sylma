<?php

class XML_Resource {
  
  protected $aRights = array();
  
  protected $sPath = '';
  protected $sName = '';
  protected $sFullPath = '';
  protected $oParent = null;
  
  private $bExist = false;
  private $bSecured = false;
  
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
  
  public function getParents($oTarget = null) {
    
    $oParent = $this;
    $aResult = array();
    
    while (($oParent = $oParent->getParent()) && (!$oTarget || ($oParent != $oTarget))) {
      
      array_unshift($aResult, $oParent);
    }
    
    if ($oTarget && !$oParent) return null;
    else return $aResult;
  }
  
  public function getParent() {
    
    return $this->oParent;
  }
  
  protected function getUserMode() {
    
    // if (!array_key_exists('user-mode', $this->aRights)) Controler::addMessage($this, 'success');
    return $this->aRights['user-mode'];
  }
  
  protected function isSecured($bSecured = null) {
    
    if ($bSecured === null) return $this->bSecured;
    else $this->bSecured = $bSecured;
  }
  
  protected function getRights() {
    
    return $this->aRights;
  }
  
  /*
   * Extract and check validity of parameter from an XML_Element
   * @return an array of validated security parameters, with the user-mode for the result of
   * rights of the user on rights on the file
   **/
  
  protected function extractRights($oElement = null) {
    
    if ($oElement && ($oSecurity = $oElement->get('ls:security', 'ls', NS_SECURITY))) {
      
      if (Controler::useStatut('file/report')) Controler::addMessage(xt('Ressource "%s" sécurisée ', new HTML_Strong($this->getFullPath())), 'file/report');
      
      $sOwner = $oSecurity->read('ls:owner', 'ls', NS_SECURITY);
      $sGroup = $oSecurity->read('ls:group', 'ls', NS_SECURITY);
      $sMode = $oSecurity->read('ls:mode', 'ls', NS_SECURITY);
      
      $iMode = Controler::getUser()->getMode($sOwner, $sGroup, $sMode, $oSecurity);
      
      if ($iMode !== null) return array('owner' => $sOwner, 'group' => $sGroup, 'mode' => $sMode, 'user-mode' => $iMode);
    }
    
    return array();
  }
  
  protected function setRights($mRights = null) {
    
    if (is_array($mRights)) $aRights = $mRights;
    else {
      
      $aDefaultRights = array(
        'owner' => $this->getOwner(),
        'group' => $this->getGroup(),
        'mode' => $this->getMode(),
        'user-mode' => $this->getUserMode());
      
      if (Controler::getUser())
        $aDefaultRights['user-mode'] = Controler::getUser()->getMode(
          $aDefaultRights['owner'], $aDefaultRights['group'], $aDefaultRights['mode']);
      
      if (is_object($mRights)) {
        
        if (!$aRights = $this->extractRights($mRights)) $aRights = $aDefaultRights;
        
      } else $aRights = $aDefaultRights;
    }
    
    $this->aRights = $aRights;
    $this->isSecured(true);
    
    return $aRights;
  }
  
  public function __toString() {
    
    return $this->getFullPath();
  }
}

class XML_Directory extends XML_Resource {
  
  private $aDirectories = array();
  private $aFiles = array();
  private $oSettings = null;
  
  private $aChildrenRights = null;
  
  public function __construct($sPath, $sName, $aRights = array(), $oParent = null) {
    
    $this->sFullPath = $sName ? $sPath.'/'.$sName : $sPath;
    
    if (is_dir(MAIN_DIRECTORY.$this->getFullPath())) {
      
      $this->aRights = $this->aChildrenRights = $aRights;
      $this->sName = $sName;
      $this->sPath = $sPath;
      $this->oParent = $oParent;
      
      $this->doExist(true);
      $this->loadRights();
    }
  }
  
  private function getChildrenRights() {
    
    return $this->aChildrenRights;
  }
  
  private function getSettings() {
    
    return $this->oSettings;
  }
  
  private function loadRights() {
    
    if (!$this->isSecured()) {
      
      if (Controler::getUser()) {
        
        $sSettings = $this->getFullPath().'/'.SECURITY_FILE;
        
        if (file_exists(MAIN_DIRECTORY.$sSettings)) {
          
          $oSettings = new XML_Document();
          $oSettings->loadFreeFile($sSettings);
          
          $this->oSettings = $oSettings;
          
          // self mode
          
          if ($aRights = $this->setRights($oSettings->get('ld:self', 'ld', NS_DIRECTORY))) $this->aChildrenRights = $aRights;
          
          // children mode
          
          if (($oChildrenRights = $oSettings->get('ld:propagate', 'ld', NS_DIRECTORY)) &&
            ($aChildrenRights = $this->extractRights($oChildrenRights))) $this->aChildrenRights = $aChildrenRights;
          
        } else $this->setRights();
        
      } else if (Controler::useStatut('file/report')) Controler::addMessage(xt('Sécurisation suspendue dans "%s"', new HTML_Strong($this->getFullPath())), 'file/report');
    }
  }
  
  public function unbrowse() {
    
    $oResult = $this->parse();
    $oParent = $this;
    
    while ($oParent = $oParent->getParent()) {
      
      $oResult->shift($oParent);
    }
    
    return $oResult;
  }
  
  public function browse($aExtensions, $aPaths = array(), $iDepth = null) {
    
    $aFiles = scandir(MAIN_DIRECTORY.$this->getFullPath(), 0);
    $oElement = $this->parse();
    
    if ($iDepth === null || $iDepth > 0) {
      
      if ($iDepth) $iDepth--;
      
      foreach ($aFiles as $sFile) {
        
        if ($sFile != '.' && $sFile != '..') {
          
          if (($oFile = $this->getFile($sFile)) && $oFile->getUserMode() != 0) {
            
            if ($aExtensions && !in_array(strtolower($oFile->getExtension()), $aExtensions)) $oFile = null;
            else $oElement->add($oFile->parseXML());
            
          } else if ($oDirectory = $this->getDirectory($sFile)) {
            
            $bValid = true;
            
            foreach ($aPaths as $sPath) {
              
              switch ($sPath{0}) {
                
                case '/' : if ($sPath == $oDirectory->getFullPath()) $bValid = false; break;
                default : if ($sPath == $oDirectory->getName()) $bValid = false; break;
              }
            }
            
            if ($bValid) {
              
              if ($iDepth === null || $iDepth) $oElement->add($oDirectory->browse($aExtensions, $aPaths, $iDepth));
              else $oElement->add($oDirectory);
            }
          }
        }
      }
    }
    
    if ($oElement->isEmpty() && $this->getUserMode() != 0) return null;
    else return $oElement;
  }
  
  public function getFiles($aExtensions = array(), $sPreg = null, $iDepth = 0) {
    
    $this->browse(array(), array(), 1);
    $aResult = array();
    
    // Files of current directory
    
    if ($aExtensions) {
      
      foreach ($this->aFiles as $sFile => $oFile) {
        
        if ($oFile &&
          (!$aExtensions || in_array(strtolower($oFile->getExtension()), $aExtensions)) &&
          (!$sPreg || preg_match($sPreg, $sFile))) $aResult[] = $oFile;
      }
      
    } else $aResult = array_values($this->aFiles);
    
    // Recursion in sub-directory
    
    if ($iDepth === null || $iDepth > 0) {
      
      if ($iDepth) $iDepth--;
      
      foreach ($this->aDirectories as $oDirectory) {
        
        if ($oDirectory) $aResult = array_merge($aResult, $oDirectory->getFiles($aExtensions, $sPreg, $iDepth));
      }
    }
    
    return $aResult;
  }
  
  public function updateFile($sName) {
    
    if (array_key_exists($sName, $this->aFiles)) unset($this->aFiles[$sName]);
    return $this->getFile($sName);
  }
  
  public function getFile($sName, $bDebug = false) {
    
    $this->loadRights();
    
    if ($sName && is_string($sName)) {
      
      if (!array_key_exists($sName, $this->aFiles)) {
        
        $oFile = new XML_File($this->getFullPath(), $sName, $this->getRights(), $this, $bDebug);
        
        if ($oFile->doExist()) {
          
          if ($oSettings = $this->getSettings()) { // named rights element
            
            if (!$mRights = $oSettings->get("ld:file[@name=\"".xmlize($sName)."\"]", 'ld', NS_DIRECTORY))
              $mRights = $this->getChildrenRights();
            
          } else $mRights = $this->getChildrenRights(); // propagated rights array
          
          $oFile->setRights($mRights);
          
          if (Controler::getUser()) $this->aFiles[$sName] = $oFile;
          else return $oFile;
          
        } else $this->aFiles[$sName] = null;
      }
      
      return $this->aFiles[$sName];
    }
    
    return null;
  }
  
  public function getDirectory($sName) {
    
    $this->loadRights();
    
    if ($sName == '.') return $this;
    else if ($sName == '..') return $this->getParent();
    else if ($sName) {
      
      if (!array_key_exists($sName, $this->aDirectories)) {
        
        $oDirectory = new XML_Directory($this->getFullPath(), $sName, $this->getChildrenRights(), $this);
        
        if ($oDirectory->doExist()) $this->aDirectories[$sName] = $oDirectory;
        else $this->aDirectories[$sName] = null;
      }
      
      return $this->aDirectories[$sName];
    }
    
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
  
  public function getDistantDirectory($aPath) {
    
    if ($aPath) {
      
      $sName = array_shift($aPath);
      
      $oSubDirectory = $this->getDirectory($sName);
      
      if ($oSubDirectory) return $oSubDirectory->getDistantDirectory($aPath);
      
    } else return $this;
    
    return null;
  }
  
  public function addDirectory($sName) {
    
    $oDirectory = null;
    
    if (!$oDirectory = $this->getDirectory($sName)) {
      
      if ($sName && $this->checkRights(MODE_WRITE)) {
        
        mkdir(MAIN_DIRECTORY.$this.'/'.$sName, 0700);
        
        unset($this->aDirectories[$sName]);
        $oDirectory = $this->getDirectory($sName);
        
        Controler::addMessage(xt('Création du répertoire %s', new HTML_Strong($oDirectory)), 'file/notice');
      }
    }
    
    return $oDirectory;
  }
  
  public function checkRights($iMode) {
    
    $this->loadRights();
    
    if (!$this->isSecured() || ($iMode & $this->getUserMode())) return true;
    
    return false;
  }
  
  public function delete() {
    
    $bResult = false;
    
    if ($this->checkRights(MODE_WRITE)) {
      
      if ($bResult = rmdir(MAIN_DIRECTORY.$this)) Controler::addMessage(xt('Suppression du répertoire %s', $this), 'file/notice');
    }
    
    return $bResult;
  }
  
  public function parse() {
    
    if (!$sName = xmlize($this->getName())) {
      
      $sName = t('<racine>');
      $sPath = '/';
      
    } else $sPath = $this->getFullPath();
    
    return new XML_Element('directory', null, array(
      'full-path' => xmlize($sPath),
      'owner' => $this->getOwner(),
      'group' => $this->getGroup(),
      'mode' => $this->getMode(),
      'read' => booltostr($this->checkRights(MODE_READ)),
      'write' => booltostr($this->checkRights(MODE_WRITE)),
      'execution' => booltostr($this->checkRights(MODE_EXECUTION)),
      'name' => $sName));
  }
  
  public function __toString() {
    
    if ($this->getFullPath()) return $this->getFullPath();
    else return '/';
  }
}

class XML_File extends XML_Resource {
  
  private $oDocument = null;
  private $sExtension = '';
  private $iSize = 0;
  private $iChanged = 0;
  
  private $bFileSecured = false;
  
  public function __construct($sPath, $sName, $aRights = array(), $oParent = null, $bDebug = true) {
    
    $this->sFullPath = $sName ? $sPath.'/'.$sName : $sPath;
    $sPath = MAIN_DIRECTORY.$this->getFullPath();
    
    if (is_file($sPath)) {
      
      $this->aRights = $aRights;
      $this->sName = $sName;
      $this->sPath = $sPath;
      $this->oParent = $oParent;
      
      $this->iSize = filesize($sPath);
      $this->iChanged = filemtime($sPath);
      
      if ($iExtension = strrpos($sName, '.')) $this->sExtension = substr($sName, $iExtension + 1);
      else $this->sExtension = '';
      
      $this->doExist(true);
      
    } else if ($bDebug) Controler::addMessage(xt('Fichier "%s" introuvable dans "%s" !', new HTML_Strong($sName), new HTML_Strong((string) $oParent)), 'file/notice');
  }
  
  public function getLastChange() {
    
    return $this->iChanged;
  }
  
  public function getActionPath() {
    
    return substr($this->getFullPath(), 0, strlen($this->getExtension()) + 1);
  }
  
  public function getExtension() {
    
    return $this->sExtension;
  }
  
  public function getSize() {
    
    return $this->iSize;
  }
  
  public function isLoaded() {
    
    return (bool) $this->oDocument;
  }
  
  public function getFreeDocument() {
    
    if (!$this->oDocument) $this->getDocument();
    
    return $this->oDocument;
  }
  
  public function getDocument() {
    
    return new XML_Document((string) $this); // load himself via XML_Document->loadFile()
  }
  
  public function setDocument($oDocument) {
    
    $this->oDocument = $oDocument;
  }
  
  public function checkRights($iMode) {
    
    if (!$this->isSecured() || ($iMode & $this->getUserMode())) return true;
    
    return false;
  }
  
  public function isFileSecured($bSecured = null) {
    
    if ($bSecured === null) return $this->bFileSecured;
    else $this->bFileSecured = $bSecured;
  }
  
  public function delete() {
    
    if ($this->checkRights(MODE_WRITE)) {
      
      unlink(MAIN_DIRECTORY.$this);
      $this->getParent()->updateFile($this->getName());
      Controler::addMessage(xt('Suppression du fichier %s', $this->parse()), 'file/notice');
    }
  }
  
  public function save($sContent) {
    
    if ($this->checkRights(MODE_WRITE)) {
      
      $sPath = MAIN_DIRECTORY.$this;
      unlink($sPath);
      file_put_contents($sPath, $sContent);
    }
  }
  
  public function parse() {
    
    return new HTML_A(PATH_EDITOR.'?path='.$this->getFullPath(), $this->getFullPath());
    //$oLink->add($this->getParent().'/', new HTML_Span($this->getName(), array('class' => 'file-name')));
    
    //return $oLink;
  }
  
  public function parseXML() {
    
    $iSize = ($this->getSize() / 1000);
    
    if ($iSize < 1) $iSize = 1;
    
    return new XML_Element('file', null, array(
      'full-path' => $this->getFullPath(),
      'name' => $this->getName(),
      'owner' => $this->getOwner(),
      'group' => $this->getGroup(),
      'mode' => $this->getMode(),
      'read' => booltostr($this->checkRights(MODE_READ)),
      'write' => booltostr($this->checkRights(MODE_WRITE)),
      'execution' => booltostr($this->checkRights(MODE_EXECUTION)),
      'size' => $iSize,
      'extension' => $this->getExtension()));
  }
}

