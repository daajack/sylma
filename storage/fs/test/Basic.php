<?php

namespace sylma\storage\fs\test;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs;

class Basic extends tester\Profiler implements core\argumentable {

  const NS = 'http://www.sylma.org/storage/fs/test';
  protected $sTitle = 'Basic';

  public function __construct() {

    $this->getManager('dom');

    $this->setDirectory(__file__);
    $this->setNamespace(self::NS, 'self');

    $this->setArguments('settings.yml');
    parent::__construct();
    //$this->setArguments(\Sylma::load('../settings.xml.php', __DIR__));

    $dir = $this->getDirectory();

    $this->setManager($this->createManager((string) $dir));

    $this->setFiles(array($this->getFile('basic.xml'), $this->getFile('tokened.xml')));
  }

  protected function createManager($sPath) {

    $result = $this->create('manager', array(\Sylma::ROOT));
    $result->loadDirectory($sPath);

    return $result;
  }

  protected function test(dom\element $test, $sContent, $controler, dom\document $doc, fs\file $file) {

    $result = parent::test($test, $sContent, $controler, $doc, $file);

    $sPath = $this->getManager()->getDirectory()->getName();

    $this->setManager($this->createManager($sPath));

    return $result;
  }
}


