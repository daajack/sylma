<?php

namespace sylma\dom;
use \sylma\dom, \sylma\storage\fs;

require_once('document.php');
require_once('complex.php');

interface handler extends dom\document, dom\complex {
  
  const NS = 'http://www.sylma.org/dom/handler';
  
  function setFile(fs\file $file);
  function getFile();
  
  function createElement($sName, $mContent = '', array $aAttributes = array(), $sUri = null);
}