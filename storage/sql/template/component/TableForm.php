<?php

namespace sylma\storage\sql\template\component;
use sylma\core, sylma\dom, sylma\storage\sql, sylma\schema, sylma\template;

class TableForm extends Table
{
  protected $bInsertQuery = false;
  protected $aContent = array();
  protected $aValidates = array();

  protected $parentDummy;
  
  public function parseRoot(dom\element $el) {

    parent::parseRoot($el);

    $this->setSource($this->getWindow()->getVariable('post'));
  }
  
  public function init($key = null, $parent = null) {

    if ($key) {

      $this->setKey($key);
    }

    $this->parentDummy = $parent;

    //$this->loadDummy($key, $parent);
  }
  
  protected function loadDummy() {

    $key = $this->getKey();
    $parent = $this->parentDummy;

    $aArguments = $this->loadDummyArguments();
    $window = $this->getWindow();

    if ($this->isSub()) {

      $form = $this->buildReflector($aArguments, 'sub');
    }
    else {

      $form = $this->buildReflector($aArguments);
    }

    $var = $window->createVar($form);

    $this->setDummy($var);
    $this->addContent($var->getInsert());

    if ($this->isSub() && $this->getParent(false)) {

      $id = $this->getElement('id', null, false);
      $this->addContent($var->call('setSub', array($this->getParent()->getAlias(), $key, $parent, $id ? $id->getName() : null)));
    }
  }
  
  protected function loadTriggers() {

    $window = $this->getWindow();

    $aContent[] = $this->loadQuery();

    if ($aTriggers = $this->getTriggers()) {

      $aContent[] = $window->createGroup($aTriggers);
    }

    return $aContent;
  }
  
  protected function addContent($mVal) {

    $this->aContent[] = $mVal;
  }
  
  public function addTrigger(array $aContent) {

    $this->aTriggers[] = $aContent;
  }
  
  protected function loadQuery() {

    if ($dummy = $this->getDummy(false)) {

      $this->getQuery()->setDummy($dummy);
    }

    $view = $this->getParser()->getView();

    $aResult[] = $this->getQuery();

    if ($this->isSub()) {

      //$result = $this->getQuery();
    }
    else {

      $aResult[] = $view->addToResult(array($this->getQuery()->getVar()), false, true);
    }

    return $aResult;
  }

  protected function addValidate($mVal) {

    if ($this->aValidates) {

      $this->aValidates[] = $this->getWindow()->createOperator('&&');
    }

    $this->aValidates[] = $mVal;
  }

  protected function callValidate() {

    $this->addValidate($this->getDummy(false)->call('validate'));

    return array_reverse($this->aValidates);
  }

  protected function getValidation() {

    $result = $this->getWindow()->createGroup($this->getContent());
    $this->aContent = array();

    return $result;
  }

  protected function getExecution() {

    $aResult = $this->aContent;
    $aResult[] = $this->loadTriggers();

    return $aResult;
  }

  protected function getContent() {

    $aResult[] = $this->aContent;

    return $aResult;
  }

  public function asArgument() {

    $window = $this->getWindow();

    // must be called first to fill content
    $aValidate = $window->createCondition($this->callValidate(), $this->loadTriggers());

    return $this->getWindow()->createGroup(array($this->getContent(), $aValidate));
  }
}

