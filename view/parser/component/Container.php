<?php

namespace sylma\view\parser\component;
use sylma\core, sylma\dom, sylma\schema as schema_ns, sylma\template as template_ns;

class Container extends template_ns\parser\template\Argumented {

  const WEIGHT_TYPE = 22;
  const WEIGHT_TYPE_NS = 17;
  const WEIGHT_TYPE_ALL = 12;

  public function parseRoot(dom\element $el) {

    parent::parseRoot($el);

    $this->parseMatch($el);
  }

  public function parseMatch(dom\element $el) {

    $this->setMatch($el->readx('@match', array(), false));

    if ($this->getMatchString()) {

      $this->parseMatchValue($this->getMatchString(), $el);
    }
  }

  /**
   * @used by self::parseMatchValue() anonymouse closure
   */
  public function buildMatchValue(dom\element $el, $sPrefix, $sName) {

    $sNamespace = '';

    if ($sPrefix and !$sNamespace = $el->lookupNamespace($sPrefix)) {

      $this->launchException('Cannot match value, namespace not found', get_defined_vars());
    }

    if (!$sName) {

      $this->launchException('Cannot match value, no name defined', get_defined_vars());
    }

    return array(
      'name' => $sName,
      'namespace' => $sNamespace,
    );
  }

  public function parseMatchValue($sMatch, dom\element $el) {

    if (!$sMatch) {

      $this->throwException('No match defined');
    }

    preg_match_all('`(?:(?<prefix>[\w\-_]+):)?(?<name>[\*\w\-_]+)[|\s]*`', $sMatch, $aMatches, PREG_SET_ORDER);

    $tpl = $this;
    $aNames = array_map(function($item) use ($el, $tpl) {

      $aResult = $tpl->buildMatchValue($el, $item['prefix'], $item['name']);

      return $aResult;

    }, $aMatches);

    $this->aMatch = $aNames;
  }

  public function getWeightSchema(schema_ns\parser\element $element, $sContext, $sMode, $bRoot = false) {

    $iResult = 0;

    if ($this->getMatch() || $bRoot) {

      if (!$this->getMatch()) {

        if ($bRoot && $sMode === $this->getMode()) {

          $iResult = self::WEIGHT_ELEMENT_ROOT;
        }
      }
      else if ($sMode === $this->getMode()) {

        $iElement = $this->getWeightName($element->getNamespace(), $element->getName());

        if ($type = $element->getType()) {

          $iType = $this->getWeightType($type);
          $iResult = $iType > $iElement ? $iType : $iElement;
        }
        else {

          $iResult = $iElement;
        }
      }
    }

    return $iResult;
  }

  protected function startLog($sMessage = 'Template', array $aVars = array()) {

    parent::startLog($sMessage, array_merge($aVars, array(
      'element' => $this->getTree()->asToken(),
    )));
  }

  protected function getWeightType(schema_ns\parser\type $type) {

    $iResult = 0;

    if ($this->getMatch()) {

      $aNames = $this->getMatch();

      foreach ($aNames as $aName) {

        $iMatch = $this->getWeightSingleName($aName, $type->getNamespace(), $type->getName(), self::WEIGHT_TYPE, self::WEIGHT_TYPE_NS, self::WEIGHT_TYPE_ALL);

        if ($iMatch > $iResult) $iResult = $iMatch;
      }
    }

    return $iResult;
  }
}

