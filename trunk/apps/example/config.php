<?php
  
define('PATH_SYLMA', '../../lib');
define('PATH_PHP', 'protected');
define('SITE_TITLE', 'Lemon.web');

// A mettre pour le débuggage, renvoie Controler::isAdmin() à true et enlève le cache des templates
define('DEBUG', false);

$aDB = array(
  
  'host' => 'localhost',
  'user' => 'root',
  'password' => '',
);