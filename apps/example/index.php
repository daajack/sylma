<?php
/*
 * Description
 * Created on 17 oct. 2008
 */

if (!defined("PATH_SEPARATOR")) {
	if (strpos($_ENV[ "OS" ], "Win") !== false )
		define("PATH_SEPARATOR", ";");
	else 
		define("PATH_SEPARATOR", ":");
}

require('config.php');

if (DEBUG) error_reporting(E_ALL);
else {
  
  // libxml_use_internal_errors(false);
  error_reporting(0);
}

set_include_path(get_include_path() . PATH_SEPARATOR . PATH_SYLMA . PATH_SEPARATOR . PATH_PHP);

require('Sylma.php');
require('Window.php');
require('Divers.php');

session_start();

db::setArgument('host', $aDB['host']);
db::setArgument('database', $aDB['database']);
db::setArgument('user', $aDB['user']);
db::setArgument('password', $aDB['password']);

db::connect();

$sError = set_error_handler("userErrorHandler");

// if (DEBUG) echo Controler::getMessages();
echo Controler::trickMe();
