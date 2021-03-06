<?php

namespace sylma\storage\sql\test;
use sylma\core, sylma\modules\tester;

class Handler extends tester\Formed implements core\argumentable {

  protected $sTitle = 'SQL';
  protected static $sArgumentClass = 'sylma\core\argument\Filed';

  public function __construct() {

    $this->setDirectory(__file__);

    parent::__construct();
  }

  protected function show() {

    $this->resetDB();
    $this->resetDB('database.xml');
  }

  public function getSchema($sPath) {

    $file = $this->getFile($sPath);
    $builder = $this->getManager(self::PARSER_MANAGER)->loadBuilder($file, $this->getExportDirectory());

    return $builder->getSchema();
  }
  
  public function initConfig() {
    
    $config = $this->createArgument('/#sylma/storage/sql/test/config.yml');
    
    \Sylma::getSettings()->merge($config);
  }
}

