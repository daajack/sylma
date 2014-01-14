<?php

namespace sylma\view\parser\crud;
use sylma\core, sylma\dom;

class View extends Pathed implements core\tokenable {

  protected $route;
  protected $bMain = true;

  public function parseRoot(dom\element $el) {

    $this->setNode($el, true);
    $this->loadName();
  }

  public function getRoute() {

    return $this->route;
  }

  public function setRoute(Route $route) {

    $this->route = $route;
  }

  public function isMain($bValue = null) {

    if (is_bool($bValue)) $this->bMain = $bValue;

    return $this->bMain;
  }

  public function getAlias() {

    $sPrefix = $this->getRoute() ? $this->getRoute()->getAlias() : '';
    $sContent = $this->getName() || $sPrefix ? $this->getName() : self::DEFAULT_FILE;

    return $sPrefix . ($sPrefix && $sContent ? '_' : '') . $sContent;
  }

  public function merge($path = null) {

    if ($path) $this->getNode()->shift($path->getNode()->queryx('* | @*'));
  }

  public function getPath(array $aPath) {

    if ($aPath) {

      $this->launchException('Cannot get subpath of view');
    }

    return $this;
  }

  public function asPath() {

    $aResult[] = $this->getSourceFile('', false)->asPath();
    $aResult[] = $this->getRoute() && (!$this->isMain() || $this->getRoute()->getName())? $this->getRoute()->getAlias() : '';
    $aResult[] = $this->getName();

    return  implode('/', array_filter($aResult, 'strlen'));
  }

  public function asDocument() {

    //$this->getNode()->add($this->getParser()->getResource());
    $this->getNode()->shift($this->loadGroups());
    $this->getNode()->shift($this->getRoute());
    $this->getNode()->shift($this->getParser()->getGlobal());

    foreach ($this->getNode()->queryx() as $node) if ($node->getType() == $node::TEXT) $node->remove();

    return $this->getNode()->getHandler();
  }

  public function asToken() {

    return '@view : ' . $this->getAlias();
  }
}

