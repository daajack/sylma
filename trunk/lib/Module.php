<?php

class Module {
  
  private $oDirectory = null;
  private $sNamespace = SYLMA_NS_XHTML;
  private $sPrefix = '';
  
  public function setDirectory($sPath) {
    
    $this->oDirectory = extractDirectory($sPath, true);
  }
  
  public function getDirectory() {
    
    return $this->oDirectory;
  }
  
  public function setNamespace($sNamespace) {
    
    $this->sNamespace = $sNamespace;
  }
  
  public function getNamespace() {
    
    return $this->sNamespace;
  }
  
  public function setPrefix($sPrefix) {
    
    $this->sPrefix = $sPrefix;
  }
  
  public function getPrefix() {
    
    return $this->sPrefix;
  }
  
  public function getDocument($sPath) {
    
    if ($oFile = Controler::getFile(Controler::getAbsolutePath($sPath, $this->getDirectory()))) return $oFile->getDocument();
    else return null;
  }
}