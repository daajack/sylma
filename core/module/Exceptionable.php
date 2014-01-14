<?php

namespace sylma\core\module;
use \sylma\core;

class Exceptionable {

  const DB_MANAGER = 'mysql';
  
  /**
   * Throw a customized exception to the main controler
   *
   * @param string $sMessage The message describing the exception
   * @param array|string $mSender A list of keys or a single key describing the previous classes throwing this exception
   * @param integer $iOffset The number of calls before final sent to main controler. This will be used to localize the call in backtrace
   */
  protected function throwException($sMessage, $mSender = array(), $iOffset = 2) {

    $mSender = (array) $mSender;
    $mSender[] = '@class ' . get_class($this);

    \Sylma::throwException($sMessage, $mSender, $iOffset);
  }

  /**
   * Tmp alias
   */
  protected function launchException($sMessage, array $aVars = array(), array $mSender = array()) {

    $mSender = (array) $mSender;
    $mSender[] = '@class ' . get_class($this);

    \Sylma::throwException($sMessage, $mSender, 3, $aVars);
  }
}
