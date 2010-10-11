<?php

class XML_Database {
  
  //private $sDatabase;
  private $oSession;
  
  private $aNamespaces = array();
  private $sNamespace = '';
  
  public function __construct($aDB) {
    
    try {
      
      $oSession = new Session($aDB['host'], $aDB['port'], $aDB['user'], $aDB['password']);
      
      if (!$oSession->execute('OPEN '.$aDB['database'])) {
        
        dspm(xt('Impossible de se connecter à la base de donnée %s : %s', $aDB['database'], $oSession->info()), 'db/error');
      }
      
      //$this->sDatabase = $sDatabase;
      $this->oSession = $oSession;
      $this->sNamespace = $aDB['namespace'];
      
    } catch (Exception $e) {
      
      //dspm(xt('Impossible de se connecter au serveur de base de donnée : %s', $e->getMessage()), 'db/error');
    }
  }
  
  public function run($sCommand) {
    
    $oSession = $this->oSession;
    
    $sResult = '';
    $bResult = true;
    //$sMessage = '';
    
    if ($oSession) {
      
      if ($bResult = $oSession->execute($sCommand)) $sResult = $oSession->result();
      else dspm(xt('Commande %s invalide. (%s)', view($sCommand), new HTML_Tag('em', $oSession->info())), 'action/error');
    }
    
    //$oSession->close();
    if (SYLMA_DB_SHOW_RESULTS) dspm(xt('xquery [result] : %s', new HTML_Tag('pre', ($bResult ? $sResult : '[error]'))), 'db/notice');
    
    return $sResult;
  }
  
  public function query($sQuery, array $aNamespaces = array()) {
    
    $sDeclare = ''; // namespaces declarations
    $aNamespaces = array_merge(array($this->sNamespace), $this->aNamespaces, $aNamespaces);
    
    foreach ($aNamespaces as $sPrefix => $sNamespace) {
      
      if (!$sPrefix) $sDeclare .= "declare default element namespace '{$sNamespace}';\n";
      else $sDeclare .= "declare namespace {$sPrefix}='{$sNamespace}';\n";
    }
    
    $sQuery = $sDeclare.$sQuery;
    
    if (SYLMA_DB_SHOW_QUERIES) dspm(xt('xquery [query] : %s', new HTML_Tag('pre', $sQuery)), 'db/notice');
    
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
  
  public function getNamespace($sPrefix = '') {
    
    if ($sPrefix) return $this->aNamespaces[$sPrefix];
    else return $this->sNamespace;
  }
  
  public function setNamespace($sNamespace, $sPrefix = '') {
    
    if ($sPrefix) $this->aNamespaces[$sPrefix] = $sNamespace;
    else $this->sNamespace = $sNamespace;
  }
  
  public function insert($mElement, $sTarget, array $aNamespaces = array()) {
    
    return $this->query("insert nodes $mElement into $sTarget", $aNamespaces);
  }
  
  public function __destruct() {
    
    if ($this->oSession) $this->oSession->close();
  }
}
