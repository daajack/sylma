<?php

namespace sylma\storage\sql\schema\component;
use sylma\core, sylma\dom, sylma\storage\fs, sylma\storage\sql;

class Foreign extends Element implements sql\schema\foreign {

  const PREFIX = 'sql';
  const JUNCTION_MODE = 'view';

  protected $elementRef;
  protected $aJunction = array();

  protected $sKey = 'id';

  public function parseRoot(dom\element $el) {

    $this->setNode($el, false);

    $this->loadName();
    $this->loadType();

    $this->reflectOccurs($el);
    $this->loadOptional();
    $this->loadKey();
  }

  protected function loadName() {

    $this->setName($this->readx('@name'));
  }

  protected function loadType() {

    $this->setType($this->getParser()->getType('foreign', $this->getParser()->getNamespace(self::PREFIX)));
  }

  protected function loadKey() {

    if ($sKey = $this->readx('@key', false)) {

      $this->setKey($sKey);
    }

    return $this->getKey();
  }

  protected function setKey($sKey) {

    $this->sKey = $sKey;
  }

  protected function getKey() {

    return $this->sKey;
  }

  protected function loadElementRef(fs\file $file = null) {

    if ($file) {

      $result = $this->getHandler()->addSchema($file, true);
    }
    else {

      list($sNamespace, $sName) = $this->parseName($this->readx('@table', true));
      $result = $this->getHandler()->getElement($sName, $sNamespace, false);
    }

    return $result;
  }

  protected function getElementRefFile() {

    if ($sImport = $this->readx('@import')) {

      try {

        $result = $this->getSourceFile($sImport);

      } catch (core\exception $e) {

        $e->addPath($this->asToken());
        throw $e;
      }

    }
    else {

      $result = null;
    }

    return $result;
  }

  protected function importElementRef() {

    if (!$result = $this->loadElementRef()) {

      $file = $this->getElementRefFile();

      if (!$result = $this->loadElementRef($file)) {

        list($sNamespace, $sName) = $this->parseName($this->readx('@table', true));
        $this->launchException('Cannot find reference : ' . $sNamespace . ':' . $sName, get_defined_vars());
      }
    }

    return $result;
  }

  /**
   *
   * @return sql\schema\component\Table
   */
  public function getElementRef() {

    if (!$this->elementRef) {

      if (is_null($this->elementRef)) {

        $this->setElementRef($this->importElementRef());
      }
      else {

        $this->throwException('No ref element defined');
      }
    }

    $result = $this->elementRef;

    if ($result) {

      $result->setParent($this);
    }

    return $result;
  }

  public function setElementRef(Table $element) {

    $this->elementRef = $element;
  }

  protected function reflectOccurs(dom\element $el) {

    if (!$sOccurs = $el->readx('@occurs', array(), false)) {

      $sOccurs = '1..1';
    }

    list($iMin, $iMax) = explode('..', $sOccurs);
    $this->setOccurs($iMin, $iMax);
  }

  /**
   * @return array A an array containing element of the junction table : (table, current foreign, target foreign)
   */
  protected function loadJunction() {

    if (!$this->aJunction) {

      $this->aJunction = $this->buildJunction();
    }

    return $this->aJunction;
  }

  protected function buildJunction() {

    $sName = $this->readx('@junction', true);

    $ref = $this->getElementRef();
    $parent = $this->getParent();

    $sParentName = $parent->getName();
    $sRefName = $ref->getName();

    $sCurrent = 'id_' . $sParentName;
    $sTarget = 'id_' . $sRefName;
    $sConnection = $parent->getConnectionAlias();

    if ($sParentName === $sRefName) {

      $sCurrent .= '_source';
      $sTarget .= '_target';
    }

    $doc = $this->createArgument(array(
      'schema' => array(
        '@targetNamespace' => $this->getNamespace(),
        'table' => array(
          '@name' => $sName,
          '@connection' => $sConnection,
          '#foreign' => array(
            array(
              '@name' => $sCurrent,
              '@occurs' => '0..1',
              '@table' => 't1:' . $parent->getName(),
              '@import' => (string) $this->getSourceFile(),
            ),
            array(
              '@name' => $sTarget,
              '@occurs' => '0..1',
              '@table' => 't2:' . $ref->getName(),
              '@import' => (string) $this->getSourceFile($this->readx('@import')),
            ),
          ),
        ),
      ),
    ), $this->getNamespace('sql'))->asDOM();

    $doc->registerNamespaces(array(
      't1' => $this->getNamespace(),
      't2' => $ref->getNamespace(),
    ));

    $sql = $this->getManager(self::DB_MANAGER)->getConnection($sConnection);

    if (!$sql->read("show tables like '$sName'", false)) {

      $handler = new sql\alter\Handler;
      $handler->setDocument($doc);

      $handler->asString();
    }
    
    //$this->getParser()->changeMode(static::JUNCTION_MODE);
    
    $table = $this->getHandler()->addSchemaDocument($doc);

    if (!$table instanceof Table) {

      //$table = current($this->getHandler()->getElements());
      $this->launchException('Table element not found', get_defined_vars());
    }

    $table->setParent($this);
    $table->isSub(true);

    $current = $table->getElement($sCurrent);
    $target = $table->getElement($sTarget);

    //$this->getParser()->resetMode();

    return array($table, $current, $target);
  }

  public function filterQuery(sql\query\parser\Basic $query) {

    //$query->setWhere($this, '=', $this->getParent()->getElementArgument('id'));
  }
}

