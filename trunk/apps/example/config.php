<?php

define('PATH_SYLMA', '../../lib');
define('PATH_PHP', 'protected');
define('SITE_TITLE', 'Lemon.web');
define('MAIN_DIRECTORY', PATH_PHP);

// A mettre pour le débuggage, renvoie Controler::isAdmin() à true et enlève le cache des templates
define('DEBUG', false);
// define('DEBUG', true);

$aDB = array(
  
  'host'      => 'localhost',
  'database'  => 'example',
  'user'      => 'root',
  'password'  => '',
);