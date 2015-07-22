<?php

namespace sylma\storage\sql\alter\component;
use sylma\core, sylma\storage\sql;

class Field extends sql\schema\component\Field implements sql\alter\alterable {

  public function asUpdate() {

    return $this->getParent()->fieldAsUpdate($this, $this->getPrevious());
  }

  public function asCreate() {

    $sResult = $this->asString();

    if ($this->isID()) {

      $sResult .= ", PRIMARY KEY (`{$this->getName()}`)";
    }
    else if ($this->isUnique()) {

      $sName = $this->getName();

      $sResult .= ", UNIQUE KEY `$sName` (`$sName`)";
    }

    return $sResult;
  }

  protected function isID() {

    $id = $this->getParser()->getType('id', $this->getNamespace('sql'));
    return $this->getType()->doExtends($id);
  }

  protected function isUnique() {

    return $this->readx('@unique');
  }

  protected function getAlterDefault() {

    if (!$sDefault = $this->readx('@alter-default')) {

      $sDefault = $this->getDefault();
    }

    return $sDefault;
  }

  protected function typeAsString() {

    $sDefault = $this->getAlterDefault();
    $sContent = $sDefault ? ' NULL' . ($this->getDefault() ? ' DEFAULT ' . $sDefault : '') : ' NOT NULL';

    return $this->asType() . ($this->isID() ? ' AUTO_INCREMENT' : $sContent);
  }

  public function asType() {

    $sDefault = $this->getAlterDefault();

    $type = $this->getType();

    if ($type->doExtends($this->getParser()->getType('datetime', $this->getNamespace('sql')))) {

      if ($sDefault) {

        $sType = 'DATETIME';
      }
      else {

        $sType = 'TIMESTAMP';
      }
    }
    else {

      $sType = $type->asString();
    }


    return $sType;
  }

  public function asString() {

    return "`{$this->getName()}` " . $this->typeAsString(); // " COMMENT '{$this->getTitle()}'";
  }
}

