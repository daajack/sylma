<?php

define('PATH_SYLMA', '../../lib');
define('PATH_PHP', 'xml');
define('MAIN_DIRECTORY', PATH_PHP);

define('PATH_LOGIN',    '/utilisateur/login');
define('PATH_LOGIN_DO', '/utilisateur/login_do');
define('PATH_LOGOUT',   '/utilisateur/logout');
define('PATH_USER_EDIT',   '/utilisateur/edit/');
define('PATH_ERROR',    '/error/view');
define('PATH_ACCESS',   '/error/access');

define('PATH_INTERFACES', '/interfaces.cml');
define('PATH_SPECIALS', '/specials.cml');

define('NS_XHTML', 'http://www.w3.org/1999/xhtml');
define('NS_EXECUTION', 'http://www.sylma.org/execution');
define('NS_SECURITY', 'http://www.sylma.org/security');
define('NS_INTERFACE', 'http://www.sylma.org/interface');

define('SITE_TITLE', 'MySite');
define('ANONYMOUS', 'famous');

define('ERROR_LEVEL', E_ALL ^ E_WARNING ^ E_NOTICE);
// A mettre pour le débuggage, renvoie Controler::isAdmin() à true et enlève le cache des templates

define('FORMAT_MESSAGES', false);
// define('FORMAT_MESSAGES', true);
// define('DEBUG', false);
define('DEBUG', true);

// if (DEBUG) echo '<strong>DEBUG is ON</strong>';

$aDefaultInitMessages = array(
  'action' => array('action-error', 'action-warning', '_action-report', 'action-notice'),
  'xml' => array('xml-error', 'xml-warning', '_xml-report', 'xml-notice'));

$aActionExtensions = array('', '.iml', '.eml', '.dml');

$aDB = array(
  
  'host'      => 'localhost',
  'database'  => 'mysite',
  'user'      => 'root',
  'password'  => '',
);