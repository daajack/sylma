<?php

class Action_Controler {
  
  private static $aInterfaces = array();
  private static $oMessages = null;
  private static $aStats = array();
  
  public static function init($aStatuts) {
    
    self::$oMessages = new Messages($aStatuts);
  }
  
  public static function loadInterfaces() {
    
    if ($oInterfaces = new XML_Document(PATH_INTERFACES)) {
      
      if ($oClasses = $oInterfaces->query('class')) {
        
        foreach ($oInterfaces->query('class') as $oClass) {
          
          $sName = $oClass->getAttribute('name');
          $sPath = $oClass->getAttribute('path');
          $oInterface = new XML_Document($sPath);
          
          if (!$oInterface->isEmpty()) self::$aInterfaces[$sName] = $oInterface;
          else self::addMessage(xt('Fichier d\'interface "%s" vide ou introuvable', new HTML_Strong($sPath)), 'error');
        }
        
      } else self::addMessage(xt('Fichier des interfaces "%s" invalide, aucune classe trouvée !', new HTML_Strong(PATH_INTERFACES)), 'error');
    } else self::addMessage(xt('Impossible de charger le fichier des interfaces à l\'adresse "%s"', new HTML_Strong(PATH_INTERFACES)), 'error');
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
      else self::addMessage(xt('Interface de classe "%s" introuvable !', new HTML_Strong($sClass)), 'error');
    }
    
    return false;
  }
  
  public static function getSpecial($sName, $oRedirect) {
    
    $oSpecials = new XML_Document(PATH_SPECIALS);
    
    if ($oSpecial = $oSpecials->get("object[@name='$sName']")) {
      if ($sCall = $oSpecial->getAttribute('call')) {
        
        if ($oSpecial->getAttribute('static') == 'true') return $sCall;
        else {
          
          eval('$oObject = '.$sCall.';');
          
          if (isset($oObject)) return $oObject;
          else self::addMessage(xt('L\'objet "%s" est nul !', new HTML_Strong($sCall)), 'error');
        }
        
      } else self::addMessage(xt('Pas de référence dans le fichier "%s",  !', new HTML_Strong(PATH_SPECIALS)), 'error');
      
    } else self::addMessage(xt('La variable spéciale "%s" n\'existe pas !', new HTML_Strong($sName)), 'error');
    
    return false;
  }
  
  public static function getMessages() {
    
    return self::$oMessages;
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

