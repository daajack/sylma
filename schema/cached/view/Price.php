<?php

namespace sylma\schema\cached\view;
use sylma\core;

class Price extends Numeric {

   public static function format($sValue, array $aSettings) {

     $sValue = parent::format($sValue, $aSettings);

     if (!isset($aSettings['simple']) || !$aSettings['simple']) {

       $sResult =
        '<span class="price">' .
          '<span class="currency">' .
            \Sylma::read('schema/currency') .
          '</span>' .
          '<span class="value numeric">' .
            $sValue .
          '</span>' .
          '<span class="decimal">.-</span>' .
        '</span>';
     }
     else {

       $sResult = $sValue . '.-';
     }

     return $sResult;
   }
}

