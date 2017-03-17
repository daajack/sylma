<?php

namespace sylma\storage\sql\test\alter;
use sylma\core, sylma\storage\sql;

class Handler extends sql\alter\Handler {

  protected static $sArgumentClass = 'sylma\core\argument\Filed';
  
  public function __construct(core\argument $args, core\argument $post, core\argument &$contexts) {
    
    parent::__construct($args, $post, $contexts);
    
    $this->setDirectory(__FILE__);
    
    $this->initConfig();
  }

  public function initConfig() {

    $config = $this->createArgument('/#sylma/storage/sql/test/config.yml');
    
    \Sylma::getSettings()->merge($config);
  }
}

