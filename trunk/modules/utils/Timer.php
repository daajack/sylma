<?php

class Timer extends Module {
  
  const NS = 'http://www.sylma.org/modules/utils/timer';
  
  protected $aStack = array();
  protected $aClasses = array();
  
  public function open($sMethod) {
    
    list($sClass, $sMethod) = explode('::', $sMethod);
    
    if (!array_key_exists($sClass, $this->aClasses)) $this->aClasses[$sClass] = array();
    if (!array_key_exists($sMethod, $this->aClasses[$sClass])) $this->aClasses[$sClass][$sMethod] = array('calls' => 0, 'time' => 0);
    
    $aMethod =& $this->aClasses[$sClass][$sMethod];
    $aMethod['current'] = microtime(true);
    $aMethod['calls']++;
    
    $this->aStack[] = array($sClass, $sMethod);
  }
  
  public function close() {
    
    if (!count($this->aStack)) return;
    list($sClass, $sMethod) = array_pop($this->aStack);
    
    $aMethod =& $this->aClasses[$sClass][$sMethod];
    
    $aMethod['time'] = $aMethod['time'] + (microtime(true) - $aMethod['current']);
    unset($aMethod['current']);
  }
  
  public function parse() {
    
    $aResult = array();
    
    foreach ($this->aClasses as $sClass => $aClass) {
      
      $aResult['#class'][] = array('@name' => $sClass, '#method' => $aClass);
    }
    
    $result = Arguments::buildDocument($aResult, self::NS);
    
    return $result;
  }
}

class TimerArgs extends Timer {
  
  protected $args;
  
  public function __construct() {
    
    $this->arguments = new Arguments;
  }
  
  public function open($sMethod) {
    
    list($sClass, $sMethod) = explode('::', $sMethod);
    $sPath = $sClass . '/' . $sMethod;
    
    if (!$method = $this->getArgument($sPath, null, false)) {
      
      $method = $this->getArguments()->set($sPath, array(
        '@name' => $sMethod,
        'calls' => 0,
        'time' => 0));
    }
    
    $method->set('current', microtime(true));
    $method->set('calls', $method->get('calls') + 1);
    
    $this->aStack[] = $method;
  }
  
  public function close() {
    
    if (!count($this->aStack)) return;
    $method = array_pop($this->aStack);
    
    $method->set('time', $method->get('time') + (microtime(true) - $method->get('current')));
    $method->set('current');
  }
  
  public function parse() {
    
    $aResult = array();
    
    foreach ($this->getArguments() as $sClass => $class) {
      
      $aResult['#class'][] = array('@name' => $sClass, '#method' => array_values($class->query()));
    }
    
    $result = Arguments::buildDocument(array('classes' => $aResult), self::NS);
    
    return $result;
  }
}

