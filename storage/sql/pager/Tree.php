<?php

namespace sylma\storage\sql\pager;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\template, sylma\parser\languages\common, sylma\storage\sql;

class Tree extends reflector\component\Foreigner implements reflector\component, template\parser\tree, common\arrayable {

  CONST PREFIX = 'sql';

  protected $var;
  protected $collection;

  protected $offset;
  protected $count;
  protected $bCount = false;
  protected $page;

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->allowForeign(true);
    $this->allowText(true);

    $collection = $this->getParser()->getTree();

    if (!$collection instanceof sql\template\component\Collection) {

      $this->launchException('Tree must be a collection');
    }

    $collection->setPager($this);
    $this->setCollection($collection);
  }

  public function setCollection(sql\template\component\Collection $collection) {

    $this->collection = $collection;
  }

  protected function setParser(reflector\domed $parent) {

    return parent::setParser($parent);
  }

  protected function getCollection() {

    return $this->collection;
  }

  protected function setVar(common\_var $var) {

    $this->var = $var;
  }

  protected function getVar() {

    if (!$this->var) {

      $this->launchException('No variable defined', get_defined_vars());
    }

    return $this->var;
  }

  protected function getOffset() {

    return $this->offset;
  }

  protected function setOffset($offset) {

    $this->offset = $offset;
  }

  protected function setCount($count) {

    $this->count = $count;
  }

  protected function getCount() {

    return $this->count;
  }

  protected function build() {

    $collection = $this->getCollection();

    $var = $this->createObject();
    //$var->insert();
    $this->setVar($var);

    $window = $this->getWindow();
    $this->setOffset($window->parse($this->parseComponentRoot($this->getx('sql:current', true))));
    $this->setCount($window->parse($this->parseComponentRoot($this->getx('sql:count', true))));

    $collection->setLimit($var->call('getOffset'), $this->getCount());
  }

  public function reflectApply($sMode, array $aArguments = array()) {

    if (!$result = $this->getParser()->lookupTemplate($this->getNode()->getName(), $this->getNamespace(), $sMode)) {

      $this->launchException('Cannot render pager without template');
    }

    $result->setTree($this);
    $result->sendArguments($aArguments);

    return $result;
  }

  public function reflectApplyFunction($sName, array $aPath, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {

    $var = $this->getVar();
    //$aResult[] = $this->loadCount();

    switch ($sName) {

      case 'init' : $aResult = $this->initCounter(); break;
      case 'is-multiple' : $aResult[] = $var->call('isMultiple'); break;
      case 'is-first' : $aResult[] = $var->call('isFirst');; break;
      case 'is-last' : $aResult[] = $var->call('isLast'); break;
      case 'pages' : $aResult[] = $this->reflectPages($sMode); break;

      default :

        $this->launchException("Unknow function : '$sName'");
    }

    return $aResult;
  }

  protected function reflectPages($sMode) {

    $window = $this->getWindow();

    $val = $window->createVariable('', 'php-null');
    $last = $this->reflectApplyDefault('last');
    $loop = $window->createLoop($window->callFunction('range', 'php-array', array(1, $last)), $val);

    $this->page = $val;

    if ($result = $this->getParser()->lookupTemplate('page', $this->getNamespace(), $sMode)) {

      $result->setTree($this);

      $loop->setContent($window->parse($result, true));
    }

    return $loop;
  }

  public function reflectApplyDefault($sPath, array $aPath = array(), $sMode = '', $bRead = false, array $aArguments = array()) {

    $var = $this->getVar();
    //$aResult[] = $this->loadCount();

    switch ($sPath) {

      case 'current' : $aResult = $var->call('getPage'); break;
      case 'next' : $aResult = $var->call('getNext'); break;
      case 'last' : $aResult = $var->call('getLast'); break;
      case 'prev' :
      case 'previous' : $aResult = $var->call('getPrevious'); break;
      case 'page' : $aResult = $this->page; break;

      default :

        $this->launchException("Unknown path : '$sPath'");
    }

    return $aResult;
  }

  public function reflectRead() {

    $this->launchException('Not implemented');
  }

  protected function initCounter() {

    return $this->getVar()->call('setCount', array($this->getCollection()->getCount()))->getInsert();
  }

  protected function _loadCount() {

    if (!$this->bCount) {

      $count = $this->getCollection()->getCount();
      $result = $this->getVar()->call('setCount', array($count))->getInsert();

      $this->bCount = true;
    }
    else {

      $result = null;
    }

    return $result;
  }

  public function asToken() {

    return '[obj]' . get_class($this);
  }

  public function asArray() {

    $this->log('Pager : build');
    $this->build();

    $var = $this->getVar();

    $aResult[] = $var->getInsert();
    $aResult[] = $var->call('setPage', array($this->getOffset()))->getInsert();
    $aResult[] = $var->call('setSize', array($this->getCount()))->getInsert();

    return $aResult;
  }
}

