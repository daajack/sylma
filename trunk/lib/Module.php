<?php

class Module {
  
  protected $oDirectory = null;
  
  private $oSettings = null;  
  protected $oSchema = null;  
  private $sSchema = '';
  private $sName = '';
  
  private $aNamespaces = array();
  private $sNamespace = SYLMA_NS_XHTML;
  private $sPrefix = '';
  
  public function setDirectory($sPath) {
    
    $this->oDirectory = extractDirectory($sPath, true);
  }
  
  public function getSettings($sPath = '') {
    
    if (!$this->oSettings && $this->getName()) {
      
      $this->oSettings = Controler::getSettings()->get("module[@name='{$this->getName()}']");
    }
    
    if ($sPath && $this->oSettings) return $this->oSettings->read($sPath);
    else return $this->oSettings;
  }
  
  public function getSchema() {
    
    if ($this->sSchema) {
      
      $this->oSchema = $this->getDocument($sPath);
      $this->sSchema = '';
    }
    
    return $this->oSchema;
  }
  
  public function getName() {
    
    return $this->sName;
  }
  
  public function setName($sName) {
    
    $this->sName = $sName;
  }
  
  public function setSchema($sPath) {
    
    $this->sSchema = $sPath;
  }
  
  public function getDirectory() {
    
    return $this->oDirectory;
  }
  
  public function setNamespace($sUri, $sPrefix = '', $bDefault = true) {
    
    $this->aNamespaces[$sPrefix] = $sUri;
    
    if ($bDefault) {
      
      $this->sNamespace = $sUri;
      $this->sPrefix = $sPrefix;
    }
  }
  
  public function getNS($sPrefix = null) {
    
    if ($sPrefix) return array($sPrefix => array_val($sPrefix, $this->aNamespaces));
    else return $this->aNamespaces;
  }
  
  public function getNamespace($sPrefix = null) {
    
    if ($sPrefix) return array_val($sPrefix, $this->aNamespaces);
    else return $this->sNamespace;
  }
  
  public function getFullPrefix() {
    
    return $this->getPrefix() ? $this->getPrefix().':' : '';
  }
  public function getPrefix() {
    
    return $this->sPrefix;
  }
  
  public function getDocument($sPath, $bXSL = true) {
    
    if ($oFile = Controler::getFile(Controler::getAbsolutePath($sPath, $this->getDirectory()))) {
      
      if ($bXSL) return new XSL_Document((string) $oFile);
      else return $oFile->getDocument();
      
    } else return null;
  }
}

