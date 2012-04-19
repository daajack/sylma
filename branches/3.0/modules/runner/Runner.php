<?php
  
class Runner extends Module {
  
  public function test() {
    
    $this->setDirectory(__FILE__);
    $oResult = null;
    
    if ($oDocument = $this->getDocument('index.eml')) {
      
      $oResult = $oDocument->parseXSL($this->getDocument('default.xsl'));
      $oResult->dspm();
    }
    
    return $oResult;
  }
}
