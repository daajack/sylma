<?php

namespace sylma\storage\sql\locale;
use sylma\core, sylma\parser\languages\common, sylma\template;

class Tree extends core\module\Domed implements template\parser\tree
{
  public function init(template\parser\Pather $pather, common\_window $window) {
    
    $this->pather = $pather;
    $this->window = $window;
  }
  
  public function reflectRead() {
    
    $this->launchException('Not implemented');
  }
  
  public function reflectApply($sMode) {
    $this->launchException('Not implemented');
  }
  
  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {
    
    $window = $this->window;
    $locale = $window->addManager('locale');
    $pather = $this->pather;
    $arguments = $pather->parseArguments($sArguments);
    
    switch ($sName) {
      
      case 'page' :

        $result = $locale->call('getPage', $arguments);
        break;
      
      
      case 'translate' :
        
        $arguments[] = (string) $pather->getSourceFile();
        $result = $locale->call('getTranslation', $arguments);
        break;
      
      default :
        
        $this->launchException('Unknown locale function : ' . $sName);
    }
    
    return $result;
  }
  
  public function reflectApplyDefault($sPath, array $aPath, $sMode, $bRead = false, array $aArguments = array()) {
    
    $window = $this->window;
    $locale = $window->addManager('locale');

    switch ($sPath) {
      
      case 'language' : 
        
        $result = $locale->call('getLanguage');
        break;
      
      case 'current' :
        
        $result = $locale->call('getCurrentPage', $aPath);
        break;
      
      default :
        
        $this->launchException('Unknown locale path : ' . $sPath);
    }
    
    return $result;
  }
  
  public function asToken() {
    
    return 'Locale tree';
  }
}
