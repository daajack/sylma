<?php

namespace sylma\modules\users\test;
use sylma\core;

class Tester extends core\module\Domed
{

  public function prepare($sUser = 'user', $sGroup = 'group', $sUserGroup = 'user_group') {

    $this->execute("RENAME TABLE `$sUser` TO `$sUser-tmp`;");
    $this->execute("RENAME TABLE `$sGroup` TO `$sGroup-tmp`;");
    $this->execute("RENAME TABLE `$sUserGroup` TO `$sUserGroup-tmp`;");
  }

  public function complete($sUser = 'user', $sGroup = 'group', $sUserGroup = 'user_group') {

    $this->execute("SELECT COUNT(*) FROM `$sUser-tmp`"); // exception if not exists
    $this->execute("SELECT COUNT(*) FROM `$sGroup-tmp`"); // exception if not exists
    $this->execute("SELECT COUNT(*) FROM `$sUserGroup-tmp`"); // exception if not exists

    $this->execute("DROP TABLE IF EXISTS `$sUserGroup`;");
    $this->execute("DROP TABLE IF EXISTS `$sUser`;");
    $this->execute("DROP TABLE IF EXISTS `$sGroup`;");

    $this->execute("RENAME TABLE `$sUser-tmp` TO `$sUser`;");
    $this->execute("RENAME TABLE `$sGroup-tmp` TO `$sGroup`;");
    $this->execute("RENAME TABLE `$sUserGroup-tmp` TO `$sUserGroup`;");
  }

  protected function execute($sQuery) {

    return $this->getManager(self::DB_MANAGER)->getConnection()->execute($sQuery);
  }
}
