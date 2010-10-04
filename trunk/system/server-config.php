<?php

define('PATH_SYLMA', 'protected/sylma');
define('PATH_LIBS', PATH_SYLMA.'/lib');
define('PATH_PHP', 'protected');
define('MAIN_DIRECTORY', PATH_PHP);
define('SESSION_MAX_LIFETIME', 3600 * 8);
define('ERROR_LEVEL', E_ALL);
//define('ERROR_LEVEL', E_ALL ^ E_WARNING ^ E_NOTICE);
define('SYLMA_PATH_SETTINGS',   '/config/root.xml');

/* Messages formating */

define('SYLMA_RESULT_LIFETIME', 30); // seconds

define('MESSAGES_SHOW_XML', true); // WARNING : with false can cause UTF-8 errors - TODO
define('SYLMA_ENCODING_CHECK', true);

define('FORMAT_MESSAGES', true);
define('SYLMA_MESSAGES_BACKTRACE', true);
define('SYLMA_BACKTRACE_LIMIT', 3);
define('SYLMA_LOG_MESSAGES', false);

define('SYLMA_ACTION_STATS', true); // infos

/* Debug CONSTANTS */

define('DEBUG', false);
define('SYLMA_DISABLE_RIGHTS', false);
define('SYLMA_DISABLE_STATUTS', false); // messages statuts
define('SYLMA_PRINT_MESSAGES', false);
define('SYLMA_ACTION_ERROR_REDIRECT', true);

define('SYLMA_DB_SHOW_QUERIES', false);

$aDB = array(
  'host' => 'localhost',
  'user' => 'admin',
  'password' => 'admin',
  'database' => 'cmp',
  'port' => 1984);

set_include_path(get_include_path() . PATH_SEPARATOR . PATH_SYLMA . PATH_SEPARATOR . PATH_PHP);

