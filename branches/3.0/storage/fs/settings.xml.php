<?php
    return new \sylma\core\argument\parser\Cached(array(
'rights' => array(
  'owner' => 'root',
  'group' => '0',
  'mode' => '711',
  '0' => 'user-mode'),
'browse' => array(
  'excluded' => array(
    '.svn'),
  'depth' => '1',
  '0' => 'extensions',
  'only-path' => '1',
  'root' => '1'),
'tokens' => array(
  'sylma' => array(
    'path' => 'sylma'),
  'tmp' => array(
    'path' => '.sylma-tmp',
    'propagate' => '1'),
  'trash' => array(
    'path' => 'trash')),
'system' => array(
  'rights' => '0770'),
'classes' => array(
  'controler' => array(
    'file' => '\sylma\storage\fs\Controler.php',
    'name' => '\sylma\storage\fs\Controler'),
  'file' => array(
    'file' => '\sylma\storage\fs\basic\File.php',
    'name' => '\sylma\storage\fs\basic\File',
    'classes' => array(
      'document' => array(
        'file' => '\sylma\dom\basic\handler\Rooted.php',
        'name' => '\sylma\dom\basic\handler\Rooted'),
      'editable' => array(
        'file' => '\sylma\storage\fs\basic\editable\File.php',
        'name' => '\sylma\storage\fs\basic\editable\File'))),
  'directory' => array(
    'file' => '\sylma\storage\fs\basic\tokened\Directory.php',
    'name' => '\sylma\storage\fs\basic\tokened\Directory',
    'classes' => array(
      'editable' => array(
        'file' => '\sylma\storage\fs\basic\editable\Directory.php',
        'name' => '\sylma\storage\fs\basic\editable\Directory'))),
  'security' => array(
    'file' => '\sylma\storage\fs\basic\security\Manager.php',
    'name' => '\sylma\storage\fs\basic\security\Manager',
    'classes' => array(
      'document' => array(
        'file' => '\sylma\dom\basic\handler\Rooted.php',
        'name' => '\sylma\dom\basic\handler\Rooted'))))));
  