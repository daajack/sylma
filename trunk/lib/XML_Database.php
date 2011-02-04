<?php

class XML_Database {
  
  private $oSession;
  
  private $aNamespaces = array();
  private $sNamespace = '';
  private $sCollection = '';
  private $iHits = 0; // temp value for result's hits count
  
  public function __construct($aDB) {
    
    try {
      
      $db = new eXist($aDB['user'], $aDB['password'], $aDB['host']);
      if (!$db->connect()) dspm($db->getError(), 'db/error');
      
      //$this->sDatabase = $sDatabase;
      $this->oSession = $db;
      $this->sNamespace = $aDB['namespace'];
      $this->sCollection = $aDB['collection'];
      
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
  
  public function setNamespaces($aNamespace) {
    
    foreach ($aNamespaces as $sPrefix => $sNamespace) $this->setNamespace($sPrefix, $sNamespace);
  }
  
  public function setNamespace($mNamespace, $sPrefix = '') {
    
    if ($sPrefix) $this->aNamespaces[$sPrefix] = $mNamespace;
    else $this->sNamespace = $mNamespace;
  }
  
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
      
      $hits = 0;
      $queryTime = 0;
      
      if (!$aResult = $this->getSession()->xquery($sQuery)) { // no result
        
        if ($bGetResult) {
          
          dspm(array(
            new HTML_Strong(t('Erreur dans la requête : ')),
            $this->getError(),
            new HTML_Hr,
            new HTML_Tag('pre', $sQuery)), 'db/warning');
            
        } else if (($sError = $this->getSession()->getError()) && $sError != 'ERROR: No data found!') {
          
          dspm($sError, 'db/error');
          
        } else $sResult = 1;
        
      } else { // has result
        
        $sResult = '';
        
        $hits = $aResult['HITS'];
        $queryTime = $aResult['QUERY_TIME'];
        // $collections = $aResult['COLLECTIONS'];
        
        if (!empty($aResult['XML'])) {
          
          if (is_string($aResult['XML'])) $sResult = $aResult['XML'];
          else {
            
            foreach ($aResult['XML'] as $sItem) $sResult .= $sItem;
          }
        }
      }
      
      $oResults = xt('[ time : %s s] [ hits : %s ]',
        new HTML_Strong(floatval($queryTime / 1000)),
        new HTML_Strong($hits));
      
      if (SYLMA_DB_SHOW_QUERIES) dspm(array(t('xquery [query] '), $oResults, new HTML_Tag('pre', $sQuery)), 'db/notice');
      if (SYLMA_DB_SHOW_RESULTS) dspm(array(t('xquery [result] '), $oResults, new HTML_Tag('pre', $sResult)), 'db/notice');
    }
    
    return $sResult;
  }
  
  public function get($sQuery, array $aNamespaces = array(), $bDocument = false) {
    
    $mResult = false;
    
    if ($sResult = $this->query($sQuery, $aNamespaces)) {
      
      $mResult = strtoxml($sResult);
      if ($bDocument) $mResult = new XML_Document($mResult);
    }
    
    return $mResult;
  }
  
  public function escape($sValue) {
    
    return addQuote($sValue);
  }
  
  public function hasDocument($sDocument) {
    
    return 'xs:boolean('.$this->pathDocument($sDocument).')';
  }
  
  public function pathDocument($sDocument) {
    
    return "doc('{$this->getCollection()}/$sDocument')";
  }
  
  public function getCollection($bFormat = false) {
    
    if ($bFormat) return "collection('{$this->sCollection}')";
    else return $this->sCollection;
  }
  
  public function load($sID) {
    
    if ($sResult = $this->query("{$this->getCollection(true)}//id('$sID')")) return new XML_Document($sResult);
    return null;
  }
  
  public function delete($sID, array $aNamespaces = array()) {
    
    return $this->query("update delete {$this->getCollection(true)}//id('$sID')", $aNamespaces, false);
  }
  
  public function update($sID, XML_Document $oDocument, array $aNamespaces = array()) {
    
    return $this->query("update replace {$this->getCollection(true)}//id('$sID') with {$oDocument->display(true, false)}", $aNamespaces, false);
  }
  
  public function insert(XML_Document $oDocument, $sTarget, array $aNamespaces = array()) {
    
    return $this->query("update insert {$oDocument->display(true, false)} into $sTarget", $aNamespaces, false);
  }
  
  public function __destruct() {
    
    if ($this->oSession && !$this->getSession()->disconnect()) {
      //dspm(xt('Erreur pendant la déconnexion : %s', $this->getError()), 'db/error');
    }
  }
}

class XDB_Module extends Module {
  
  protected function getDB() {
    
    return Controler::getDatabase();
  }
  
  protected function getCollection($sDocument = '') {
    
    //return "doc('{$this->getDB()->getCollection()}/{$this->readOption('parent-path')}')";
    // $sParentPath = $this->readOption('parent-path', false);
    // $sParent = $sParentPath ? $sParentPath : $this->readOption('parent').'/*';
    
    if ($sDocument) $sDocument = '/'.$sDocument;
    
    return "doc('{$this->getDB()->getCollection()}$sDocument')";
  }
  
  protected function buildValues($oValues, XML_Element $oParent = null) {
    
    if (!$oParent) $oParent = new XML_Document($this->getEmpty());
    // dspf($this->getEmpty());
    foreach ($oValues->getChildren() as $oValue) {
      
      if ($oValue->isElement()) {
        
        if (substr($oValue->getName(), 0, 4) == 'attr') {
          
          $sName = substr($oValue->getName(), 5);
          
          if ($sName == 'id') $oParent->setAttribute('xml:'.$sName, $oValue->read());
          else $oParent->setAttribute($sName, $oValue->read());
          
        } else {
          
          $oChild = $oParent->addNode($this->getFullPrefix().$oValue->getName(), null, null, $this->getNamespace());
          
          if ($oValue->isComplex()) $oChild->add($this->buildValues($oValue, $oChild));
          else $oChild->add($oValue->read());
          
          if (!trim($oChild->read())) $oChild->remove();
        }
      }// else dspf($oValue);
    }
    
    return $oParent;
  }
  
  protected function getPost(Redirect $oRedirect, $bMessage = true) {
    
    $oResult = null;
    
    if (!$oPost = $oRedirect->getDocument('post')) {
      
      if ($bMessage) {
        
        dspm(t('Une erreur s\'est produite. Impossible de continuer. Modifications perdues'), 'error');
        dspm(t('Aucune données dans $_POST'), 'action/warning');
      }
      
    } else {
      
      if (!$oValues = $this->buildValues($oPost)) {
        
        if ($bMessage) {
          
          dspm(t('Impossible de lire les valeurs envoyés par le formulaire'), 'error');
          dspm(xt('Erreur dans la conversion des valeurs %s dans $_POST', view($oPost)), 'action/error');
        }
        
      } else $oResult = $oValues;
    }
    
    return $oResult;
  }
  
  public function mergeNamespaces($aNamespaces = array()) {
    
    if ($aNamespaces) return array_merge($this->getNS(), $aNamespaces);
    else return $this->getNS();
  }
  
  public function load($sID) { // TOUSE ?
    
   return $this->getDB()->load($sID);
  }
  
  public function get($sQuery, $bDocument = false, array $aNamespaces = array()) {
    
    return $this->getDB()->get($sQuery, $this->mergeNamespaces($aNamespaces), $bDocument);
  }
  
  public function query($sQuery, array $aNamespaces = array(), $bGetResult = true) {
    
    return $this->getDB()->query($sQuery, $this->mergeNamespaces($aNamespaces), $bGetResult);
  }
  
  public function update($sID, XML_Document $oDocument, array $aNamespaces = array()) {
    
    return $this->getDB()->update($sID, $oDocument, $this->mergeNamespaces($aNamespaces));
  }
  
  public function insert(XML_Document $mElement, $sTarget, array $aNamespaces = array()) {
    
    return $this->getDB()->insert($mElement, $sTarget, $this->mergeNamespaces($aNamespaces));
  }
  
  public function delete($sID, array $aNamespaces = array()) {
    
    return $this->getDB()->delete($sID, $this->mergeNamespaces($aNamespaces));
  }
}




