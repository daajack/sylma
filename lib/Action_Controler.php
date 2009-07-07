<?php

class Action_Controler {
  
  private static $aInterfaces = array();
  private static $oInterfaces = null;
  private static $aStats = array();
  
  public static function buildInterfacesIndex() {
    
    if (!$oDirectory = Controler::getDirectory(PATH_INTERFACES)) {
      
      Controler::addMessage(xt('Le répértoire des interfaces "%s" n\'existe pas !', new HTML_Strong(PATH_INTERFACES)), 'action/warning');
      
    } else {
      
      $oInterfaces = $oDirectory->browse(array('iml'));
      
      if (!$aInterfaces = $oInterfaces->query('//file')) {
        
        Controler::addMessage(xt('Aucun fichier d\'interface à l\'emplacement "%s" indiqué !', new HTML_Strong(PATH_INTERFACES)), 'action/warning');
        
      } else {
        
        $oIndex = new XML_Document('interfaces');
        
        foreach ($aInterfaces as $oFile) {
          
          $sPath = $oFile->getAttribute('full-path');
          $oInterface = new XML_Document($sPath, MODE_EXECUTION);
          
          if ($oInterface->isEmpty()) {
            
            Controler::addMessage(xt('Fichier d\'interface "%s" vide', new HTML_Strong($sPath)), 'action/warning');
            
          } else {
            
            if (!$sName = $oInterface->read('ns:name')) {
              
              Controler::addMessage(xt('Fichier d\'interface "%s" invalide, aucune classe n\'est indiquée !', new HTML_Strong($sPath)), 'action/warning');
              
            } else {
              
              $oIndex->addNode('interface', $sPath, array('class' => $sName));
            }
          }
        }
        
        $sPath = PATH_INTERFACES.'/../interfaces.cml';
        $oPath = new XML_Path($sPath, false);
        
        $oIndex->save($oPath);
        Controler::addMessage(xt('Interface d\'actions %s regénéré !', $oPath->parse()), 'success');
      }
    }
  }
  
  public static function loadInterfaces() {
    
    // self::buildInterfacesIndex();
    self::$oInterfaces = new XML_Document(PATH_INTERFACES.'/../interfaces.cml', MODE_EXECUTION);
  }
  
  public static function loadInterface($sClass) {
    
    $oInterface = null;
    
    if (!self::$oInterfaces) {
      
      Controler::addMessage('L\'interface n\'est pas prêt !', 'action/error');
      return null;
    }
    
    if (array_key_exists($sClass, self::$aInterfaces)) {
      
      $oInterface = self::$aInterfaces[$sClass];
      
    } else {
      
      if ($oElement = self::$oInterfaces->get("interface[@class='$sClass']")) {
        
        $sPath = $oElement->read();
        $oInterface = new XML_Document($sPath, MODE_EXECUTION);
        
        if ($oInterface->isEmpty()) {
          
          Controler::addMessage(xt('Fichier d\'interface "%s" vide', new HTML_Strong($sPath)), 'action/warning');
          
        } else {
          
          if ($sExtends = $oInterface->read('ns:extends')) {
            
            if ($oSubInterface = self::loadInterface($sExtends)) {
              
              $oInterface->add($oSubInterface->query('ns:method'));
              
            } else {
              
              Controler::addMessage(xt('Extension de la classe "%s" impossible, interface de classe "%s" introuvable !', new HTML_Strong($sClass), new HTML_Strong($sExtends)), 'action/warning');
            }
            
          }
          
          self::$aInterfaces[$sClass] = $oInterface;
          if (Controler::useStatut('report')) Controler::addMessage(xt('Chargement de l\'interface "%s"', new HTML_Strong($sPath)), 'action/report');
        }
      }
    }
    
    return $oInterface;
  }
  
  public static function getInterface($oObject) {
    
    if (is_object($oObject)) $sClass = get_class($oObject);
    else $sClass = $oObject;
    
    if (!$oInterface = self::loadInterface($sClass)) {
      
      $sPrevClass = $sClass;
      
      do {
        
        $sTempClass = $sPrevClass;
        $sPrevClass = get_parent_class($sPrevClass);
        
        if ($sPrevClass) $oInterface = self::loadInterface($sPrevClass);
        
      } while ($sPrevClass && !$oInterface);
      
      if (!$oInterface) Controler::addMessage(xt('Interface de classe "%s" introuvable !', new HTML_Strong($sClass)), 'action/warning');
    }
    
    return $oInterface;
  }
  
  public static function getSpecial($sName, $oAction, $oRedirect) {
    
    $oSpecials = new XML_Document(PATH_SPECIALS, MODE_EXECUTION);
    
    if ($oSpecial = $oSpecials->get("object[@name='$sName']")) {
      if ($sCall = $oSpecial->getAttribute('call')) {
        
        if ($oSpecial->getAttribute('static') == 'true') return array('variable' => $sCall, 'static' => true);
        else {
          
          eval('$oObject = '.$sCall.';');
          
          if (isset($oObject)) return array('variable' => $oObject, 'static' => false);
          else Controler::addMessage(xt('L\'objet "%s" est nul !', new HTML_Strong($sCall)), 'action/warning');
        }
        
      } else Controler::addMessage(xt('Pas de référence dans le fichier "%s",  !', new HTML_Strong(PATH_SPECIALS)), 'action/warning');
      
    } else Controler::addMessage(xt('La variable spéciale "%s" n\'existe pas !', new HTML_Strong($sName)), 'action/warning');
    
    return null;
  }
}

