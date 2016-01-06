<?php

namespace sylma\action\component;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\parser\languages\common;

class Context extends Basic implements common\arrayable {

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->allowText(true);
    $this->allowForeign(true);
  }

  protected function parseChildren(dom\collection $children) {

    if ($aContent = parent::parseChildren($children)) {

      $aResult = $this->getWindow()->parseArrayables($aContent);
      //$aResult = count($aResult) > 1 ? $aResult : current($aResult);
    }
    else {

      $aResult = array();
    }

    return $aResult;
  }

  protected function loadCalls(common\_callable $context, $aContent) {

    $aResult = array();
    $window = $this->getWindow();

    foreach ($aContent as $content) {

      $aResult[] = $window->createInstruction($context->call('add', array($content)));
    }

    return $aResult;
  }

  protected function buildContent(common\_window $window) {

    $sContext = $this->readx('@name');
    $contexts = $window->getVariable('contexts');
    $content = $window->parse($this->parseChildren($this->getNode()->getChildren()));

    $result = $this->loadCalls($contexts->call('get', array($sContext), '\sylma\core\argument'), $content);

    return $result;
  }

  protected function build() {

    $sContext = $this->readx('@location');

    if ($sContext !== 'tree') {

      $window = $this->getRoot()->getResourceWindow();
      $tmpWindow = $this->getHandler()->getWindow();

      $this->getRoot()->setWindow($window);
      $calls = $this->buildContent($window);
      $this->getRoot()->setWindow($tmpWindow);

      $window->add($calls);
      $result = array();
    }
    else {

      $window = $this->getWindow();
      $result = $this->buildContent($window);
    }

    return $result;
  }

  public function asArray() {

    return $this->build();
  }
}
