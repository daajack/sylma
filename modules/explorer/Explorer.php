<?php
  
class Explorer {
  
  public function updateRights() {
    
    $bResult = false;
    
    $bDirectory = array_val('directory', $_POST) ? true : false;
    $sPath      = array_val('resource', $_POST);
    $sMode      = array_val('mode', $_POST);
    $sOwner     = array_val('owner', $_POST);
    $sGroup     = array_val('group', $_POST);
    
    if (!$sPath || !$sOwner || $sMode === '' || $sGroup === '') dspm(t('Erreur dans la requête !'), 'error');
    else {
      
      if ($bDirectory) { // directory
        
        if (!$oDirectory = Controler::getDirectory($sPath)) dspm(t('Répertoire introuvable'), 'error');
        else if ($bResult = $oDirectory->updateRights($sOwner, $sGroup, $sMode))
          dspm(t('Mise-à-jour des droits du répertoire effectuée'), 'success');
        
      } else { // file
        
        if (!$oFile = Controler::getFile($sPath)) dspm(t('Fichier introuvable'), 'error');
        else if ($bResult = $oFile->updateRights($sOwner, $sGroup, $sMode))
          dspm(t('Mise-à-jour des droits du fichier effectuée'), 'success');
      }
    } 
    
    return booltoint($bResult);
  }
  
  public function update() {
    
    $sPath = array_val('resource', $_POST);
    
    $oAction = new XML_Action(extractDirectory(__file__).'/file.eml');
    $oAction->getPath()->pushIndex(Controler::getFile($sPath));
    
    return $oAction;
  }
  
  public function updateName() {
    
    $bResult = false;
    
    $bDirectory = array_val('directory', $_POST) ? true : false;
    $sPath      = array_val('resource', $_POST);
    $sName      = array_val('name', $_POST);
    
    if (!$sName) dspm(t('Erreur dans la requête !'), 'error');
    else {
      
      if ($bDirectory) { // directory
        
        if (!$oDirectory = Controler::getDirectory($sPath)) dspm(t('Répertoire introuvable'), 'error');
        else $bResult = $oDirectory->updateName($sName);
        
      } else { // file
        
        if (!$oFile = Controler::getFile($sPath)) dspm(t('Fichier introuvable'), 'error');
        else $oResult = $oFile->updateName($sName); // update
      }
    }
    
    return (string) $oResult;
  }
}
