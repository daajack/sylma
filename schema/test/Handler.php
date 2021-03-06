<?php

namespace sylma\schema\test;
use sylma\core, sylma\dom, sylma\modules\tester, sylma\storage\fs;

class Handler extends tester\Parser implements core\argumentable {

  protected $sTitle = 'Grouped';

  public function __construct() {

    $this->setDirectory(__file__);

    parent::__construct();
  }

  protected function buildResult(dom\element $test, $manager, fs\file $file, array $aArguments) {

    $builder = $manager->loadBuilder($file, $this->getExportDirectory());

    return array(
      'content' => $builder->getSchema(),
      'result' => true,
    );
  }
}

