<?php

namespace sylma\storage\sql\template\view;
use sylma\core, sylma\storage\sql, sylma\parser\languages\common, sylma\schema\parser;

class Reference extends sql\template\component\Reference {

  protected $collection;

  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {

    switch ($sName) {

      case 'collection' :

        $result = $this->getParser()->parsePathToken($this->getCollection(), $aPath, $sMode, $aArguments);
        break;

      default :

        $result = parent::reflectApplyFunction($sName, $aPath, $sMode, $bRead, $sArguments, $aArguments);
    }

    return $result;
  }

  protected function getCollection() {

    if (!$this->collection) {

      $this->collection = $this->loadCollection();
    }

    return $this->collection;
  }

  protected function loadCollection() {

    $table = $this->getElementRef();
    $element = $this->getForeign();

    $result = $this->loadSimpleComponent('component/collection');
    $result->setTable($table);

    if ($element->getMaxOccurs(true)) {

      $this->launchException('Not implemented');
    }
    else {

      $table->getQuery()->setWhere($element, '=', $this->getParent()->getElement('id')->reflectRead());
    }

    return $result;
  }

  protected function reflectFunctionRef(array $aPath, $sMode, array $aArguments = array()) {

    $collection = $this->getCollection();

    if ($aPath) {

      $result = $this->getParser()->parsePathToken($collection, $aPath, $sMode, false, $aArguments);
    }
    else {

      $result = $collection->reflectApplyAll($sMode, $aArguments);
    }

    return $result;
  }
}

