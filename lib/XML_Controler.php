<?php
  
  XML_Controler::init();
  
  class XML_Controler {
    
    private static $oMessages = null;
    
    public static function init() {
      
      self::$oMessages = new Messages(array('error', 'warning'));
    }
    
    public static function addMessage($mValue, $sStatut = 'notice', $aArguments = array()) {
      
      if (Controler::isAdmin()) {
        
        $aMessage = array(
          new HTML_Strong('XML', array('style' => 'text-decoration: underline;')),
          ' : ',
          $mValue);
        
        if ($sStatut == 'error')  $aMessage = array_merge($aMessage, array(new HTML_Br, Controler::getBacktrace()));
        
        if (Controler::isReady()) Controler::addMessage($aMessage, $sStatut, $aArguments);
        else echo new HTML_Tag('pre', $aMessage, array('class' => 'message-'.$sStatut));
      }
    }
  }
  