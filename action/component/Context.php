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

    foreach ($aContent as $content) {

      $aResult[] = $context->call('add', array($content));
    }

    return $aResult;
  }

  public function asArray() {

    $window = $this->getRoot()->getResourceWindow();
    $sContext = $this->readx('@name');

    $contexts = $window->getVariable('contexts');

    $tmpWindow = $this->getHandler()->getWindow();
    $this->getRoot()->setWindow($window);

    $content = $this->parseChildren($this->getNode()->getChildren());

    $this->getRoot()->setWindow($tmpWindow);

    $result = $this->loadCalls($contexts->call('get', array($sContext), '\sylma\core\argument'), $content);

    $window->add($result);

    return array();
  }
}
