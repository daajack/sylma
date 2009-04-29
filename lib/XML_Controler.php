<?php
  
  XML_Controler::init();
  
  class XML_Controler {
    
    private static $oMessages = null;
    private static $aStats = array();
    
    public static function init() {
      
      self::$oMessages = new Messages(array('error', 'warning'));
    }
    
    public static function viewStats() {
      
      // self::addStat('load', 1); // precog ;)
      
      $oResult = new XML_Document('statistics');
      
      $oResult->addArray(self::$aStats, 'category');
      return $oResult->parseXSL(new XML_Document('/users/controler/stats.xsl'));
    }
    
    public static function addStat($sKey, $iWeight = 1) {
      
      if (!array_key_exists($sKey, self::$aStats)) self::$aStats[$sKey] = $iWeight;
      else self::$aStats[$sKey] += $iWeight;
    }
    
    public static function addMessage($mValue, $sStatut = 'notice', $aArguments = array()) {
      
      if (Controler::isAdmin()) {
        
        $aMessage = array(
          new HTML_Strong('XML', array('style' => 'text-decoration: underline;')),
          ' : ',
          $mValue);
        
        if ($sStatut == 'error') $aMessage = array_merge($aMessage, array(new HTML_Br, Controler::getBacktrace()));
        
        if (Controler::isReady()) Controler::addMessage($aMessage, $sStatut, $aArguments);
        else echo new HTML_Tag('pre', $aMessage, array('class' => 'message-'.$sStatut));
      }
    }
  }
  