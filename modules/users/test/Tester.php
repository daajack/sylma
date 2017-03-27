<?php

namespace sylma\modules\users\test;
use sylma\core;

class Tester extends core\module\Domed
{

  public function prepare($sUser = 'user', $sGroup = 'group', $sUserGroup = 'user_group')
  {
    $this->execute("SET FOREIGN_KEY_CHECKS=0;");
    $this->execute("DROP TABLE IF EXISTS `$sUser`;");
    $this->execute("DROP TABLE IF EXISTS `group_group`;");
    $this->execute("DROP TABLE IF EXISTS `$sGroup`;");
    $this->execute("SET FOREIGN_KEY_CHECKS=1;");
  }

  protected function execute($sQuery) {

    return $this->getManager(self::DB_MANAGER)->getConnection()->execute($sQuery);
  }
  
  public function checkGroups() {

    $user = $this->getManager('user');

    if (!in_array('sub1', $user->getGroups()) || !in_array('sub2', $user->getGroups()))
    {
      $this->launchException('Cannot find group sub');
    }
  }
}
