<?php

namespace sylma\storage\sql\schema\component;
use sylma\core, sylma\dom, sylma\schema, sylma\storage\sql;

class Table extends Element implements sql\schema\table {

  protected $sAlias = '';
  protected $bSub = false;

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->setName($el->readx('@name'));
    $this->loadNamespace();

    $handler = $this->getHandler();
    $type = $this->loadSimpleComponent('component/complexType', $handler);
    $particle = $this->loadComponent('component/particle', $el, $handler);

    $type->setParticle($particle);
    $type->prepare();

    $this->setType($type);
  }

  public function getElement($name, $ns = null, $debug = true) {

    if (is_null($ns)) {

      $ns = $this->getNamespace();
    }

    if (!$this->isComplex()) {

      $this->launchException("Cannot get sub element of simple typed element $ns:$name", get_defined_vars());
    }

    if ($result = $this->getType()->getElement($name, $ns, $debug)) {

      $this->loadChild($result);
    }
    else {

      if ($debug) $this->launchException("Cannot find element $ns:$name", get_defined_vars());
      $result = null;
    }

    return $result;
  }

  public function getElements() {

    if (!$this->isComplex()) {

      $this->launchException('Cannot get sub elements of simple type');
    }

    $result = $this->getType()->getElements();

    foreach ($result as $child) {

      $this->loadChild($child);
    }

    return $result;
  }

  protected function loadChild(schema\parser\element $child) {

    $child->setParent($this);
  }

  public function getConnectionAlias() {

    return $this->readx('@connection');
  }

  protected function getCharset() {

    return $this->readx('@charset');
  }

  public function isSub($bVal = null) {

    if (is_bool($bVal)) $this->bSub = $bVal;

    return $this->bSub;
  }

  public function setAlias($sValue) {

    $this->useAlias((bool) $sValue);

    $this->sAlias = $sValue;
  }

  public function getAlias($sMode = '') {

    return $this->sAlias;
  }

  public function asAlias() {

    return "`" . $this->getName() . "`" . ($this->useAlias() ? ' AS `' . $this->getAlias() . '`' : '');
  }

  public function asString() {

    return "`" . ($this->useAlias() ? $this->getAlias() : $this->getName()) . "`";
  }
}

