<?php

namespace sylma\storage\fs;
use \sylma\dom, \sylma\storage\fs, \sylma\core;

require_once('resource.php');

interface file extends fs\resource {

  function __construct($sName, fs\directory $parent, array $aRights, $iDebug);

  function getDirectory();

  /**
   * @return dom\document|null An XML document loaded with the content of this file
   *
   */
  function asDocument(array $aNS = array(), $iMode = \Sylma::MODE_READ, $bWhitespaces = false);

  /**
   * @return string The content of the text file
   */
  function read();

  /**
   * Get file name without extension
   * ex : /sylma/index.eml will output index
   */
  function getSimpleName();

  function getExtension();

  /**
   * Get last modify time
   */
  function getUpdateTime();

  /**
   * Check for file existanz
   */
  function updateStatut();

  function asPath();
}
