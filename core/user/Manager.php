<?php

namespace sylma\core\user;
use sylma\core;

class Manager extends core\module\Filed {

  protected $user;

  public function __construct() {

    $this->setDirectory(__FILE__);

    $this->setArguments(include('settings.xml.php'));
    $this->getArguments()->merge(\Sylma::get('users'));

    $user = $this->createUser();
    $user->load();

    $this->setUser($user);
  }

  public function createUser() {

    return $this->create('user', array($this));
  }

  public function getUser() {

    return $this->user;
  }

  protected function setUser(core\user $user) {

    $this->user = $user;
  }

  public function getDocument($sPath = '', $bDebug = true) {

    return parent::getDocument($sPath, $bDebug);
  }

  public function crypt($sPassword) {

    //return password_hash('test', \PASSWORD_DEFAULT);
    return crypt($sPassword, $this->generateHash());
  }

  /**
   * @from https://php.net/manual/fr/function.crypt.php#114060
   */
  protected function generateHash($cost=11) {

    $salt=substr(base64_encode(openssl_random_pseudo_bytes(17)),0,22);
    $salt=str_replace("+",".",$salt);
    $param='$'.implode('$',array(
            "2y", //select the most secure version of blowfish (>=PHP 5.3.7)
            str_pad($cost,2,"0",STR_PAD_LEFT), //add the cost in two digits
            $salt //add the salt
    ));

    return $param;
  }
}
