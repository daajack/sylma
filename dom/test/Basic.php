<?php

namespace sylma\dom\test;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs;

class Basic extends tester\Parser implements core\argumentable {

  protected $sTitle = 'DOM';

  public function __construct() {

    $this->setDirectory(__file__);

    $this->setSettings('settings.xml');
    parent::__construct();

    $this->setControler($this);
  }

  public function getFile($sPath = '', $bDebug = true) {

    return parent::getFile($sPath, $bDebug);
  }

  public function createDocument($mContent = null) {

    return parent::createDocument($mContent);
  }
}

