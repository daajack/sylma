<?php

namespace sylma\device;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\view;

class Elemented extends reflector\handler\Elemented implements reflector\elemented
{

  public function parseContent(dom\collection $children) {

    $parser = $this->getParent();

    $content = $this->loadContent($parser, $children);
    $result = $parser->addToResult($content, false);

    return $result;
  }

  protected function loadContent(view\parser\Elemented $parser, dom\collection $children) {

    if ($template = $parser->getCurrentTemplate(false)) {
      
      $result = $template->parseChildren($children);
    }
    else {
      
      $result = $parser->parseChildren($children);
    }
    
    return $result;
  }
  
  public function reflectApplyDefault($sPath, array $aPath, $sMode, $bRead = false, array $aArguments = array()) {
    
    $window = $this->getWindow();
    $return = $this->createDummy('dummy', array(), null, false);
    $manager = $window->addManager('device', null, $return);
    
    return $manager;
  }
}
