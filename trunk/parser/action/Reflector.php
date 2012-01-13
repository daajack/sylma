<?php

namespace sylma\parser\action;
use \sylma\core, \sylma\dom, \sylma\parser, \sylma\storage\fs;

require_once(dirname(__DIR__) . '/Reflector.php');

abstract class Reflector extends parser\Reflector {
  
  protected function reflectFile(dom\element $el) {
    
    $result = null;
    
    if (!$sPath = $el->getAttribute('path')) {
      
      $this->throwException(txt('No path defined for %s', $el->getPath()));
    }
    
    $sPath = core\functions\path\toAbsolute($sPath, $this->getDirectory());
    $window = $this->getWindow();
    
    $path = $this->parseString($sPath);
    
    if (!$sOutput = $el->getAttribute('output')) $sOutput = 'xml';
    if (!$iMode = (int) $el->getAttribute('mode')) $iMode = \Sylma::MODE_READ;
    
    require_once('core/functions/Text.php');
    
    $bParse = strtobool($el->getAttribute('parse-variables'));
    
    if ($bParse) {
      
      $result = $window->create('template', array($this->parseString($callContent, true)));
    }
    else {
      
      $result = $window->createCall($window->getSelf(), 'getFile', '\sylma\storage\fs\file', array($path, $iMode));
    }
    
    return $result;
  }
  
  protected function parseString($sValue) {
    
    $window = $this->getWindow();
    
    preg_match_all('/\[\$([\w-]+)\]/', $sValue, $aResults, PREG_OFFSET_CAPTURE);
    
    if ($aResults && $aResults[0]) {
      
      $iSeek = 0;
      
      foreach ($aResults[1] as $aResult) {
        
        $iVarLength = strlen($aResult[0]) + 3;
        $sVarValue = (string) $window->getVariable($aResult[0]);
        
        $sValue = substr($sValue, 0, $aResult[1] + $iSeek - 2) . $sVarValue . substr($sValue, $aResult[1] + $iSeek - 2 + $iVarLength);
        
        $iSeek += strlen($sVarValue) - $iVarLength;
      }
    }
    
    return $window->create('string', array($sValue));
  }
  
  protected function reflectDocument(dom\element $el) {
    
    
  }
  
  protected function reflectAction(dom\element $el) {
    
    $sPath = $el->getAttribute('path');
    
    $parser = $this->getControler();
    
    //arguments
    
    return $parser->createAction($sPath);
  }
  
  protected function reflectDirectory(dom\element $el) {
    
    $window = $this->getWindow();
    
    return $window->createCall($window->getSelf(), 'getDirectory', '\sylma\storage\fs\directory', array());
  }
  
  protected function reflectGetArgument(dom\element $el) {
    
    $window = $this->getWindow();
    $sName = $el->getAttribute('name');
    
    if (!$mVal = $this->getArgument($sName)) {
      
      $this->throwException(txt('Unknown argument : %s', $sName));
    }
    
    return $window->createCall($window->getSelf(), 'getArgument', $window->loadInstance($mVal), array($sName));
  }
  
  protected function reflectSettingsArgument(dom\element $el) {
    
    $aResult = array();
    $window = $this->getWindow();
    
    $sName = $el->getAttribute('name');
    $sFormat = $el->getAttribute('format');
    
    $val = $window->stringToInstance($sFormat);
    
    if ($val instanceof php\basic\StringInstance) {
      
      $call = $window->createCall($window->getSelf(), 'readArgument', $val, array($sName));
      $aResult[] = $window->createCall($window->getSelf(), 'validateString', 'boolean', array($call));
    }
    else if ($val instanceof php\basic\ArrayInstance) {
      
      $call = $window->createCall($window->getSelf(), 'getArgument', $val, array($sName));
      $aResult[] = $window->createCall($window->getSelf(), 'validateString', 'boolean', array($call));
    }
    else if ($val instanceof php\_object) {
      
      $call = $window->createCall($window->getSelf(), 'getArgument', $val, array($sName));
      
      $interface = $val->getInterface();
      $aResult[] = $window->createCall($window->getSelf(), 'validateObject', 'boolean', array($call, $interface->getName()));
    }
    
    if ($el->hasChildren()) {
      
      if ($validate = $el->get('self:validate')) {
        
        
      }
    }
    
    return $aResult;
  }
}