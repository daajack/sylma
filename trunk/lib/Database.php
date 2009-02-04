<?php

/*
 * Classe des gestion de la DB
 **/
class db {
  
  private static $rDb;
  private static $aArguments;
  private static $aQueries = array();
  
  public static function query($sQuery = '') {
    
    $rResult = mysql_query($sQuery) or die (self::getError($sQuery));
    
    if (substr($sQuery, 0, 6) == 'SELECT') {
      
      $iCountRows = mysql_affected_rows();
      if (!$iCountRows) $iCountRows = '0';
      if ($iCountRows < 10) $iCountRows = '0'.$iCountRows;
      $oCountRows = new HTML_Strong($iCountRows, array('style' => 'color: red;'));
      $sQuery = '['.$oCountRows.'] '.$sQuery;
    }
    
    self::$aQueries[] = $sQuery;
    
    return $rResult;
  }
  
  public static function getError($sQuery) {
    
    $oMessages = new Messages;
    
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
  
  private static function queryColorize($aQueries) {
    
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
    
    $aResults = preg_replace(array_keys($aRemplacements), $aRemplacements, $aQueries);
    // echo htmlentities($aResults[0]);
    
    foreach ($aResults as &$sResult) {
      
      $oDocument = new XML_Document;
      $oDocument->loadText('<li>'.$sResult.'</li>');
      $sResult = $oDocument->getRoot();
    }
    
    return $aResults;
  }
  
  public static function getQueries() {
    
    return self::queryColorize(self::$aQueries);
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
  
  public static function buildString($sString = '') {
    
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
