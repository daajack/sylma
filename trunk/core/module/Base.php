<?php

class ModuleBase extends Namespaced {
  
  protected $oSchema = null;  
  
  // array of classe's object to use within this class with $this->create() loaded in [settings]/classes
  protected $aClasses = array();
  
  private $sName = '';
  
  private $oDirectory = null;
  private $arguments = null;
  
  protected function setName($sName) {
    
    return $this->sName = $sName;
  }
  
  public function getName() {
    
    return $this->sName;
  }
  
  protected function setDirectory($mPath) {
    
    if (is_string($mPath)) $this->oDirectory = extractDirectory($mPath);
    else $this->oDirectory = $mPath;
  }
  
  public function getDirectory() {
    
    return $this->oDirectory;
  }
  
  protected function getFile($sPath) {
    
    return Controler::getFile(Controler::getAbsolutePath($sPath, $this->getDirectory()));
  }
  
  public function create($sName, $aArguments = array()) {
    
    $result = null;
    
    if (!$this->getArguments()) {
      
      $this->throwException(txt('Cannot build object @class %s. No settings defined', $sName));
    }
    
    $aPath = explode('/', $sName);
    array_unshift($aPath, null);
    
    $sPath = implode('/classes/', $aPath);
    
    if (!$class = $this->getArgument($sPath)) {
      
      $this->throwException(txt('Cannot build object @class %s. No settings defined for these class', $sName));
    }
    
    // set absolute path for relative classe file's path
    
    if (!$sFile = $class->get('file', false)) {
      
      $sFile = $class->get('name') . '.php';
    }
    //dspf($sFile);
    //dspf(path_absolute($sFile, $this->getDirectory()));
    $class->set('file', path_absolute($sFile, $this->getDirectory()));
    //dspf($class->query());
    return Controler::createObject($class, $aArguments);
  }
  
  protected function setArguments($mArguments = null, $bMerge = true) {
    
    if ($mArguments) {
      
      if (is_array($mArguments)) {
        
        if ($this->getArguments() && $bMerge) $this->getArguments()->mergeArray($mArguments);
        else $this->arguments = new XArguments($mArguments, $this->getName());
      }
      else {
        
        if ($this->getArguments() && $bMerge) $this->getArguments()->merge($mArguments);
        else $this->arguments = $mArguments;
      }
    }
    else {
      
      $this->arguments = null;
    }
    
    return $this->getArguments();
  }
  
  protected function getArguments() {
    
    return $this->arguments;
  }
  
  protected function getArgument($sPath, $mDefault = null, $bDebug = true) {
    
    $mResult = $mDefault;
    
    if ($this->getArguments()) {
      
      $mResult = $this->getArguments()->get($sPath, $bDebug);
      if (!$mResult && $mDefault !== 'null') $mResult = $mDefault;
    }
    
    return $mResult;
  }
  
  protected function setSchema($mSchema, $bNamespace = true, $sPrefix = '') {
    
    if (is_string($mSchema)) $mSchema = $this->getDocument($mSchema, Sylma::MODE_EXECUTE);
    
    if ($mSchema && !$mSchema->isEmpty()) { // !$this->getNamespace() && TODO REM
      
      if ($sNamespace = $mSchema->getAttribute('targetNamespace')) {
        
        if (!$sPrefix) $sPrefix = $this->getPrefix();
        $this->setNamespace($sNamespace, $sPrefix, $bNamespace);
      }
      
      $this->oSchema = $mSchema;
    }
  }
  
  protected function getSchema() {
    
    return $this->oSchema;
  }
  
  protected function throwException($sMessage, $mSender = array(), $iOffset = 2) {
    
    $mSender = (array) $mSender;
    $mSender[] = '@namespace ' . $this->getNamespace();
    
    Sylma::throwException($sMessage, $mSender, $iOffset);
  }
  
  /*
   * Add a log message with the @class Logger
   * @param mixed|DOMNode|string|array $mMessage The message to send, will be parsed or stringed
   * @param $sStatut The statut of message : see @file /system/allowed-messages.xml for more infos
   **/
  protected function log($mMessage, $sStatut = Sylma::LOG_STATUT_DEFAULT) {
    
    return Sylma::log($this->getNamespace(), $mMessage, $sStatut);
  }
  /**
   * Alias of log for ascendent compatibility
   */
  protected function dspm($mMessage, $sStatut = Sylma::LOG_STATUT_DEFAULT) {
    
    $oPath = new HTML_Div(xt('Module %s -&gt; %s', view($this->getName()), new HTML_Strong($this->getDirectory())),
      array('style' => 'font-weight: bold; padding: 5px 0 5px;'));
    return dspm(array($oPath, $mMessage, new HTML_Tag('hr')), $sStatut);
  }
}

