<?php

class XML_Resource {
  
  protected $aRights = array();
  
  protected $sPath = '';
  protected $sName = '';
  protected $sFullPath = '';
  protected $oParent = null;
  private $oSettingsElement = null;
  
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
  
  public function isOwner() {
    
    return Controler::getUser()->isName($this->getOwner());
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
  
  /**
   * Get the rights element from the parent's directory security file
   * @return XML_Element|null
   */
  protected function getSettingsElement() {
    
    return $this->oSettingsElement;
  }
  
  /**
   * Set the rights element of this file in the parent's directory security file
   * @param XML_Element Element containing rights
   */
  protected function setSettingsElement(XML_Element $oElement) {
    
    $this->oSettingsElement = $oElement;
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
  
  /**
   * Put all rights into object
   * @param array|XML_Element|null $mRights Rights to use
   * @return array Rights used
   */
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
  
  /**
   * Check rights arguments for update in updateRights
   */
  protected function checkRightsArguments($sOwner, $sGroup, $sMode) {
    
    if ($this->isOwner()) {
      
      $bOwner = $sOwner !== $this->getOwner();
      $bGroup = $sGroup !== $this->getGroup();
      $bMode  = $sMode !== $this->getMode();
      
      if ($bOwner || $bGroup || $bMode) {
        
        $bResult = true;
        
        // Check validity
        
        if ($bOwner) {
          
          $bOwner = false;
          dspm(t('Changement d\'utilisateur impossible pour le moment'), 'file/warning');
        }
        
        if ($bGroup && !Controler::getUser()->isMember($sGroup)) {
          
          $bResult = false;
          dspm(t('Vous n\'avez pas les droits sur ce groupe ou il n\'existe pas !'), 'file/warning');
        }
        
        $iMode = Controler::getUser()->getMode($sOwner, $sGroup, $sMode);
        
        if ($bMode && $iMode === null) {
          
          $bResult = false;
          dspm(t('Les arguments pour la mise-à-jour ne sont pas valides'), 'file/warning');
        }
        
        if ($bMode && !($iMode & MODE_READ)) {
          
          $bResult = false;
          dspm(t('Vous ne pouvez pas retirer tous les droits de lecture'), 'file/warning');
        }
        
        // all datas are ok, or not modified
        
        if ($bResult && ($bOwner || $bGroup || $bMode)) return true;
      }
      
    } else dspm('Vous n\'avez pas les droits pour faire des modifications !', 'file/warning');
    
    return false;
  }
  
  public function __toString() {
    
    return $this->getFullPath();
  }
}

class XML_Directory extends XML_Resource {
  
  private $aDirectories = array();
  private $aFiles = array();
  private $aFreeFiles = array();
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
  
  /**
   * Get security XML_Document (eg: directory.sml)
   * @param boolean $bRecursive Get last setting file from parents
   * @return XML_Document|null
   */
  public function getSettings($bRecursive = false) {
    
    if ($bRecursive && !$this->oSettings) {
      
      if ($this->getParent()) return $this->getParent()->getSettings(true);
      else dspm(t('Aucun fichier de sécurité dans le répertoire parent'), 'file/error');
    }
    
    return $this->oSettings;
  }
  
  private function loadRights() {
    
    if (!$this->isSecured()) {
      
      if (Controler::getUser()) {
        
        $sSettings = $this->getFullPath().'/'.SYLMA_SECURITY_FILE;
        
        if (file_exists(MAIN_DIRECTORY.$sSettings)) {
          
          $oSettings = new XML_Document();
          $oSettings->loadFreeFile($sSettings);
          
          $this->oSettings = $oSettings;
          
          // self mode
          
          if ($oSettingsElement = $oSettings->get('ld:self', 'ld', NS_DIRECTORY)) $this->setSettingsElement($oSettingsElement);
          if ($aRights = $this->setRights($oSettingsElement)) $this->aChildrenRights = $aRights;
          
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
    
    //if ($oElement->isEmpty() && $this->getUserMode() != 0) return null;
    //else 
    return $oElement;
  }
  
  /**
   * Add a file into this directory via XML_Document->freeSave()
   */
  
  public function addFreeDocument($sName, XML_Document $oDocument) {
    
    $oDocument->saveFree($this, $sName);
  }
  
  public function getFiles(array $aExtensions = array(), $sPreg = null, $iDepth = 0) {
    
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
  
  /**
   * Unload then reload Document, maybe TODO to optimize by keeping the doc
   */
  public function updateFile($sName) {
    
    if (array_key_exists($sName, $this->aFiles)) unset($this->aFiles[$sName]);
    return $this->getFile($sName);
  }
  
  /**
   * Build an XML_File, check existenz and right access
   * @param $sName The name + extension of the file
   * @param $bDebug If true, send an error message if no access is found
   * @return XML_File the file requested
   */
  public function getFile($sName, $bDebug = false) {
    
    $this->loadRights();
    
    if ($sName && is_string($sName)) {
      
      if (!array_key_exists($sName, $this->aFiles)) {
        
        $oFile = new XML_File($this->getFullPath(), $sName, $this->getRights(), $this, $bDebug);
        
        if ($oFile->doExist()) {
          
          if ($oSettings = $this->getSettings()) { // named rights element
            
            $mRights = $oSettings->get("ld:file[@name=\"".xmlize($sName)."\"]", 'ld', NS_DIRECTORY);
            
            if (!$mRights) $mRights = $this->getChildrenRights();
            else $oFile->setSettingsElement($mRights);
            
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
        
        $sPath = MAIN_DIRECTORY.$this.'/'.$sName;
        
        mkdir($sPath, SYLMA_DEFAULT_MODE);
        
        unset($this->aDirectories[$sName]);
        $oDirectory = $this->getDirectory($sName);
        
        dspm(xt('Création du répertoire %s', new HTML_Strong($oDirectory)), 'file/notice');
        
        //} else dspm(xt('Création du répertoire %s impossible', new HTML_Stong($this.$sName)), 'file/error');
      }
    }
    
    return $oDirectory;
  }
  
  public function checkRights($iMode) {
    
    $this->loadRights();
    
    if (!$this->isSecured() || ($iMode & $this->getUserMode())) return true;
    
    return false;
  }
  
  /**
   * Change rights in corresponding SECURITY_FILE
   */
  public function updateRights($sOwner, $sGroup, $sMode) {
    
    if ($this->checkRightsArguments($sOwner, $sGroup, $sMode)) {
      
      $eDirectory = new XML_Element('self', 
        new XML_Element('ls:security', array(
            new XML_Element('ls:owner', $sOwner, null, NS_SECURITY),
            new XML_Element('ls:group', $sGroup, null, NS_SECURITY),
            new XML_Element('ls:mode', $sMode, null, NS_SECURITY)),
          null, NS_SECURITY), NS_DIRECTORY);
      
      if ($oSettingsElement = $this->getSettingsElement()) $oSettingsElement->remove();
      
      if (!$oSecurityDocument = $this->getSettings()) {
        
        // Creation of a security file
        
        $oSecurityDocument = new XML_Document;
        $oSecurityDocument->addNode('directory', null, null, NS_DIRECTORY);
        
        $this->addFreeDocument(SYLMA_SECURITY_FILE, $oSecurityDocument);
      }
      
      $oSecurityDocument->add($eDirectory);
      
      return $oSecurityDocument->saveFree($this, SYLMA_SECURITY_FILE);
    }
    
    return false;
  }
  
  public function getRealPath() {
    
    return $this->getParent() ? MAIN_DIRECTORY.$this : MAIN_DIRECTORY;
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
  private $oSettings = null;
  
  private $bFileSecured = false;
  
  public function __construct($sPath, $sName, array $aRights, XML_Directory $oParent, $bDebug) {
    
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
    
    return substr($this->getFullPath(), 0, strlen($this->getFullPath()) - strlen($this->getExtension()) - 1);
  }
  
  public function getExtension() {
    
    return $this->sExtension;
  }
  
  public function getSize() {
    
    return $this->iSize;
  }
  
  public function getRealPath() {
    
    return $this->getParent()->getRealPath().'/'.$this->getName();
  }
  
  public function isLoaded() {
    
    return (bool) $this->oDocument;
  }
  
  /**
   * Get the real corresponding XML_Document without appending rights control
   */
  public function getFreeDocument() {
    
    if (!$this->oDocument) $this->getDocument(); // will load XML_Document in the XML_Document->loadFile() with setDocument(), maybe TODO
    
    return $this->oDocument;
  }
  
  /**
   * Get the copy of the corresponding document
   * Call XML_Document->loadFile() via new instance, that will put a copy here of the document with setDocument()
   */
  public function getDocument() {
    
    return new XML_Document((string) $this);
  }
  
  /**
   * Each XML_Document loads will register in the corresponding XML_File
   * @param XML_Document $oDocument The XML_Document caller
   */
  public function setDocument(XML_Document $oDocument) {
    
    if ($oDocument->isEmpty()) $oDocument = new XML_Document;
    else $oDocument = new XML_Document($oDocument->getRoot()); // getRoot avoid parsing of specials classes like actions
    
    $oDocument->setFile($this);
    
    $this->oDocument = $oDocument;
  }
  
  public function checkRights($iMode) {
    
    if (!$this->isSecured() || ($iMode & $this->getUserMode())) return true;
    
    return false;
  }
  
  public function isFileSecured($mSecured = null) {
    
    if ($mSecured === null) return $this->bFileSecured;
    else $this->bFileSecured = $mSecured;
  }
  
  /**
   * Change rights in corresponding SECURITY_FILE
   */
  public function updateRights($sOwner, $sGroup, $sMode) {
    
    if ($this->checkRightsArguments($sOwner, $sGroup, $sMode)) {
      
      $eFile = new XML_Element('file', 
        new XML_Element('ls:security', array(
            new XML_Element('ls:owner', $sOwner, null, NS_SECURITY),
            new XML_Element('ls:group', $sGroup, null, NS_SECURITY),
            new XML_Element('ls:mode', $sMode, null, NS_SECURITY)),
          null, NS_SECURITY),
        array('name' => $this->getName()), NS_DIRECTORY);
      
      if ($oSettingsElement = $this->getSettingsElement()) $oSettingsElement->remove();
      
      if (!$oSecurityDocument = $this->getParent()->getSettings()) {
        
        // Creation of a security file
        
        $oSecurityDocument = new XML_Document;
        $oSecurityDocument->addNode('directory', null, null, NS_DIRECTORY);
        
        $this->getParent()->addFreeDocument(SYLMA_SECURITY_FILE, $oSecurityDocument);
      }
      
      $oSecurityDocument->add($eFile);
      
      return $oSecurityDocument->saveFree($this->getParent(), SYLMA_SECURITY_FILE);
    }
    
    return false;
  }
  
  public function updateName($sNewName) {
    
    if ($this->checkRights(MODE_WRITE)) {
      
      $bResult = rename($this->getRealPath(), $this->getParent()->getRealPath().'/'.$sNewName);
      
      if ($bResult) Controler::addMessage(t('Fichier renommé !'), 'success');
      else Controler::addMessage(t('Impossible de renommer le fichier !'), 'warning');
    }
  }
  
  public function delete($bMessage = true, $bUpdateDirectory = true) {
    
    if ($this->checkRights(MODE_WRITE)) $this->deleteFree($bMessage, $bUpdateDirectory);
  }
  
  public function deleteFree($bMessage = false, $bUpdateDirectory = false) {
    
    unlink(MAIN_DIRECTORY.$this);
    if ($bUpdateDirectory) $this->update();
    
    Controler::addMessage(xt('Suppression du fichier %s', $this->parse()), 'file/notice');
  }
  
  public function saveText($sContent) {
    
    return file_put_contents($this->getRealPath(), $sContent);
  }
  /*
   * Call parent directory to reload (re-create) an XML_File reference, this one will be destroy
   */
  
  public function update() {
    
    $this->getParent()->updateFile($this->getName());
  }
  
  public function parse() {
    
    return new HTML_A(SYLMA_PATH_EDITOR.'?path='.$this->getFullPath(), $this->getFullPath());
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

