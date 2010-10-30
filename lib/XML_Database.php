<?php

class XML_Database {
  
  //private $sDatabase;
  private $oSession;
  
  private $aNamespaces = array();
  private $sNamespace = '';
  
  public function __construct($aDB) {
    
    try {
      
      $db = new eXist($aDB['user'], $aDB['password'], $aDB['host']);
      if (!$db->connect()) dspm($db->getError(), 'db/error');
      
      //$this->sDatabase = $sDatabase;
      $this->oSession = $db;
      $this->sNamespace = $aDB['namespace'];
      
    } catch (Exception $e) {
      
      dspm(xt('Impossible de se connecter au serveur de base de donnée : %s', $e->getMessage()), 'db/error');
    }
  }
  
  public function getError() {
    
    return $this->getSession() ? $this->getSession()->getError() : null;
  }
  
  public function getSession() {
    
    return $this->oSession;
  }
  
  public function getNamespace($sPrefix = '') {
    
    if ($sPrefix) return $this->aNamespaces[$sPrefix];
    else return $this->sNamespace;
  }
  
  public function setNamespace($sNamespace, $sPrefix = '') {
    
    if ($sPrefix) $this->aNamespaces[$sPrefix] = $sNamespace;
    else $this->sNamespace = $sNamespace;
  }
  
  /*
  public function run($sCommand) {
    
    $oSession = $this->oSession;
    
    $sResult = '';
    $bResult = true;
    //$sMessage = '';
    
    if ($oSession) {
      
      if ($bResult = $oSession->execute($sCommand)) $sResult = $oSession->result();
      else dspm(xt('Commande %s invalide. (%s)', view($sCommand), new HTML_Tag('em', $oSession->info())), 'action/warning');
    }
    
    //$oSession->close();
    if (SYLMA_DB_SHOW_RESULTS) dspm(xt('xquery [result] : %s', new HTML_Tag('pre', ($bResult ? $sResult : '[error]'))), 'db/notice');
    
    return $sResult;
  }
  */
  public function query($sQuery, array $aNamespaces = array(), $bGetResult = true) {
    
    $sResult = null;
    
    if (!$this->getSession()) dspm(t('Aucun base de données instanciées'), 'db/error');
    else {
      
      $sDeclare = ''; // namespaces declarations
      $aNamespaces = array_merge(array($this->sNamespace), $this->aNamespaces, $aNamespaces);
      
      foreach ($aNamespaces as $sPrefix => $sNamespace) {
        
        if ($sPrefix === 0) $sDeclare .= "declare default element namespace '{$sNamespace}';\n";
        else if ($sPrefix) $sDeclare .= "declare namespace {$sPrefix}='{$sNamespace}';\n";
      }
      
      $sQuery = $sDeclare.$sQuery;
      
      if (SYLMA_DB_SHOW_QUERIES) dspm(xt('xquery [query] : %s', new HTML_Tag('pre', $sQuery)), 'db/notice');
      
      if (!$aResult = $this->getSession()->xquery($sQuery)) {
        
        if ($bGetResult) {
          
          dspm(array(
            new HTML_Strong(t('Erreur dans la requête : ')),
            $this->getError(),
            new HTML_Hr,
            new HTML_Tag('pre', $sQuery)), 'db/error');
        }
        
      } else {
        
        $sResult = '';
        
        $hits = $aResult['HITS'];
        $queryTime = $aResult['QUERY_TIME'];
        $collections = $aResult['COLLECTIONS'];
        
        if (!empty($aResult['XML'])) {
          
          if (is_string($aResult['XML'])) $sResult = $aResult['XML'];
          else {
            
            foreach ($aResult['XML'] as $sItem) $sResult .= $sItem;
          }
        }
        
        if (SYLMA_DB_SHOW_RESULTS) dspm(xt('xquery [result] : %s', new HTML_Tag('pre', $sResult)), 'db/notice');
      }
    }
    
    return $sResult;
  }
  
  public function get($sQuery, array $aNamespaces = array(), $bDocument = false) {
    
    $mResult = false;
    
    if ($sResult = $this->query($sQuery, $aNamespaces)) {
      
      $mResult = new XML_Document($sResult);
      if (!$bDocument) $mResult = $mResult->getRoot();
    }
    
    return $mResult;
  }
  
  public function load($sID) {
    
    if ($sResult = $this->query("//id('$sID')")) return new XML_Document($sResult);
    return null;
  }
  
  public function delete($sID, array $aNamespaces = array()) {
    
    return $this->query("update delete //id('$sID')", $aNamespaces, false);
  }
  
  public function update($sID, XML_Document $oDocument, array $aNamespaces = array()) {
    
    return $this->query("update replace //id('$sID') with {$oDocument->display(true, false)}", $aNamespaces, false);
  }
  
  public function insert($mElement, $sTarget, array $aNamespaces = array()) {
    
    return $this->query("update insert $mElement into $sTarget", $aNamespaces, false);
  }
  
  public function __destruct() {
    
    if ($this->oSession && !$this->getSession()->disconnect()) {
      dspm(xt('Erreur pendant la déconnexion : %s', $this->getError()), 'db/error');
    }
  }
}
