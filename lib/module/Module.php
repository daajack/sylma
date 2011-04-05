<?php

require_once('Base.php');

class Module extends ModuleBase {
  
  private $oSettings = null;  // global module settings
  private $oOptions = null;  // contextual settings
  
  protected function readSettings($sPath = '', $mDefault = '', $bDebug = true) {
    
    $sResult = $mDefault;
    
    if ($oSetting = $this->getSettings($sPath, $bDebug)) $sResult = $oSetting->read();
    if (!$sResult) $sResult = $mDefault;
    
    return $sResult;
  }
  
  protected function getSettings($sPath = '', $mDefault = null, $bDebug = true) {
    
    if (!$this->oSettings && $this->getName()) {
      
      $oSettings = Controler::getSettings()->get("module[@name='{$this->getName()}']");
      if (!$oSettings) {
        
        $this->dspm(t('Aucune configuration définie pour ce module'), 'action/warning');
      }
      else {
        
        $this->oSettings = new Options(new XML_Document($oSettings), null); // TODO, schemas and namespaces
      }
    }
    
    if ($sPath && $this->oSettings) return $this->oSettings->get($sPath, $bDebug);
    else if ($mDefault) return $mDefault;
    else return $this->oSettings;
  }
  
  protected function runAction($sPath, $aArguments = array()) {
    
    $sPath = Controler::getAbsolutePath($sPath, $this->getDirectory());
    $oPath = new XML_Path($sPath, $aArguments, true, false);
    
    return new XML_Action($oPath);
  }
  
  protected function getTemplate($sPath) {
    
    if ($oFile = $this->getFile($sPath)) return new XSL_Document((string) $oFile, MODE_EXECUTION);
    else return null;
  }
  
  protected function getFile($sPath) {
    
    return Controler::getFile(Controler::getAbsolutePath($sPath, $this->getDirectory()));
  }
  
  protected function getDocument($sPath, $iMode = MODE_READ) {
    
    if ($oFile = $this->getFile($sPath)) return new XML_Document((string) $oFile, $iMode);
    else return null;
  }
  
  /*** Options ***/
  
  protected function setOptions(XML_Document $oOptions, XML_Document $oSchema = null, $bPrefix = false) {
    
    $this->oOptions = new Options($oOptions, $oSchema, $this->getNS());
    
    return $this->getOptions();
  }
  
  protected function getOptions() {
    
    return $this->oOptions;
  }
  
  protected function getOption($sPath, $mDefault = null, $bDebug = false) {
    
    if ($this->getOptions() !== null) $eResult = $this->getOptions()->get($sPath, $bDebug);
    return $eResult ? $eResult : $mDefault;
  }
  
  protected function readOption($sPath, $mDefault = null, $bDebug = false) {
    
    if ($this->getOptions() !== null) $sResult = $this->getOptions()->read($sPath, $bDebug);
    return $sResult ? $sResult : $mDefault;
  }
  
  public function getFullPrefix() {
    
    return $this->getPrefix() ? $this->getPrefix().':' : '';
  }
}

