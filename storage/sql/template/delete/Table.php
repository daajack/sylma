<?php

namespace sylma\storage\sql\template\delete;
use sylma\core, sylma\storage\sql, sylma\parser\languages\common;

class Table extends sql\template\component\Table implements common\argumentable {

  protected $sMode = 'delete';

  public function parseRoot(\sylma\dom\element $el) {

    parent::parseRoot($el);
    $this->insertQuery(false);
  }

  public function asArgument() {
    
    $window = $this->getWindow();
    
    $aArguments = $this->loadDummyArguments();
    $form = $this->buildReflector($aArguments);
    $dummy = $window->createVar($form);
    
    $content = array($dummy->call('asString'));
    
    $aResult[] = $dummy->getInsert();
    $aResult[] = $window->createCondition($dummy->call('validate'), array(
        $this->getQuery(),
        $this->getHandler()->getView()->addToResult($content, false)
    ));
    
    return $this->getWindow()->createGroup($aResult);
  }
}
