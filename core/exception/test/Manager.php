<?php

namespace sylma\core\exception\test;
use sylma\core, sylma\modules;

class Manager extends modules\users\test\Tester implements core\stringable {

  const LOGIN = '/#sylma/modules/users/login-do.vml';

  protected $aUser = array(
    'name' =>  'root',
    'password' => '12345',
  );

  public function login() {

    $this->setDirectory(__FILE__);

    return $this->getScript(self::LOGIN, array(), $this->aUser, array(
      'messages' => $this->getManager(self::PARSER_MANAGER)->getContext('messages'),
    ));
  }

  protected function checkUser() {

    if (!$this->login()) {

      $sUser = implode(':', $this->aUser);
      $this->launchException("Test need a user authenticated with '$sUser'");
    }
  }

  public function checkConfig() {

    if (\Sylma::read('debug/enable')) {

      $this->launchException('Cannot test in debug mode');
    }

    $this->checkUser();
  }

  public function asString() {

    return '';
  }
}

