<?php
  
  XML_Controler::init();
  
  class XML_Controler {
    
    private static $oMessages = null;
    
    public static function init() {
      
      self::$oMessages = new Messages(array('error', 'warning'));
    }
    
    public static function addMessage($mValue, $sStatut = 'notice', $aArguments = array()) {
      
      if (Controler::isReady() && Controler::isAdmin()) Controler::addMessage(array('XML : ', $mValue), $sStatut, $aArguments);
      else if (DEBUG) print_r($mValue);
    }
  }
  