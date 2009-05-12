<?php

class User extends Temp_Action {
  
  private $sUser = '';
  private $aGroups = array();
  private $bIsReal = false;
  private $aArguments = array();
  
  public function __construct($sUser = null, $aGroups = array(), $aArguments = array()) {
    
    if ($sUser) $this->setReal();
    else $this->setReal(false);
    
    $this->setBloc('user', $sUser);
    
    if (isset($aArguments['full_name']))
      $this->setBloc('full_name', $aArguments['full_name']);
    
    $this->setRoles($aGroups);
    $this->setArguments($aArguments);
  }
  
  public function login() {
    
    $_SESSION['user'] = serialize($this);
  }
  
  public function logout() {
    
    unset($_SESSION['user']);
    $this->setReal(false);
    
    Controler::addMessage(t('Cookie détruit !'), 'report');
  }
  
  public function isReal() {
    
    return $this->bIsReal;
  }
  
  public function setReal($bValue = true) {
    
    $this->bIsReal = $bValue;
  }
  
  public function getRoles() {
    
    return $this->aGroups;
  }
  
  public function setRoles($aGroups) {
    
    $this->aGroups = $aGroups;
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
  
  function __toString() {
    
    $this->addBloc('user');
    
    parent::__toString();
  }
}
