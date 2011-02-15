<?php

class Module {
  
  protected $oDirectory = null;
  
  private $oSettings = null;  // global module settings
  private $oOptions = null;  // contextual settings
  
  protected $oSchema = null;  
  private $sName = '';
  
  private $aNamespaces = array();
  private $sNamespace = '';
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
  /*
  protected function getOptions() {
    
    return $this->oOptions;
  }
  
  protected function getOption($sName, $bDebug = true) {
    
    $oResult = null;
    
    if (!$this->getOptions()) $this->dspm(xt('Aucune option disponible pour le module'), 'action/warning');
    else {
      
      $oResult = $this->getOptions()->getByName($sName);
      
      if ($bDebug && !$oResult)
        $this->dspm(xt('Option %s introuvable dans %s', new HTML_Strong($sPath), view($this->getOptions())), 'action/warning');
    }
    
    return $oResult;
  }
  
  protected function readOption($sPath, $bDebug = true) {
    
    if ($oOption = $this->getOption($sPath, $bDebug)) return $oOption->read();
    else return '';
  }
  */
  protected function setName($sName) {
    
    $this->sName = $sName;
  }
  
  public function getName() {
    
    return $this->sName;
  }
  
  protected function setSchema($oSchema, $bNamespace = false, $sPrefix = '') {
    
    if ($bNamespace && !$this->getNamespace() && $oSchema && !$oSchema->isEmpty()) {
      
      if ($sNamespace = $oSchema->getAttribute('targetNamespace')) {
        
        if (!$sPrefix) $sPrefix = $this->getPrefix();
        $this->setNamespace($sNamespace, $sPrefix, true);
      }
    }
    
    $this->oSchema = $oSchema;
  }
  
  public function getSchema() {
    
    return $this->oSchema;
  }
  
  public function getDirectory() {
    
    return $this->oDirectory;
  }
  
  protected function setNamespace($sUri, $sPrefix = '', $bDefault = true) {
    
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
  
  public function runAction($sPath, $aArguments = array()) {
    
    $sPath = Controler::getAbsolutePath($sPath, $this->getDirectory());
    $oPath = new XML_Path($sPath, $aArguments, true, false);
    
    return new XML_Action($oPath);
  }
  
  public function getDocument($sPath, $bXSL = false) {
    
    if ($oFile = Controler::getFile(Controler::getAbsolutePath($sPath, $this->getDirectory()))) {
      
      if ($bXSL) return new XSL_Document((string) $oFile, MODE_EXECUTION);
      else return $oFile->getDocument();
      
    } else return null;
  }
  
  public function dspm($mMessage, $sStatut = SYLMA_MESSAGES_DEFAULT_STAT) {
    
    $oPath = new HTML_Div(xt('Module %s -&gt; %s', view($this->getName()), new HTML_Strong($this->getDirectory())),
      array('style' => 'font-weight: bold; padding: 5px 0 5px;'));
    return dspm(array($oPath, $mMessage, new HTML_Tag('hr')), $sStatut);
  }
}
