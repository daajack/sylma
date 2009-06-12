<?php

class XML_Controler {
  
  private static $oMessages = null;
  private static $aStats = array();
  
  public static function init($aStatuts) {
    
    self::$oMessages = new Messages($aStatuts);
  }
  
  public static function viewStats() {
    
    // self::addStat('load', 1); // precog ;)
    
    $oResult = new XML_Document('statistics');
    
    $oResult->addArray(self::$aStats, 'category');
    return $oResult->parseXSL(new XML_Document(Controler::getSettings('messages/statistic-template')));
  }
  
  public static function addStat($sKey, $iWeight = 1) {
    
    if (!array_key_exists($sKey, self::$aStats)) self::$aStats[$sKey] = $iWeight;
    else self::$aStats[$sKey] += $iWeight;
  }
  
  public static function useStatut($sStatut) {
    
    return self::getMessages()->useStatut('xml-'.$sStatut);
  }
  
  public static function getMessages() {
    
    return self::$oMessages;
  }
  
  public static function addMessage($mValue, $sStatut = 'notice', $aArguments = array()) {
    
    $sStatut = 'xml-'.$sStatut;
    
    if (FORMAT_MESSAGES) {
      
      $mMessage = array(
        new HTML_Strong('XML', array('style' => 'text-decoration: underline;')),
        ' : ',
        $mValue);
      
      if ($sStatut == 'error') $mMessage = array_merge($mMessage, array(new HTML_Br, Controler::getBacktrace()));
      
    } else $mMessage = $mValue;
    
    if (Controler::isReady()) Controler::addMessage($mMessage, $sStatut, $aArguments);
    else self::getMessages()->addMessage(new Message($mMessage, $sStatut, $aArguments));
    // echo new HTML_Tag('pre', $aMessage, array('class' => 'message-'.$sStatut));
  }
}
