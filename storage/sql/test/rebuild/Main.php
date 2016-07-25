<?php

namespace sylma\storage\sql\test\rebuild;
use sylma\core, sylma\modules;

class Main extends modules\rebuild\Main {

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
//dsp($sylma->get('locale'));
  }
}

