<?php

namespace sylma\storage\sql\locale;
use sylma\core, sylma\dom, sylma\parser\languages\common, sylma\storage\sql;

class Translate extends sql\view\component\Basic implements common\arrayable
{

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->allowText(true);
    $this->allowForeign(true);
    $this->allowUnknown(true);
  }
  
  protected function build() {
    
    $content = $this->parseChildren($this->getNode()->getChildren());
    $window = $this->getHandler()->getWindow();
    
    $locale = $window->addManager('locale');

    return array($locale->call('getTranslation', array($window->toString($content), (string) $this->getSourceFile())));
  }
  
  public function asArray() {
    
    return $this->build();
  }
}