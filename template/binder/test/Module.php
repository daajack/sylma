<?php

namespace sylma\template\binder\test;
use sylma\core, sylma\dom, sylma\storage\fs, sylma\modules\tester;

class Module extends tester\Parser {

  const TEST_ALIAS = 'testjs';

  public function __construct() {

    $this->setDirectory(__file__);
    $this->setNamespace(self::NS, 'self');

    $this->setSettings(\Sylma::get('tester'));
    $this->initProfile();
  }
}

