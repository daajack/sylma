<?php
/*
 * Classe de gestion des erreurs
 * Created on 23 oct. 2008
 */

function userErrorHandler($errno, $errstr, $errfile, $errline) {
  
  $sMsg = "<strong>ERREUR</strong> [$errno] $errstr - [$errline] - $errfile<br/>";
  
  if (Controler::isAdmin()) {
    
    if (Controler::isReady()) Controler::addMessage($sMsg, 'error');
    else echo $sMsg;
  }
  
  return true;
}