<?php

namespace sylma\parser\action\php\basic;
use \sylma\core, \sylma\dom, \sylma\parser\action\php;

require_once('core/argumentable.php');
require_once('Controled.php');

/**
 * Add content result to template, choose for wich as*() method to use for render
 */
class Insert extends Controled implements core\argumentable {

  protected $content;

  protected $iKey;

  protected $sContext = php\_window::CONTEXT_DEFAULT;

  public function __construct(php\_window $controler, php\linable $mContent, $iKey = null, $bTemplate = true) {

    $this->setControler($controler);

    $this->bTemplate = $bTemplate;
    if (is_null($iKey)) $this->iKey = $controler->getKey('insert');
    else $this->iKey = $iKey;

    if ($bTemplate) $controler->add(new self($controler, $mContent, $this->getKey(), false));
    else $this->addContent($mContent);

    $this->setContext($controler->getContext());
  }

  protected function getContext() {

    return $this->sContext;
  }

  protected function setContext($sContext) {

    $this->sContext = $sContext;
  }

  protected function addContent(php\linable $mContent) {

    if ($this->content) {

      $this->throwException(t('Cannot set more than once the content in insert'));
    }

    $this->content = $mContent;
  }

  public function getKey() {

    return $this->iKey;
  }

  public function asArgument() {

    return $this->getControler()->createArgument(array(
      ($this->bTemplate ? 'insert-call' : 'insert') => array(
        '@key' => $this->getKey(),
        '@context' => $this->getContext(),
        $this->content,
      ),
    ));
  }
  
  public function asString() {
    
    return '[sylma:insert:' . $this->getKey() . ']';
  }
}