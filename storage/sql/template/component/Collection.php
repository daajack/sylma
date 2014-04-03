<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\storage\sql;

class Collection extends Rooted implements sql\template\pathable {

  protected $table;
  protected $pager;
  protected $counter;
  protected $key;

  protected $dummy;

  /**
   * Before loop
   */
  protected $aStart = array();

  public function getElement($sName, $sNamespace = null) {

    return $this->getTable()->getElement($sName, $sNamespace);
  }

  public function setTable(Table $table) {

    $sNamespace = $this->getHandler()->getNamespace('sql');

    $this->setType($this->getHandler()->getType('collection', $sNamespace));
    $this->setNamespace($sNamespace);
    //$this->setNamespace($table->getNamespace(), 'element', false);
    $this->setName('[collection]');
    $this->setQuery($table->getQuery());

    $this->table = $table;
    //$this->loadNamespace($table->getNamespace());
  }

  public function getTable() {

    return $this->table;
  }

  protected function preBuild() {

    $window = $this->getWindow();

    $source = $window->createVariable('', '\sylma\core\argument', false);
    $key = $window->createVariable('', 'php-string', false);

    $this->setSource($source);
    $this->setKey($key);

    $this->getQuery()->isMultiple(true);
    $this->getTable()->insertQuery(false);
  }

  protected function postBuild($result) {

    $window = $this->getWindow();

    $loop = $window->createLoop($this->getQuery()->getVar(), $this->getSource(), $this->getKey());
    $loop->setContent($window->parseArrayables(array($result)));

    $aResult[] = $loop;

    return $aResult;
  }

  public function reflectRead(array $aPath = array(), array $aArguments = array()) {

    if (!$aPath) {

      $this->launchException('Cannot read collection');
    }

    return $this->getParse()->parsePathToken($this, $aPath, $aArguments);
  }

  public function reflectApplyDefault($sPath, array $aPath, $sMode) {

    $this->launchException('Cannot reflect collection default path');
  }

  public function reflectApply($sMode = '', array $aArguments = array()) {

    if ($result = $this->lookupTemplate($sMode)) {

      $result->setTree($this);
      $result->sendArguments($aArguments);

      $this->preBuild();
    }
    else {

      if (!$sMode) {

        $this->launchException('Cannot apply collection without template', get_defined_vars());
      }

      $result = null;
    }

    $aResult = array();

    if (!$sMode) {

      $aResult[] = $this->getCounter();
    }

    $aResult[] = $result;

    return $aResult;
  }

  public function reflectApplyAll($sMode, array $aArguments = array()) {

    $this->preBuild();

    $this->getTable()->setSource($this->getSource());
    $this->getTable()->setKey($this->getKey());
    $this->getTable()->setQuery($this->getQuery());
    $this->getTable()->setCollection($this);

    $content = $this->getTable()->reflectApply($sMode, $aArguments);

    if ($this->insertQuery()) {

      $aResult[] = $this->getQuery();
    }

    $collection = $this;
    $window = $this->getWindow();

    $aResult[] = $window->createCaller(function() use ($collection, $window) {

      return $window->createGroup($collection->getStart());
    });

    $aResult[] = $this->postBuild($content);

    $this->insertQuery(false);

    return $aResult;
  }

  /**
   * @usedby self::reflectApplyAll() dummy callback
   * @return array
   */
  public function getStart() {

    return $this->aStart;
  }

  protected function getDummy() {

    if (!$this->dummy) {

      $result = $this->getWindow()->createVar($this->buildReflector(array(), 'dummy'));
      $this->aStart[] = $result->getInsert();

      $this->dummy = $result;
    }

    return $this->dummy;
  }

  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {

    switch ($sName) {

      case 'static' :

        $result = $aPath ? $this->getHandler()->parsePathToken($this->getTable(), $aPath, $sMode, true, $aArguments) : $this->getTable()->reflectApply($sMode, $aArguments, true);
        break;

      case 'count' :

        $result = $this->getCount();
        break;

      case 'pager' :

        $result = $this->getPager()->reflectApply($sMode);
        break;

      case 'dummy' :

        $result = $this->reflectDummy($aPath, $aArguments);
        break;

      case 'distinct' :

        $aFunctionArguments = $this->getHandler()->getPather()->parseArguments($sArguments, $sMode, $bRead, false);
        $result = $this->getDistinct($aFunctionArguments, $aPath, $sMode, $aArguments);
        break;

      case 'has-children' :

        $this->preBuild();

        $result = array();

        if ($this->insertQuery()) $result[] = $this->getQuery();
        $this->insertQuery(false);

        $result[] = $this->getQuery()->getVar()->call('hasChildren');

        break;

      default :

        $result = $this->getHandler()->getView()->getCurrentTemplate()->reflectApplyFunction($sName, $sArguments);
    }

    return $result;
  }

  protected function reflectDummy(array $aPath, array $aArguments = array()) {

    $dummy = $this->getDummy();

    if ($aPath) {

      $sFunction = current($aPath);
      $result = $dummy->call($sFunction, $aArguments);
    }
    else {

      $result = $dummy;
    }

    return $result;
  }

  protected function getDistinct(array $aFunctionArguments, array $aPath, $sMode, array $aArguments = array()) {

    $el = array_pop($aFunctionArguments);
    $table = $this->loadDistinctElement($el);

    $query = clone $this->getQuery();
    $this->getQuery()->addClone($query);

    $query->clearColumns();
    $query->clearLimit();
    $query->clearOrder();
    //$query->clearJoins();

    $query->setMethod('extract');

    $query->setColumn(array('DISTINCT ', $el->asString()));

    $collection = $this->loadSimpleComponent('component/collection');
    $collection->setTable($table);
    $collection->setQuery($table->getQuery());

    $window = $this->getWindow();

    $sIDS = array('(', $window->callFunction('implode', 'php-string', array(',', $query->getVar()->call('asArray'))), ')');

    $collection->getQuery()->setWhere($table->getElement('id'), 'IN', $sIDS);

    $aResult[] = $query;
    $aResult[] = $aPath ? $this->getHandler()->parsePath($collection, implode('/', $aPath), $sMode, $aArguments) : $collection->reflectApply($sMode, $aArguments);

    return $aResult;
  }

  protected function loadDistinctElement(Foreign $el) {

    return $el->getElementRef();
  }

  public function setPager(sql\template\Pager $pager) {

    $pager->setCollection($this);
    $pager->setParser($this->getHandler()->getView());

    $this->pager = $pager;
  }

  protected function getPager() {

    if (!$this->pager) {

      $this->launchException('No pager defined');
    }

    return $this->pager;
  }

  public function setLimit($offset, $count) {

    $query = $this->getQuery();

    $query->setOffset($offset);
    $query->setCount($count);
  }

  public function getCount() {

    return $this->getCounter()->getVar();
  }

  protected function loadCounter() {

    $query = clone $this->getQuery();
    $this->getQuery()->addClone($query);

    $result = $this->loadSimpleComponent('counter');
    $result->setQuery($query);

    return $result;
  }

  protected function getCounter() {

    if (!$this->counter) {

      $this->counter = $this->loadCounter();
    }

    return $this->counter;
  }
}

