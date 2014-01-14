<?php

namespace sylma\storage\sql\cached;
use sylma\core;

class Pager extends core\module\Exceptionable {

  protected $iCount;
  protected $iPage;
  protected $iSize;

  public function setCount($iCount) {

    $this->iCount = intval($iCount);
  }

  protected function getCount() {

    return $this->iCount;
  }

  public function setPage($iPage) {

    $this->iPage = intval($iPage);
  }

  public function getPage() {

    return $this->iPage;
  }

  public function setSize($iSize) {

    $this->iSize = intval($iSize);
  }

  protected function getSize() {

    return $this->iSize;
  }

  public function getOffset() {

    $test = ($this->getPage() - 1) * $this->getSize();

    return $test;
  }

  public function isMultiple() {

    $this->getCount() > $this->getSize();
  }

  public function isFirst() {

    return $this->getPage() == 1;
  }

  public function isLast() {

    return $this->getLast() == $this->getPage();
  }

  public function getNext() {

    return $this->getPage() + 1;
  }

  public function getLast() {

    return ceil($this->getCount() / $this->getSize());
  }

  public function getPrevious() {

    return $this->getPage() - 1;
  }
}

