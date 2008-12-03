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

set_include_path(get_include_path() . PATH_SEPARATOR . '../lib' . PATH_SEPARATOR . '..');

require('Error.php');
require('Global.php');

require('HTML.php');
require('HTML_Form.php');
require('Field.php');
require('Divers.php');
require('Controler.php');
require('Action.php');
require('User.php');
require('Database.php');
require('spyc/spyc.php');

require('../Window.php');

session_start();
db::connect();

$sError = set_error_handler("userErrorHandler");

if (DEBUG) echo Controler::getMessages();
echo Controler::trickMe('intervention', 'home');

