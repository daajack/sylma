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
    
    $this->setDirectory(__FILE__);
    
    $user = $this->getManager('user');
    $db = $this->getManager('mysql')->getConnection();
    
    $password = $user->getManager()->crypt('12345');
    
    $db->execute("DROP TABLE IF EXISTS group_group");
    $db->execute("DROP TABLE IF EXISTS user_group");

    $db->execute("DROP TABLE IF EXISTS `user`");
    $db->execute("DROP TABLE IF EXISTS `group`");

    $this->getScript('/#sylma/storage/sql/alter/index.vml', array(
      'file' => '/#sylma/modules/users/group.xql',
    ));
    
    $this->getScript('/#sylma/storage/sql/alter/index.vml', array(
      'file' => '/#sylma/modules/users/schema.xql',
    ));
    
    $db->insert("INSERT INTO user (`name`, `password`) VALUES ('root', '$password')");
    
    $this->login();
  }

  public function checkConfig() {
    
    $config = array(
      'debug/enable' =>  false,
      'debug/public' =>  false,
      'exception/break' => false,
    );
    
    $error = false;

    foreach ($config as $key => $val) {
      
      if (\Sylma::read($key) !== $val) {
        
        dsp('Bad key : ' . $key);
        $error = true;
        break;
      }
    }
    
    if ($error) {
      
      $this->launchException('Exception configuration error');
    }

    $this->checkUser();
  }

  public function asString() {

    return '';
  }
}

