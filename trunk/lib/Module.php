<?php

class Module {
  
  private $oDirectory = null;
  
  private $oSchema = null;  
  private $sSchema = '';
  
  private $aNamespaces = array();
  private $sNamespace = SYLMA_NS_XHTML;
  private $sPrefix = '';
  
  public function setDirectory($sPath) {
    
    $this->oDirectory = extractDirectory($sPath, true);
  }
  
  public function getSchema() {
    
    if ($this->sSchema) {
      
      $this->oSchema = $this->getDocument($sPath);
      $this->sSchema = '';
    }
    
    return $this->oSchema;
  }
  
  public function setSchema($sPath) {
    
    $this->sSchema = $sPath;
  }
  
  public function getDirectory() {
    
    return $this->oDirectory;
  }
  
  public function setNamespace($sUri, $sPrefix, $bDefault = true) {
    
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
  
  public function getPrefix($sName = null) {
    
    return $this->sPrefix;
  }
  
  public function getDocument($sPath) {
    
    if ($oFile = Controler::getFile(Controler::getAbsolutePath($sPath, $this->getDirectory()))) return $oFile->getDocument();
    else return null;
  }
}

