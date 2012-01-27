<?php

namespace sylma\parser\action\php\basic;
use sylma\parser\action\php, sylma\core;

require_once(dirname(__dir__) . '/_Scalar.php');

require_once('core/argumentable.php');

class _String extends php\basic\_Scalar implements core\argumentable {

  protected $sFormat = 'php-string';

  public function __construct(php\_window $controler, $mValue) {

    $this->setControler($controler);
    $this->setValue($mValue);
  }

  protected function setValue($mValue) {

    if (is_array($mValue)) {

      foreach ($mValue as $mVal) {

        $this->setValue($mVal);
      }
    }
    else {

      $this->aValues[] = $mValue;
    }
  }

  public function asArgument() {

    if (!$this->aValues) {

      $this->getControler()->throwException(t('No value defined for string'));
    }

    if (count($this->aValues) == 1) {

      $aResult = array('cast' => array(
        '@type' => 'string',
        $this->aValues,
      ));
    }
    else {

      $aResult = array('concat' => $this->aValues);
    }

    return $this->getControler()->createArgument($aResult);
  }
}