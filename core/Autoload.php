<?php

namespace sylma\core;
use sylma\core;

class Autoload
{
  protected $aNamespaces = array();
  protected $aNames = array();
  protected $aLoaded = array();

  public function __construct() {

    spl_autoload_register(array($this, 'loader'));
  }

  public function loadNamespaces(array $aNamespaces) {

    foreach ($aNamespaces as $sName => $sDirectory) {

      $this->register($sName, $sDirectory);
    }
  }

  public function loader($sValue) {

    $aClass = explode('\\', $sValue);
    $sName = $aClass[0];

    if (in_array($sName, $this->aNames)) {

      $sDirectory = $this->aNamespaces[$sName];

      $sClass = $sDirectory . implode('/', array_slice($aClass, 1));

      if (!in_array($sName, $this->aLoaded)) {

        $sFile = $sClass . '.php';
        include_once($sFile);

        $this->aLoaded[] = $sClass;
      }

      //file_put_contents('cache/debug.log', $sFile . "\n", \FILE_APPEND);
      //include_once(\Sylma::buildCache(\Sylma::ROOT . '/' . $sFile));
    }
  }

  public function register($sName, $sDirectory) {

    $this->aNames[] = $sName;
    $this->aNamespaces[$sName] = $sDirectory;
  }

}
