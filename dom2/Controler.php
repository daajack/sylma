<?php

namespace sylma\dom;
use \sylma\core;

require_once('core/module/Filed.php');

class Controler extends core\module\Filed {
  
  const NS = 'http://www.sylma.org/dom';
  const SETTINGS = 'settings.yml';
  
  protected $aDefaultClasses = array();
  
  protected $aClasses = array(
    'document' => 'DOMDocument',
    'element' => 'DOMElement',
    'fragment' => 'DOMDocumentFragment',
    'text' => 'DOMText',
  );
  
  protected $directory;
  
  protected $aStats = array();
  
  public function __construct() {
    
    $this->setDirectory(__file__);
    $this->setArguments(self::SETTINGS);
    $this->setNamespace(self::NS);
    
    foreach ($this->getArgument('namespaces')->query() as $sPrefix => $sNamespace) {
      
      $this->setNamespace($sNamespace, $sPrefix, false);
    }
  }
  
  public function getClasses(core\argument $settings = null) {
    
    $aClasses = array();
    
    if (!$this->aDefaultClasses || $settings) {
      
      require_once('core/factory.php');
      
      $this->getArguments()->registerToken(core\factory::CLASSBASE_TOKEN);
      $this->getArguments()->registerToken(core\factory::DIRECTORY_TOKEN);
      
      $classes = $this->getArguments()->get('classes');
      if ($settings) $classes->merge($settings);
      
      foreach ($this->aClasses as $sKey => $sClass) {
        
        if ($class = $classes->get($sKey)) {
          
          if ($sClassBase = $classes->getToken(core\factory::CLASSBASE_TOKEN)) {
            
            $class->set('name', path_absolute($class->read('name'), $sClassBase, '\\'));
          }
          
          if (!class_exists($class->read('name'))) {
            
            if ($sFile = $class->read('file', false)) $class->set('file', path_absolute($sFile, $class->getLastDirectory()));
            \Controler::loadClass($class->read('name'), $class->read('file', false));
          }
          
          $aClasses[$sClass] = $class->read('name');
        }
      }
      
      $this->getArguments()->unRegisterToken(core\factory::CLASSBASE_TOKEN);
      $this->getArguments()->unRegisterToken(core\factory::DIRECTORY_TOKEN);
      
      if (!$settings) $this->aDefaultClasses = $aClasses;
    }
    else {
      
      $aClasses = $this->aDefaultClasses;
    }
    
    return $aClasses;
  }
  
  public function readArgument($sPath, $mDefault = null, $bDebug = false) {
    
    return parent::readArgument($sPath, $mDefault, $bDebug);
  }
  
  public function addStat($sName, array $aArguments) {
    
    if ($this->readArgument('stats/enable')) $this->aStats[$sName][] = $aArguments;
  }
  
  public function stringToBool($sValue, $bDefaut = false) {
    
    $sValue = strtolower($sValue);
    
    if (strtolower($sValue) == 'true') return true;
    else if (strtolower($sValue) == 'false') return false;
    else return $bDefaut;
  }
}
