<?php

namespace sylma\modules\report;
use sylma\core, sylma\schema;

class Report extends schema\cached\form\Form {

  protected function checkCaptcha($reponse) {

    $secret = \Sylma::read('modules/captcha/secret');
    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$reponse);
    $responseData = json_decode($verifyResponse);

    if (!$result = $responseData->success)
    {
      $this->addMessage('La vérification captcha a échouée, veuillez réessayer !', array('error' => true));
    }

    return $result;
  }

  public function validate() {

    if ($result = parent::validate())
    {
      if ($result = $this->checkCaptcha($this->read('g-recaptcha-response')))
      {
        $this->addElement('infos', new \sylma\schema\cached\form\_String(print_r($_SERVER, true)));
      }
    }

    return $result;
  }
}
