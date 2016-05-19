<?php

namespace sylma\schema\parser\component;
use sylma\core, sylma\dom, sylma\schema, sylma\parser\reflector, sylma\parser\languages\common;

class Element extends Basic implements schema\parser\element, core\tokenable {

  protected $type;
  protected $parent;
  protected $particle;

  protected $iPosition = 0;

  protected $iMinOccurs;
  protected $iMaxOccurs;

  protected $bOptional = false;

  protected $sReflector = '';
  protected $reflector;
  protected $typeName;

  /**
   * Token is cached cause it's used many times as id in template lookup
   * @var string
   */
  protected $sToken;

  public function setParent(schema\parser\element $parent) {

    $this->parent = $parent;
  }

  /**
   * @param boolean $bDebug
   * @return \sylma\schema\parser\element
   */
  public function getParent($bDebug = true) {

    if (!$this->parent && $bDebug) {

      $this->throwException('No parent');
    }

    return $this->parent;
  }

  public function parseName($sName) {

    return $this->getParser()->parseName($sName, $this, $this->getNode(false));
  }

  /**
   * @TODO Bad mix with module\Namespaced
   */
  public function getNamespace($sPrefix = 'element') {

    return parent::getNamespace($sPrefix);
  }

  public function setPosition($iPosition) {

    $this->iPosition = $iPosition;
  }

  public function getPosition() {

    return $this->iPosition;
  }

  public function setParticle(schema\parser\particle $particle) {

    $this->particle = $particle;
  }

  protected function getParticle() {

    if (!$this->particle) {

      $this->launchException('No particle defined');
    }

    return $this->particle;
  }

  protected function getPrevious() {

    $result = null;

    if ($this->getPosition()) {

      $result = $this->getParticle()->getElementFromIndex($this->getPosition() - 1);
    }

    return $result;
  }

  protected function getNext() {

    return $this->getParent()->getElementFromIndex($this->getPosition() + 1);
  }

  protected function parseTypeString() {

    if ($this->typeName) {

      $ns = $this->getHandler()->parseName($this->typeName);
      $type = $this->getHandler()->getType($ns[0], $ns[1]);

      $this->setType($type);
    }
  }

  /**
   * @param bool $debug
   * @return \schema\parser\type
   */
  public function getType($debug = true) {

    if (!$this->type && $this->typeName) {

      $name = $this->typeName;
      $this->type = $this->getHandler()->getType($name[1], $name[0]);
    }

    if (!$this->type && $debug) {

      $this->throwException('No type defined');
    }

    return $this->type;
  }

  public function setType(schema\parser\type $type) {

    $this->type = $type;
  }

  public function isComplex() {

    return $this->getType()->isComplex();
  }

  protected function isRequired() {

    return !$this->isOptional();
  }

  protected function isOptional($bValue = null) {

    if (is_bool($bValue)) $this->bOptional = $bValue;

    return $this->bOptional;
  }

  protected function setOccurs($iMin, $iMax) {

    $this->iMinOccurs = intval($iMin);
    $this->iMaxOccurs = intval($iMax);
  }

  protected function getMinOccurs() {

    return $this->iMinOccurs;
  }

  protected function getMaxOccurs($bBool) {

    return $bBool ? $this->iMaxOccurs == 'n' || $this->iMaxOccurs > 1 : $this->iMaxOccurs;
  }

  protected function getReflectorStatic() {

    if (is_null($this->reflector)) {

      if (!$result = $this->parseReflector(array(), true)) {

        $result = $this->getType()->getReflectorStatic();
      }

      $this->reflector = $result;
    }

    return $this->reflector;
  }

  protected function loadReflector() {

    return $this->sReflector;
  }

  protected function setReflectorName($sName) {

    $this->sReflector = $sName;
  }

  public function buildReflector(array $aArguments = array(), $sAlias = 'cached') {

    if ($sClass = $this->loadReflector()) {

      $result = $this->createObject($sClass, $aArguments, null, false);
    }
    else if (!$result = $this->parseReflector($aArguments)) {

      if (!$this->getType(false) || !$result = $this->getType()->buildReflector($aArguments)) {

        $result = $this->createObject($sAlias, $aArguments, null, false);
      }
    }

    return $result;
  }

  public function asToken() {

    if (!$this->sToken) {

      $this->sToken = "schema:element [{$this->getNamespace()}:{$this->getName()}]";
    }

    return $this->sToken;
  }

  public function __clone() {

    $this->setType(clone $this->getType());
  }

}

