<?php

namespace sylma\storage\sql\template\view;
use sylma\core, sylma\storage\sql, sylma\parser\languages\common, sylma\schema\parser;

class Reference extends sql\template\component\Reference {

  protected $collection;

  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {

    switch ($sName) {

      case 'collection' : $result = $this->getParser()->parsePathToken($this->getCollection(), $aPath, $sMode, $aArguments); break;
      case 'extract' : $result = $this->reflectFunctionExtract($aPath, $sMode, $aArguments, $bRead); break;
      case 'join' : $result = $this->reflectFunctionJoin($aPath, $sMode, $aArguments); break;

      default :

        $result = parent::reflectApplyFunction($sName, $aPath, $sMode, $bRead, $sArguments, $aArguments);
    }

    return $result;
  }

  protected function reflectFunctionJoin(array $aPath, $sMode, array $aArguments = array()) {

    $foreign = $this->getForeign();

    if ($foreign->getMaxOccurs(true)) {

      list($junctionTable, $junctionCurrent, $junctionTarget) = $foreign->loadJunction();
      
      $targetTable = $foreign->getElementRef();
      $parent = $foreign->getParent();
      $currentTable = $this->getParent();
      $query = $currentTable->getQuery();
      
      $parent->setSource($currentTable->getSource());
      $parent->setQuery($query);
      $parent->insertQuery(false);
      
      $query->addJoin($junctionTable, $junctionTarget, $currentTable->getElement('id'));
      $query->addJoin($parent, $parent->getElement('id'), $junctionCurrent);
      
      $result = $this->getParser()->parsePathToken($parent, $aPath, $sMode, false, $aArguments);
    }
    else {

      $targetTable = $this->getElementRef();
      $currentTable = $this->getParent();

      $query = $currentTable->getQuery();

      $targetTable->setSource($currentTable->getSource());
      $targetTable->setQuery($query);
      $targetTable->insertQuery(false);

      $query->addJoin($targetTable, $foreign, $currentTable->getElement('id'));
      
      $result = $this->getParser()->parsePathToken($targetTable, $aPath, $sMode, false, $aArguments);
    }

    return $result;
  }

  protected function getCollection($bReset = false) {

    if ($bReset || !$this->collection) {

      $this->collection = $this->loadCollection();
    }

    return $this->collection;
  }

  protected function loadCollection() {

    $table = $this->getElementRef();
    $element = $this->getForeign();

    $result = $this->loadSimpleComponent('component/collection');
    $result->setTable($table, true);

    if ($element->getMaxOccurs(true)) {

      $this->launchException('Not implemented');
    }
    else {

      $key = $this->getParent()->getElement($element->getKey());
      $val = $key->reflectRead();
      $bString = $key->getType()->doExtends($this->getParser()->getType('string', $this->getNamespace('sql')));

      $table->getQuery()->setWhere($element, '=', $bString ? $this->reflectEscape($val) : $val);
    }

    return $result;
  }

  protected function reflectFunctionRef(array $aPath, $sMode, array $aArguments = array()) {

    $collection = $this->getCollection(true);

    if ($aPath) {

      $result = $this->getHandler()->parsePathToken($collection, $aPath, $sMode, false, $aArguments);
    }
    else {

      $result = $collection->reflectApplyAll($sMode, $aArguments);
    }

    return $result;
  }

  protected function reflectFunctionExtract(array $aPath, $sMode, array $aArguments = array(), $bRead = false) {

    $collection = $this->getCollection(true);
    $collection->getQuery()->setMethod('extract');

    //$aContent[] = $collection->reflectApplyFunction('init', array(), '');
    $aContent[] = $this->getWindow()->parse($this->getHandler()->parsePathToken($collection->getTable(), $aPath, $sMode, $bRead, $aArguments));


    return array(
      $collection->getQuery(),
      $collection->getQuery()->getVar()->call('asArray'),
      $this->getWindow()->createGroup($aContent),
    );
  }

}

