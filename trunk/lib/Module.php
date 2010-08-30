<?php

class Module {
  
  private $oDirectory = null;
  
  public function setDirectory($sPath) {
    
    $this->oDirectory = extractDirectory($sPath, true);
  }
  
  public function getDirectory() {
    
    return $this->oDirectory;
  }
  
  public function getDocument($sPath) {
    
    if ($oFile = Controler::getFile(Controler::getAbsolutePath($sPath, $this->getDirectory()))) return $oFile->getDocument();
    else return null;
  }
}