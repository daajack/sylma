<?php

namespace sylma\storage\sql\alter\component;
use sylma\core, sylma\schema\xsd as schema;

class Simple extends schema\component\SimpleType {

  public function asString() {

    $sql = $this->getHandler()->getNamespace('sql');
    $xs = $this->getHandler()->getNamespace('xs');

    if ($this->doExtends($this->getParser()->getType('text', $sql))) {

      $sValue = 'MEDIUMTEXT';
    }
    else if ($this->doExtends($this->getParser()->getType('string', $xs))) {

      if ($define = $this->getDefine(false)) {

        $iSize = $this->getDefine()->getRule('maxLength');
      }
      else {

        $this->launchException('No @maxLength defined');
      }

      if ($iSize > 255) {

        $sValue = 'TEXT';
      }
      else {

        $sValue = "VARCHAR({$iSize})";
      }
    }
    else if ($this->doExtends ($this->getParser ()->getType('id', $sql))) {

      $sValue = 'BIGINT UNSIGNED';
    }
    else if ($this->doExtends($this->getParser()->getType('integer', $xs))) {

      $iSize = $this->getDefine()->getRule('totalDigits');

      if (!$iSize) {

        $this->launchException('No @totalDigits defined');
      }

      $sValue = "INT({$iSize})";
    }
    else if ($this->doExtends($this->getParser()->getType('boolean', $xs))) {

      $sValue = "TINYINT(1)";
    }
    else if ($this->doExtends($this->getParser()->getType('float', $xs))) {

      $sValue = "FLOAT";
    }
    else {

      $iSize = 11;
      $sValue = "INT({$iSize})";
    }

    return $sValue;
  }
}

