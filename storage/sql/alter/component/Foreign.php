<?php

namespace sylma\storage\sql\alter\component;
use sylma\core, sylma\storage\sql, sylma\parser\languages\common, sylma\schema\parser;

class Foreign extends sql\schema\component\Foreign implements sql\alter\alterable {
  
  public function asUpdate() {

    return $this->getParent()->fieldAsUpdate($this, $this->getPrevious());
  }

  public function asCreate() {

    $ref = $this->getElementRef();

    if (!$this->getMaxOccurs(true)) {

      if ($file = $this->getElementRefFile()) {

        $this->getParent()->buildSchema($file);
      }

      $sKey = $this->getKey();

      $sResult = $this->asString() . ",CONSTRAINT FOREIGN KEY (`{$this->getName()}`) REFERENCES `{$ref->getName()}` ($sKey)";

      if ($this->readx('@cascade')) {

        $sResult.= ' ON DELETE CASCADE ON UPDATE RESTRICT';
      }
    }
    else {

      $sResult = '';
    }

    return $sResult;
  }

  public function asJunction() {

    if ($this->getMaxOccurs(true)) {

      $this->loadJunction();
    }
  }

  protected function typeAsString() {

    if (!$sDefault = $this->readx('@alter-default')) {

      $sDefault = $this->getDefault();
    }

    $sContent = $sDefault ? ' NULL' . ($this->getDefault() ? ' DEFAULT ' . $this->getDefault() : '') : ' NOT NULL';

    $ref = $this->getElementRef()->getElement($this->getKey());
//if (!$ref instanceof Field) dsp($ref);
    //$sType = $ref->getType()->asString();
    //$sType = $ref instanceof Field ? $ref->asType() : $ref->getType()->asString();
    $sType = $this->refAsString($ref);

    return $sType . $sContent;
  }
  
  protected function refAsString(Field $ref) {
    
    return $ref->asType();
  }

  public function asString() {

    return "`{$this->getName()}` " . $this->typeAsString(); // " COMMENT '{$this->getTitle()}'";
  }
}

