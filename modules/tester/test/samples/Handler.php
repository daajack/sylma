<?php

namespace sylma\modules\tester\test\samples;
use sylma\core, sylma\modules\tester, sylma\dom;

class Handler extends tester\Parser implements core\argumentable {

  protected $sTitle = 'Test';

  public function __construct() {

    $this->setDirectory(__file__);

    parent::__construct();
  }

  protected function saveProfile() {

    if ($this->profiler) {

      $this->profiler->stop(true);
    }
  }

  public function saveTestProfile() {

    return parent::saveProfile();
  }
}

