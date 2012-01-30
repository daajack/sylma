<?php

namespace sylma\modules\formater;
use \sylma\core, \sylma\dom;

require_once('core/module/Domed.php');

class Controler extends core\module\Domed {

  const NS = 'http://www.sylma.org/modules/formater';

  public function __construct() {

    $this->setDirectory(__file__);
    $this->setNamespace(self::NS);
    $this->loadDefaultArguments();
  }

  protected function loadArray(array $aVal) {

    $aItems = array();

    foreach ($aVal as $mKey => $mVal) {

      $mVal = $this->loadVar($mVal);
      $mKey = $this->loadVar($mKey);

      $aItems[] = array(
        'key' => $mKey,
        'value' => $mVal,
      );
    }

    return array('array' => array(
      '#item' => $aItems,
    ));
  }

  protected function loadObject($val) {

    $result = null;

    if ($val instanceof core\argument\Basic) {

      $aResult = $val->query();
      $result = $this->loadArray($aResult);
    }
    else if ($val instanceof dom\handler) {

      $result = $val;
    }
    else if ($val instanceof core\argumentable) {

      $arg = $val->asArgument();
      $result = $this->loadArgument($arg);
    }
    else if ($val instanceof dom\domable) {
      
      $result = $val->asDOM();
    }

    return $result;
  }

  protected function loadArgument(core\argument $arg) {

    return $this->loadObject($arg);
  }

  protected function loadString($sVal) {

    return array('string' => $sVal);
  }

  protected function loadNumeric($iVal) {

    return array('numeric' => $iVal);
  }

  protected function loadVar($mVar) {

    $aResult = array();

    if (is_array($mVar)) $aResult = $this->loadArray ($mVar);
    else if (is_object($mVar)) $aResult = $this->loadObject ($mVar);
    else if (is_string($mVar)) $aResult = $this->loadString($mVar);
    else if (is_numeric($mVar)) $aResult = $this->loadNumeric($mVar);
    else {

      $aResult = array('unknown' => array('@type' => gettype($mVar)));
    }

    return $aResult;
    //else if ()
  }

  public function asHTML($mVal) {

    $result = null;
    $aResult = array('window' => $this->loadVar($mVal));

    if ($arg = $this->createArgument($aResult)) {

      if ($doc = $arg->asDOM()) {

        $template = $this->getTemplate('default.xsl');
        $result = $template->parseDocument($doc);
        //dspm($result->asString());
      }
    }

    return $result;
  }

  public function asToken($mVal) {

    $sResult = '[unknown]';

    if (is_string($mVal)) {

      $sResult = '[string] ' . $this->limitString($mVal);
    }
    else if (is_object($mVal)) {

      $sResult = '[object] ' . get_class($mVal);
    }
    else if (is_array($mVal)) {

      $sResult = '[array] ' . '@length ' . count($mVal);
    }
    else if (is_null($mVal)) {

      $sResult = '[null] ';
    }

    return '@var ' . $sResult;
  }

  public function limitString($mValue, $iLength = 50, $bXML = false) {

    $sValue = (string) $mValue;

    if (strlen($sValue) > $iLength) $sValue = substr($sValue, 0, $iLength).'...';

    if ($bXML) {

      $iLastSQuote = strrpos($sValue, '&');
      $iLastEQuote = strrpos($sValue, ';');

      if (($iLastSQuote) && ($iLastEQuote < $iLastSQuote)) $sValue = substr($sValue, 0, $iLastSQuote).'...';
    }

    return $sValue;
  }

  public function getBacktrace() {

    foreach (debug_backtrace() as $aLine) {

      echo $aLine['file'].' - '.$aLine['class'].'::'.$aLine['function'] . ' - ' . $aLine['line'] . '<br/>';
    }
  }
}