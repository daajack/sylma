<?php

class XML_Database {
  
  //private $sDatabase;
  private $oSession;
  private $aSession;
  
  public function __construct($aDB) {
    
    try {
      
      $oSession = new Session($aDB['host'], $aDB['port'], $aDB['user'], $aDB['password']);
      
      if (!$oSession->execute('OPEN '.$aDB['database'])) {
        
        dspm(xt('Impossible de se connecter à la base de donnée %s : %s', $aDB['database'], $oSession->info()), 'db/error');
      }
      
      //$this->sDatabase = $sDatabase;
      $this->oSession = $oSession;
      
    } catch (Exception $e) {
      
      //dspm(xt('Impossible de se connecter au serveur de base de donnée : %s', $e->getMessage()), 'db/error');
    }
  }
  
  private function run($sCommand) {
    
    $oSession = $this->oSession;
    
    $sResult = '';
    //$sMessage = '';
    //$bError = false;
    
    if ($oSession) {
      
      if ($oSession->execute($sCommand)) $sResult = $oSession->result();
      else dspm(xt('Commande %s invalide. (%s)', view($sCommand), new HTML_Tag('em', $oSession->info())), 'action/error');
    }
    
    //$oSession->close();
    
    return $sResult;
  }
  
  public function query($sQuery) {
    
    if (SYLMA_DB_SHOW_QUERIES) dspm(xt('xquery : %s', new HTML_Tag('pre', $sQuery)), 'db/notice');
    
    return $this->run('xquery '.$sQuery);
  }
  
  public function get($sQuery) {
    
    $mResult = false;
    
    if ($sResult = $this->query($sQuery)) {
      
      $mResult = new XML_Document($sResult);
      $mResult = $mResult->getRoot();
    }
    
    return $mResult;
  }
  
  public function load($sId) {
    
    return new XML_Document($this->query("id('$sId')"));
  }
  
  public function delete($sId) {
    
    return $this->query("delete node id('$sId')");
  }
  
  public function update($sId, XML_Element $oElement) {
    
    return $this->query("replace node id('$sId') with $oElement");
  }
  
  public function insert(XML_Element $oElement, $sTarget) {
    
    return $this->query("insert nodes $oElement as last into $sTarget");
  }
  
  public function __destruct() {
    
    if ($this->oSession) $this->oSession->close();
  }
}
