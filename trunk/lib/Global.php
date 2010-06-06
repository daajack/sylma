<?php

function t($s) {
  
  //$s = '[['.$s.']]';
  
  return $s;
}

function extractDirectory($sPath) {
  
  $sPath = substr($sPath, strlen(getcwd().MAIN_DIRECTORY) + 1);
  if (isset($_ENV['OS']) && strpos($_ENV['OS'], 'Win') !== false) $sPath = str_replace('\\', '/', $sPath);
  
  return substr($sPath, 0, strlen($sPath) - strlen(strrchr($sPath, '/')));
}

/*** Array ***/

function array_last($aArray, $mDefault = null) {
  
  if ($aArray) return array_val(count($aArray) - 1, $aArray);
  else return $mDefault;
}

/**
 * Si il existe, renvoie la valeur de l'index du tableau , sinon renvoie la valeur de $mDefault
 */
function array_val($sKey, $aArray, $mDefault = null) {
  
  //is_array($aArray) && (is_string($sKey) || is_numeric($sKey)) && 
  
  if (array_key_exists($sKey, $aArray)) return $aArray[$sKey];
  else return $mDefault;
}

function array_clear($aArray, $sDefault = '') {
  
  $aCopyArray = $aArray;
  
  foreach ($aArray as $sKey => $sValue)if (!$sValue) unset($aCopyArray[$sKey]);
  
  return $aCopyArray;
}

function array_remove($sKey, &$aArray, $bDebug = true) {
  
  if ($bDebug || array_key_exists($sKey, $aArray)) {
    
    $mValue = $aArray[$sKey];
    unset($aArray[$sKey]);
    
  } else $mValue = null;
  
  return $mValue;
}

function strtobool($sValue, $bDefault = null) {
  
  if (strtolower($sValue) == 'true') return true;
  else if (strtolower($sValue) == 'false') return false;
  else return $bDefault;
}

function booltostr($bValue) {
  
  return ($bValue) ? 'true' : 'false';
}

function booltoint($bValue) {
  
  return $bValue ? 1 : 0;
}

/**
 * Renvoie la première valeur non nulle envoyée en argument, si aucune, renvoie la dernière valeur
 */
function nonull_val() {
  
  foreach (func_get_args() as $mArg) {
    
    $mResult = $mArg;
    if ($mArg) return $mArg;
  }
  
  return $mResult;
}

/**
 * 'Quote' une chaîne, ou plusieurs dans un tableau
 */
function addQuote($mValue) {
  
  if (is_array($mValue)) {
    
    foreach ($mValue as &$mSubValue) $mSubValue = addQuote($mSubValue);
    return $mValue;
    
  } else if ($sResult = (string) $mValue) return "'".addslashes($sResult)."'";
  else return null;
}

/**
 * Formate le nombre donnée en argument au format prix (p.ex : 1'999.95)
 */
function formatPrice($fNumber) {
  
  if (is_numeric($fNumber)) return 'CHF '.number_format($fNumber, 2, '.', "'");
  else return '';
}

function formatMemory($size) {
  
  $aUnit = array('b','Kb','Mb','Gb','Tb','Pb');
  return round($size / pow(1024, ($iResult = floor(log($size,1024)))), 2).' '.$aUnit[$iResult];
}

function stringResume($mValue, $iLength = 50, $bXML = false) {
  
  $sValue = (string) $mValue;
  
  if (strlen($sValue) > $iLength) $sValue = substr($sValue, 0, $iLength).'...';
  
  if ($bXML) {
    
    $iLastSQuote = strrpos($sValue, '&');
    $iLastEQuote = strrpos($sValue, ';');
    
    if (($iLastSQuote) && ($iLastEQuote < $iLastSQuote)) $sValue = substr($sValue, 0, $iLastSQuote).'...';
  }
  
  return $sValue;
}

/**
 * Fusionne les clés et les valeurs en insérant une chaîne de séparation
 */
function fusion($sSep, $aArray) {
  
  $aResult = array();
  
  foreach ($aArray as $sKey => $sVal) $aResult[] = $sKey.$sSep.$sVal;
  
  return $aResult;
}

/**
 * Implosion = fusion + implode
 */
function implosion($sSepFusion, $sepImplode, $aArray) {
  
  return implode($sepImplode, fusion($sSepFusion, $aArray));
}

/**
 * Conversion in UTF-8 of the characters : & " < >
 */
function xmlize($sString) {
  
  return htmlspecialchars($sString, ENT_COMPAT, 'UTF-8');
}

/**
 * Return a color between gray and xxx, depends on fValue
 */
function inter_color($fValue) {
  
  $iColor = 255 - intval(255 * $fValue);
  
  return "rgb($iColor, $iColor, 213)";
}

function float_format($mValue, $iDec = 2, $iPoint = '.', $iThousand = '\'') {
  
  return (is_float($mValue) ? number_format($mValue, $iDec, $iPoint, $iThousand) : $mValue);
}

/* Display function */

function dspf($mVar, $sStatut = 'success') {
  
  dspm(view($mVar, false), $sStatut); 
}

function dspm($mVar, $sStatut = 'success') {
  
  Controler::addMessage($mVar, $sStatut);
}

function view($mVar, $bFormat = true) {
  
  return Controler::formatResource($mVar, $bFormat);
}

/*
 * Pour le débuggage, affiche une variable dans un tag <pre> qui affiche les retours à la ligne
 **/
function dsp($mVar) {
  
  echo '<pre>';
  print_r($mVar);
  echo '</pre>';
}
