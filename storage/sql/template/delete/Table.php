<?php

namespace sylma\storage\sql\template\delete;
use sylma\core, sylma\storage\sql, sylma\parser\languages\common;

class Table extends sql\template\component\TableForm implements common\argumentable {

  protected $sMode = 'delete';

  protected function loadQuery() {

    if ($dummy = $this->getDummy(false)) {

      $this->getQuery()->setDummy($dummy);
    }

    $view = $this->getParser()->getView();

    $aResult[] = $this->getQuery();

    return $aResult;
  }
}
