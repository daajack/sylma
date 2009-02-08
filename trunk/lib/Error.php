<?php
/*
 * Classe de gestion des erreurs
 * Created on 23 oct. 2008
 */

function userErrorHandler($errno, $errstr, $errfile, $errline) {
  
  $oMessage = new HTML_Div;
  $oMessage->add(
    new HTML_Strong('ERREUR'),
    " [$errno] $errstr - [$errline] - $errfile",
    new HTML_Br,
    Controler::getBacktrace());
  
  if (Controler::isAdmin()) {
    
    if (Controler::isReady()) Controler::addMessage(new Message($oMessage), 'error');
    else echo $oMessage;
    // echo $oMessage;
  }
  
  return true;
}