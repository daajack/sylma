<?php

namespace sylma\storage\sql\test\alter;
use sylma\core, sylma\storage\sql;

class Handler extends sql\alter\Handler {

  protected static $sArgumentClass = 'sylma\core\argument\Filed';
  
  public function __construct(core\argument $args, core\argument $post, core\argument &$contexts) {
    
    parent::__construct($args, $post, $contexts);
    
    $this->setDirectory(__FILE__);
    
    $sylma = $this->createArgument('/#sylma/core/sylma.yml');
    $test = $this->createArgument('/#sylma/core/test.yml');
    $config = $this->createArgument('/#sylma/storage/sql/test/config.yml');
    
    $sylma->merge($test);
    $sylma->merge($config);
    
    \Sylma::setSettings($sylma);
    \Sylma::setManager('locale', null);
  }
}

