<?php

namespace sylma\modules\users\test;
use sylma\core;

class Tester extends core\module\Domed
{

  public function prepare($sUser = 'user', $sGroup = 'group', $sUserGroup = 'user_group')
  {
    $this->execute("TRUNCATE TABLE `$sUser`;");
    $this->execute("TRUNCATE TABLE `$sGroup`;");
  }

  protected function execute($sQuery) {

    return $this->getManager(self::DB_MANAGER)->getConnection()->execute($sQuery);
  }
}
