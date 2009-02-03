<?php
/*
 * Description
 * Created on 17 oct. 2008
 */
// error_reporting(0);
error_reporting(E_ALL);

if (!defined("PATH_SEPARATOR")) {
	if (strpos($_ENV[ "OS" ], "Win") !== false )
		define("PATH_SEPARATOR", ";");
	else 
		define("PATH_SEPARATOR", ":");
}

require('config.php');

set_include_path(get_include_path() . PATH_SEPARATOR . PATH_SYLMA . PATH_SEPARATOR . PATH_PHP);

require('Sylma.php');
require('Window.php');
require('Divers.php');

define('PATH_LOGIN',    '/utilisateur/login');
define('PATH_LOGIN_DO', '/utilisateur/login_do');
define('PATH_LOGOUT',   '/utilisateur/logout');
define('PATH_ERROR',    '/error/view');
define('PATH_ACCESS',   '/error/access');

session_start();

// Arguments globaux ! obligatoire !

db::setArgument('host', $aDB['host']);
db::setArgument('database', $aDB['database']);
db::setArgument('user', $aDB['user']);
db::setArgument('password', $aDB['password']);

db::connect();

$sError = set_error_handler("userErrorHandler");

if (DEBUG) echo Controler::getMessages();
echo Controler::trickMe('intervention', 'home');

