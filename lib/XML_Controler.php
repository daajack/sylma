<?php

class XML_Controler {
  
  private static $aStats = array();
  
  public static function viewStats() {
    
    // self::addStat('load', 1); // precog ;)
    
    $oResult = new XML_Document('statistics');
    
    $oResult->addArray(self::$aStats, 'category');
    return $oResult->parseXSL(new XML_Document(Controler::getSettings('messages/statistic-template/@path')));
  }
  
  public static function getStats() {
    
    return self::$aStats;
  }
  
  public static function addStat($sKey, $iWeight = 1) {
    
    if (!array_key_exists($sKey, self::$aStats)) self::$aStats[$sKey] = $iWeight;
    else self::$aStats[$sKey] += $iWeight;
  }
}
