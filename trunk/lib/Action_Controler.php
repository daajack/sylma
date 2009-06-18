<?php

class Action_Controler {
  
  private static $aInterfaces = array();
  private static $oMessages = null;
  private static $aStats = array();
  
  public static function init($aStatuts) {
    
    self::$oMessages = new Messages($aStatuts);
  }
  
  public static function loadInterfaces() {
    
    if (!$oDirectory = Controler::getDirectory(PATH_INTERFACES)) {
      
      self::addMessage(xt('Le répértoire des interfaces "%s" n\'existe pas !', new HTML_Strong(PATH_INTERFACES)), 'warning');
      
    } else {
      
      $oInterfaces = $oDirectory->browse(array('iml'));
      
      if (!$aInterfaces = $oInterfaces->query('//file')) {
        
        self::addMessage(xt('Aucun fichier d\'interface à l\'emplacement "%s" indiqué !', new HTML_Strong(PATH_INTERFACES)), 'warning');
        
      } else {
        
        foreach ($aInterfaces as $oFile) {
          
          $sPath = $oFile->getAttribute('full-path');
          $oInterface = new XML_Document($sPath, MODE_EXECUTION);
          
          if ($oInterface->isEmpty()) {
            
            self::addMessage(xt('Fichier d\'interface "%s" vide', new HTML_Strong($sPath)), 'warning');
            
          } else {
            
            if (!$sName = $oInterface->read('ns:name')) {
              
              self::addMessage(xt('Fichier d\'interface "%s" invalide, aucune classe n\'est indiquée !', new HTML_Strong($sPath)), 'warning');
              
            } else self::$aInterfaces[$sName] = $oInterface;
          }
        }
      }
    }
  }
  
  public static function getInterface($oObject) {
    
    if (is_object($oObject)) $sClass = get_class($oObject);
    else $sClass = $oObject;
    
    if (array_key_exists($sClass, self::$aInterfaces)) return self::$aInterfaces[$sClass];
    else {
      
      $sPrevClass = $sClass;
      
      do {
        
        $sTempClass = $sPrevClass;
        $sPrevClass = get_parent_class($sPrevClass);
        
      } while ($sPrevClass && !array_key_exists($sPrevClass, self::$aInterfaces));
      
      if ($sPrevClass && array_key_exists($sPrevClass, self::$aInterfaces)) return self::$aInterfaces[$sPrevClass];
      else self::addMessage(xt('Interface de classe "%s" introuvable !', new HTML_Strong($sClass)), 'warning');
    }
    
    return false;
  }
  
  public static function getSpecial($sName, $oAction, $oRedirect) {
    
    $oSpecials = new XML_Document(PATH_SPECIALS, MODE_EXECUTION);
    
    if ($oSpecial = $oSpecials->get("object[@name='$sName']")) {
      if ($sCall = $oSpecial->getAttribute('call')) {
        
        if ($oSpecial->getAttribute('static') == 'true') return $sCall;
        else {
          
          eval('$oObject = '.$sCall.';');
          
          if (isset($oObject)) return $oObject;
          else self::addMessage(xt('L\'objet "%s" est nul !', new HTML_Strong($sCall)), 'warning');
        }
        
      } else self::addMessage(xt('Pas de référence dans le fichier "%s",  !', new HTML_Strong(PATH_SPECIALS)), 'warning');
      
    } else self::addMessage(xt('La variable spéciale "%s" n\'existe pas !', new HTML_Strong($sName)), 'warning');
    
    return null;
  }
  
  public static function getMessages() {
    
    return self::$oMessages;
  }
  
  public static function useStatut($sStatut) {
    
    return self::getMessages()->useStatut('action-'.$sStatut);
  }
  
  public static function addMessage($mValue, $sStatut = 'notice', $aArguments = array()) {
    
    $sStatut = 'action-'.$sStatut;
    
    if (FORMAT_MESSAGES) {
      
      $mMessage = array(
        new HTML_Strong('Action', array('style' => 'text-decoration: underline;')),
        ' : ',
        $mValue);
      
      if ($sStatut == 'action-error') $mMessage = array_merge($mMessage, array(new HTML_Br, Controler::getBacktrace()));
      
    } else $mMessage = $mValue;
    
    if (Controler::isReady()) Controler::addMessage($mMessage, $sStatut, $aArguments);
    else self::getMessages()->addMessage(new Message($mMessage, $sStatut, $aArguments));
    // echo new HTML_Tag('pre', $mMessage, array('class' => 'message-'.$sStatut));
  }
}

