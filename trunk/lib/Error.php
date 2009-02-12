<?php
/*
 * Classe de gestion des erreurs
 * Created on 23 oct. 2008
 */

function userErrorHandler($errno, $errstr, $errfile, $errline) {
  
  $oMessage = new HTML_Div;
  $oMessage->add(
    new HTML_Strong("ERREUR [$errno] "),
    $errstr,
    new HTML_Tag('i', " - [$errline] - $errfile"),
    new HTML_Br,
    Controler::getBacktrace());
  
  if (Controler::isAdmin()) {
    
    if (!DEBUG && Controler::isReady()) Controler::getMessages()->addMessage(new Message($oMessage, 'error'));
    else echo $oMessage;
  }
  
  return true;
}