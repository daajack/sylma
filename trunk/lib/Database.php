<?php

/*
 * Classe des gestion de la DB
 **/
class db {
  
  private static $rDb;
  private static $aArguments;
  private static $aQueries = array();
  
  public static function buildTable($oDocument, $aHeaders = array(), $sPath) {
    
    $oHeaders = new XML_Element('headers');
    $oHeaders->addArray($aHeaders, 'field');
    
    if ($aHeaders) $oDocument->getRoot()->shift($oHeaders);
    
    $oDocument->getRoot()->setAttribute('path_to', $sPath);
    
    return $oDocument->parseXSL(new XML_Document('/xml/query-table.xsl'));
  }
  
  public static function getXML($sQuery) {
    
    $oResult = self::query($sQuery);
    $oDocument = new XML_Document();
    
    if (!mysql_num_rows($oResult)) {
      
      Controler::addMessage('Aucun résultat pour la requête !', 'warning');
      
    } else {
      
      $aRow = mysql_fetch_assoc($oResult);
      
      $oDocument->set('record');
      $oDocument->add(self::getXMLRow($aRow)->getChildren());
    }
    
    return $oDocument;
  }
  
  public static function queryXML($sQuery) {
    
    $oResult = self::query($sQuery);
    $oDocument = new XML_Document();
    
    if (!mysql_num_rows($oResult)) {
      
      Controler::addMessage('Aucun résultat pour la requête !', 'warning');
      
    } else {
      
      $oDocument->set('records');
      while ($aRow = mysql_fetch_assoc($oResult)) $oDocument->add(self::getXMLRow($aRow));
    }
    
    return $oDocument;
  }
  
  public static function getXMLRow($aRow, $sName = 'row') {
    
    $oElement = new XML_Element($sName);
    
    foreach ($aRow as $sFieldKey => $sFieldValue) {
      
      $oElement->addNode($sFieldKey, $sFieldValue);
    }
    
    return $oElement;
  }
  
  public static function query($sQuery) {
    
    $rResult = mysql_query($sQuery) or die (self::getError($sQuery));
    
    if (substr($sQuery, 0, 6) == 'SELECT') {
      
      $iCountRows = mysql_affected_rows();
      if (!$iCountRows) $iCountRows = '0';
      if ($iCountRows < 10) $iCountRows = '0'.$iCountRows;
      $oCountRows = new HTML_Strong($iCountRows, array('style' => 'color: red;'));
      $sQuery = '['.$oCountRows.'] '.$sQuery;
    }
    
    // self::$aQueries[] = $sQuery;
    
    if (Controler::isAdmin()) {
      
      $sResult = self::queryColorize($sQuery);
      
      $oDocument = new XML_Document(new HTML_Div(strtoxml($sResult)));
      
      Controler::addMessage($oDocument->getRoot(), 'query');
    }
    
    return $rResult;
  }
  
  public static function getError($sQuery) {
    
    $oMessages = new Messages(array('error'));
    
    if (Controler::isAdmin()) {
      
      $oMessages->addStringMessage(t('Erreur MySQL').' : '.mysql_error(self::$rDb), 'error');
      $oMessages->addStringMessage($sQuery, 'error');
      
    } else $oMessages->addStringMessage(t('Erreur dans la base de données !'), 'error');
    
    Controler::errorRedirect($oMessages->getMessages());
  }
  
  public static function connect() {
    
    self::$rDb = mysql_connect(self::getArgument('host'), self::getArgument('user'), self::getArgument('password'));
    mysql_select_db(self::getArgument('database'));
    
    mysql_query('SET CHARACTER SET utf8');
  }
  
  private static function queryColorize($sQuery) {
    
    $aRemplacements = Array(
      
      '/((?:\'[^\']*\'))/' => // chaînes
        '<span class="query-string">\1</span>',
        
      '/([ ,\(])(\d+)/' => // nombres
        '\1<span class="query-number">\2</span>',
        
      '/( (?:=|<|>|IN) )/i' => // Opérateurs
        '<span style="color: #aaa">\1</span>',
        
      // '/(\w+ AS \w+)/i' => // AS
        // '<span class="query-as>\1</span></span>',
        
      '/(\s(?:AS|AND|OR|ON|ASC|DESC)(?: |$))/i' => // Mots clé 1
        '<span class="query-keyword-1">\1</span>',
        
      '/(SELECT|INSERT|UPDATE|DELETE|COUNT|FROM|WHERE|LEFT|RIGHT|INNER|JOIN|ORDER BY|GROUP BY|SET)/i' => // Mots clé 2
        '<span class="query-keyword-2">\1</span>',
    );
    
    $sResult = preg_replace(array_keys($aRemplacements), $aRemplacements, $sQuery);
    
    return $sResult;
  }
  
  public static function getQueries($sStatut = 'new') {
    
    $sStatut = 'query-'.$sStatut;
    
    $aResults = self::queryColorize(self::$aQueries);
    $aMessages = array();
    
    foreach ($aResults as $sResult) {
      
      $oDocument = new XML_Document(new HTML_Div(strtoxml($sResult)));
      
      $aMessages[] = new Message($oDocument->getRoot(), $sStatut);
    }
    
    return $aMessages;
  }
  
  public static function buildUpdate($aFields) {
    
    return 'SET '.implode(', ', fusion(' = ', $aFields));
  }
  
  public static function buildInsert($aFields) {
    
    return db::buildMultiInsert(array_keys($aFields), array($aFields));
  }
  
  public static function buildMultiInsert($aKeys, $aValues) {
    
    foreach ($aValues as &$aValue) $aValue = '('.implode(', ', $aValue).')';
    
    $sKeys = implode(', ', $aKeys);
    $sValues = implode(', ', $aValues);
    
    return "($sKeys) VALUES $sValues";
  }
  
  public static function buildWhere($aFields = array(), $sOperator = '=', $sLogic = 'AND') {
    
    foreach ($aFields as $sKey => &$sValue) if (is_string($sKey)) $sValue = $sKey." $sOperator ".$sValue;
    //fusion(" $sOperator ", $aFields)
    
    return '('.implode(" $sLogic ", $aFields).')';
  }
  
  public static function buildDate($sDate = '') {
    
    if ($sDate) return "STR_TO_DATE('$sDate', '%d.%m.%Y')";
    else return 'NULL';
  }
  
  public static function formatString($sString = '') {
    
    return addQuote(mysql_real_escape_string($sString));
  }
  
  public function setArgument($sKey, $sValue) {
    
    self::$aArguments[$sKey] = $sValue;
  }
  
  public function setArguments($aArguments = array()) {
    
    if (is_array($aArguments)) self::$aArguments = $aArguments;
  }
  
  private function getArgument($sKey) {
    
    return isset(self::$aArguments[$sKey]) ? self::$aArguments[$sKey] : null;
  }
  
  private function getArguments() {
    
    return self::$aArguments;
  }
}
