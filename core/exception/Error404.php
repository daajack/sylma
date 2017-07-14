<?php

namespace sylma\core\exception;
use sylma\core, sylma\modules;

class Error404 extends Basic {
  const DB_MANAGER = 'mysql';

  protected function insert($aContent)
  {

    $db = \Sylma::getManager(self::DB_MANAGER)->getConnection()->getDatabase();
    $sTable = 'error404';

    $sql = "INSERT INTO `$sTable`"
           . " (url, count) VALUES"
           . " (:url, :count)"
           . " ON DUPLICATE KEY UPDATE"
           . " url=url, count = count + 1";

    $sth = $db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
    
    $sth->execute(array(
          ':url' => $_SERVER['REQUEST_URI'],
          ':count' => 1,
        ));
  }

}

