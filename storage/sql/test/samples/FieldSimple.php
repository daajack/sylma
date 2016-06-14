<?php

namespace sylma\storage\sql\test\samples;
use sylma\core, sylma\storage\sql;

class FieldSimple extends sql\template\view\Field
{
  protected function reflectSelf($bHTML = false) {

    $result = parent::reflectSelf($bHTML);

    return array($result, $this->getWindow()->argToInstance(' hello'));
  }
}
