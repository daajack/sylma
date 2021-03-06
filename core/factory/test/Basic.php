<?php

namespace sylma\core\factory\test;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs;

class Basic extends tester\Profiler implements core\argumentable {

  const NS = 'http://www.sylma.org/core/factory/test';

  protected $sTitle = 'Cached';

  public function __construct() {

    $this->getManager('dom');

    $this->setDirectory(__file__);
    $this->setNamespace(self::NS, 'self');

    $this->setManager($this->getFactory());
    parent::__construct();
  }

  public function createArgument($mArguments, $sNamespace = '') {

    return parent::createArgument($mArguments, $sNamespace);
  }

  public function getDirectory($sPath = '', $bDebug = true) {

    return parent::getDirectory($sPath, $bDebug);
  }

  public function createFactory(core\argument $arg = null) {

    return parent::createFactory($arg);
  }

  protected function test(dom\element $test, $sContent, $manager, dom\document $doc, fs\file $file) {

    $manager = $this;

    return parent::test($test, $sContent, $manager, $doc, $file);
  }
}

