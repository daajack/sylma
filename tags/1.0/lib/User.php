<?php

class User extends HTML_Tag {
  
  private $sUser = '';
  private $aRoles = array();
  private $bIsReal = false;
  private $aArguments = array();
  
  public function __construct($sUser = null, $aRoles = array(), $aArguments = array()) {
    
    if ($sUser) $this->setReal();
    else $this->setReal(false);
    
    $this->setBloc('user', $sUser);
    
    if (isset($aArguments['prenom']) && isset($aArguments['nom']))
      $this->setBloc('full_name', $aArguments['prenom'].' '.$aArguments['nom']);
    
    $this->setRoles($aRoles);
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
    
    return $this->aRoles;
  }
  
  public function setRoles($aRoles) {
    
    $this->aRoles = $aRoles;
  }
  
  public function isRole($sRole) {
    
    return in_array($sRole, $this->aRoles);
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
