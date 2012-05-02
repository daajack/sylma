<?php

namespace sylma\parser\action\cached;
use \sylma\core, \sylma\dom, \sylma\parser, \sylma\storage\fs;

require_once('Basic.php');
require_once('dom/domable.php');

abstract class Document extends Basic implements dom\domable {

  protected $sTemplate = '';

  protected function loadTemplate($iKey, array $aArguments) {

    $sResult = $this->includeTemplate($this->sTemplate, $iKey, $aArguments);

    $doc = $this->create('document');
    $doc->setContent($sResult);

    return $doc;
  }

  protected function parseAction() {

    $aResult = null;
    $aArguments = parent::parseAction();

    if ($this->useTemplate()) {
/*
      $controler = $this->getControler();
      $file = $controler->getFile();

      $sTemplate = $file->getParent()->getDirectory(parser\action::EXPORT_DIRECTORY)->getRealPath() . '/' . $file->getName() . '.tpl.php';
*/
      $aResult = $aArguments;
      $aResult[self::CONTEXT_DEFAULT] = array($this->loadTemplate(0, $aArguments));
      //dspf($aResult);
    }
    else {

      $aResult = $aArguments;
    }

    return $aResult;
  }

  protected function includeTemplate($sTemplate, $iTemplate, array $aArguments) {

    ob_start();

    include($sTemplate);
    $sResult = ob_get_contents();

    ob_end_clean();

    return $sResult;
  }

  protected function useTemplate() {

    return (bool) $this->sTemplate;
  }

  protected function loadDomable(dom\domable $val) {

    $dom = $val->asDOM();

    return $dom;
  }

  public function asDOM() {

    $mAction = $this->getContext();

    if ($this->useTemplate()) {

      $mResult = array_pop($mAction);
    }
    else {

      $iAction = count($mAction);

      if ($iAction == 1) {

        $mAction = array_pop($mAction);
      }

      if ($iAction > 1 || !($mAction instanceof dom\handler)) {

        $mResult = $this->getControler()->create('document');
        $mResult->add($mAction);
      }
      else {

        $mResult = $mAction;
      }
    }

    return $mResult;
  }
}
