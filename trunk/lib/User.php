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
  
  public function getMode($sOwner, $sGroup, $sMode, $oNode = null) {
    
    if ($oNode === null) $oNode = new XML_Element('null');
    
    if (!$sOwner) XML_Controler::addMessage(xt('Sécurité : "user" invalide ! - %s', new HTML_Tag('em', $oNode->viewResume())), 'warning');
    else if (strlen($sMode) < 3 || !is_numeric($sMode)) { echo (!is_numeric($sMode)).' '.$sMode;XML_Controler::addMessage(xt('Sécurité : "mode" invalide ! - %s', new HTML_Tag('em', $oNode->viewResume())), 'warning');}
    else if (!strlen($sGroup)) XML_Controler::addMessage(xt('Sécurité : "group" invalide ! - %s', new HTML_Tag('em', $oNode->viewResume())), 'warning');
    else {
      
      $iOwner = intval($sMode{0});
      $iGroup = intval($sMode{1});
      $iPublic = intval($sMode{2});
      
      if ($iOwner > 7 || $iGroup > 7 || $iPublic > 7) XML_Controler::addMessage(xt('Sécurité : Attribut "mode" invalide !', new HTML_Tag('em', $oNode->viewResume())), 'warning');
      else {
        
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
      new HTML_A(PATH_USER_EDIT.$this->getArgument('id'), $this->getArgument('full_name')),
      ' ('.implode(', ', $this->getGroups()).')'), array('id' => 'user-info'));
    
    return $oNode;
  }
}
