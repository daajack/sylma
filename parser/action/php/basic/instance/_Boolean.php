<?php

namespace sylma\parser\action\php\basic\instance;
use sylma\parser\action\php;

require_once('_Scalar.php');
require_once(dirname(dirname(__dir__)) . '/_instance.php');

class _Boolean extends _Scalar implements php\_instance {

  protected $sFormat = 'php-boolean';

  protected $bValue = null;
  protected $content;

  public function __construct(php\_window $controler, $mValue) {

    $this->setControler($controler);
    $this->setValue($mValue);
  }

  protected function setValue($mValue) {

    $bValue = false;

    if (is_bool($mValue)) {

      $this->bValue =  $mValue;
    }
    else if (is_string($mValue)) {

      if ($mValue == 'true') $this->bValue = true;
      else if ($mValue != 'false') {

        $this->throwException(txt('Unknown value for boolean conversion : %s', $mValue));
      }
    }
    else if (is_numeric($mValue)) {

      $this->bValue =  (bool) $mValue;
    }
    else {

      $this->content = $mValue;
    }
  }

  public function useFormat($sFormat) {

    return parent::useFormat($sFormat) || $sFormat == 'php-bool';
  }

  protected function getValue($bString = false) {

    if ($bString) return $this->bValue ? 'true' : 'false';
    else return $this->bValue;
  }

  public function asArgument() {

    if ($this->content) {

      $mContent = $this->content;
    }
    else {

      $mContent = array('@value' => $this->getValue(true));
    }

    return $this->getControler()->createArgument(array('boolean' => $mContent));
  }
}
