<?php

class Messages extends XML_Document {
  
  private $aAllowedMessages = array();
  private $aStatuts = array();
  private $oLast = null;
  
  public function __construct($oSchema = null, $mMessages = array()) {
    
    parent::__construct('messages');
    
    if ($oSchema) $this->add($oSchema->getChildren());
    
    $this->addMessages($mMessages);
  }
  
  public function addMessage($oMessage) {
    
    $oResult = null;
    
    if (($oMessage instanceof XML_Element) && $oMessage->useNamespace(NS_MESSAGES)) {
      
      // TODO: foreach ($oMessage->aArguments as $oArgument) $this->setArgument('fields'][ += $oMessage[]
      
      $sPath = $oMessage->read('path');
      
      if ($oCategory = $this->get($sPath)) {
        
        $oResult = $oCategory->add($oMessage);
        $this->oLast = $oMessage;
      }
      
    }
    
    return $oResult;
  }
  
  /*
   * Add a message from a String
   * 
   * @param $mMessage
   *   The message format String
   * @param $sStatut
   *   The stat of the message format String
   * @param $aArguments
   *   The arguments of the message format Array
   * @return
   *   A pointer to the node added
   **/
  public function addStringMessage($mMessage, $sPath = 'user', $aArguments = array()) {
    
    return $this->addMessage(new Message($mMessage, $sPath, $aArguments));
  }
  
  /*
   * Ajoute une liste de messages dans la pile
   * 
   * @param $aMessages
   *   Un tableau contenant les messages à ajouter
   **/
  public function addMessages($aMessages) {
    
    $aResult = array();
    foreach ($aMessages as $oMessage) $aResult[] = $this->addMessage($oMessage);
    
    return $aResult;
  }
  
  /*
   * Récupère les messages sous forme de liste
   * 
   * @param $sPath
   *   Si donné, seul les messages du statut correspondant seront récupérés
   **/
  public function getMessages($sPath = '') {
    
    $oResult = $this->query("$sPath//lm:message", 'lm', NS_MESSAGES);
    if (!$oResult->length) $oResult = array();
    
    return $oResult;
  }
  
  public function useStatut($sPath) {
    
    if (!array_key_exists($sPath, $this->aStatuts)) $this->aStatuts[$sPath] = ($this->get($sPath));
    
    return $this->aStatuts[$sPath];
  }
  
  public function hasMessages($sPath = '') {
    
    return ($this->getMessages($sPath));
  }
  
  public function parse() {
    
    if ($this->get('//lm:message', 'lm', NS_MESSAGES)) {
      
      return $this->parseXSL(new XML_Document(Controler::getSettings('messages/template/@path')));
      
    } else return null;
  }
}

class Message extends XML_Element {
  
  public function __construct($mMessage, $sPath = '', $aArgs = array()) {
    
    parent::__construct('lm:message', null, null, NS_MESSAGES);
    
    if (!$sPath) $sPath = '/';
    
    $this->addNode('content', $mMessage);
    $this->addNode('path', $sPath);
    $this->addNode('arguments')->addArray($aArgs);
  }
}

