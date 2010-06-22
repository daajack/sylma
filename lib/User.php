<?php

class User {
  
  private $sName = '';
  private $bIsReal = false;
  private $aGroups = array();
  private $aArguments = array();
  
  public function __construct($sName = null, $aGroups = array(), $aArguments = array()) {
    
    if ($sName) $this->setReal();
    else $this->setReal(false);
    
    $this->setName($sName);
    $this->setGroups($aGroups);
    $this->setArguments($aArguments);
  }
  
  public function login() {
    
    $_SESSION['user'] = serialize($this);
  }
  
  public function logout() {
    
    unset($_SESSION['user']);
    $this->setReal(false);
    
    Controler::addMessage(t('Session détruite !'), 'report');
  }
  
  public function isReal() {
    
    return $this->bIsReal;
  }
  
  public function setReal($bValue = true) {
    
    $this->bIsReal = $bValue;
  }
  
  public function getGroups() {
    
    return $this->aGroups;
  }
  
  public function setGroups($aGroups) {
    
    $this->aGroups = $aGroups;
  }
  
  public function getDirectory() {
    
    return Controler::getDirectory('/users/'.$this->getName());
  }
  
  public function getName() {
    
    return $this->sName;
  }
  
  private function setName($sName) {
    
    $this->sName = $sName;
  }
  
  public function isName($sName) {
    
    return ($this->getName() == $sName);
  }
  
  public function isMember($sGroup) {
    
    return in_array($sGroup, $this->aGroups);
  }
  
  public function setArgument($sKey, $sValue) {
    
    $this->aArgument[$sKey] = $sValue;
  }
  
  public function setArguments($aArguments = array()) {
    
    if (is_array($aArguments)) $this->aArguments = $aArguments;
  }
  
  public function getArgument($sKey) {
    
    return isset($this->aArguments[$sKey]) ? $this->aArguments[$sKey] : null;
  }
  
  public function getArguments() {
    
    return $this->aArguments;
  }
  
  public function getMode($sOwner, $sGroup, $sMode, $oOrigin = null) {
    
    $sMode = (string) $sMode;
    if ($oOrigin === null) $oOrigin = new XML_Element('null');
    
    // Validity control of the arguments
    
    if (!$sOwner) {
      
      Controler::addMessage(xt('Sécurité : "owner" inexistant ! %s', $oOrigin), 'xml/warning');
      
    } else if (strlen($sMode) < 3 || !is_numeric($sMode)) {
      
      Controler::addMessage(xt('Sécurité : "mode" invalide ! - %s', $oOrigin), 'xml/warning');
      
    } else if (!strlen($sGroup)) {
      
      Controler::addMessage(xt('Sécurité : "group" inexistant ! %s', $oOrigin), 'xml/warning');
      
    } else {
      
      // everything is ok
      
      $iOwner = intval($sMode{0});
      $iGroup = intval($sMode{1});
      $iPublic = intval($sMode{2});
      
      if ($iOwner > 7 || $iGroup > 7 || $iPublic > 7) {
        
        // check validity of mode
        Controler::addMessage(xt('Sécurité : Attribut "mode" invalide !', $oOrigin), 'xml/warning');
        
      } else {
        
        // now everything is ok
        $iMode = $iPublic;
        
        if ($sOwner == $this->isName($sOwner)) $iMode |= $iOwner;
        if ($this->isMember($sGroup)) $iMode |= $iGroup;
        return $iMode;
      }
    }
    
    return null;
  }
  
  public function parse() {
    
    $oNode = new HTML_Div(array(
      new HTML_A(SYLMA_PATH_USER_EDIT.$this->getName(), $this->getArgument('full-name')),
      ' ('.implode(', ', $this->getGroups()).')'), array('id' => 'user-info'));
    
    return $oNode;
  }
}
