<?php
/*
 * Description
 * Created on 17 oct. 2008
 */

define('MAIN_DIRECTORY', 'protected'); // set site path
require(MAIN_DIRECTORY . '/sylma/Init.php'); // set SYLMA path

Sylma::init('server.yml'); // relative path to yaml server's config file
echo Sylma::render();
