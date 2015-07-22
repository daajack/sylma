<?php

namespace sylma\core\argument\parser\test;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs;

class Standalone extends tester\Prepare implements core\argumentable {

  const NS = 'http://2013.sylma.org/core/argument/parser/test';

  protected $sTitle = 'Standalone';

  public function __construct() {

    $this->setDirectory(__file__);
    $this->setNamespace(self::NS, 'self');

    //if (!$manager) $manager = $this;

    $this->setManager($this->getManager('parser'));
    $this->setFiles(array($this->getFile('standalone.xml')));

    parent::__construct();
  }

  public function getDirectory($sPath = '', $bDebug = true) {

    return parent::getDirectory($sPath, $bDebug);
  }

  public function getArgument($sPath, $bDebug = true, $mDefault = null) {

    return parent::getArgument($sPath, $bDebug, $mDefault);
  }

  public function setArgument($sPath, $mValue) {

    return parent::setArgument($sPath, $mValue);
  }

  protected function test(dom\element $test, $sContent, $manager, dom\document $doc, fs\file $file) {

    return parent::test($test, $sContent, $this, $doc, $file);
  }

  public function createHandler($sPath) {

    $file = $this->getManager('fs')->getFile($sPath, $this->getDirectory('samples'));

    return $this->getManager()->load($file, array(), true);
  }
}

