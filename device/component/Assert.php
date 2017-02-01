<?php

namespace sylma\device\component;
use sylma\core, sylma\parser\reflector, sylma\parser\languages\common;

class Assert extends reflector\component\Foreigner implements common\arrayable {

  public function parseRoot(\sylma\dom\element $el) {

    $this->setNode($el);

    $this->allowForeign(true);
    $this->allowUnknown(true);
    $this->allowText(true);
  }

  public function asArray() {
    
    $window = $this->getWindow();
    $resourceWindow = $this->getRoot()->getResourceWindow();
    
    $test = $this->parseTest($resourceWindow);
    $resourceCondition = $resourceWindow->createCondition($test);
    $resourceWindow->add($resourceCondition);
    
    $test = $this->parseTest($window);
    $resourceWindow->setScope($resourceCondition);
    $result = $window->createCondition($test, $this->parseContent());
    $resourceWindow->stopScope();
    
    return array($result);
  }

  protected function parseContent() {

    $content = $this->getParser()->parseContent($this->getNode()->getChildren());

    return $content;
  }

  protected function parseTest(common\_window $window) {

    $return = $this->createDummy('dummy', array(), null, false);
    $manager = $window->addManager('device', null, $return);
    $sValue = $this->readx('@test', true);

    if ($sValue{0} === '!') {

      $result = $window->createNot($manager->call('isDevice', array(substr($sValue, 1))));
    }
    else {

      $result = $manager->call('isDevice', array($sValue));
    }

    return $result;
  }
}
