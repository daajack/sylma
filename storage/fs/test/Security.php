<?php

namespace sylma\storage\fs\test;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs;

class Security extends tester\Prepare implements core\argumentable {

  const NS = 'http://www.sylma.org/storage/fs/test';
  protected $sTitle = 'Security';
  protected $user;

  protected $aUsers = array(
    'tester01' => array(
      'test01',
      'test00',
    ),
    'tester02' => array(
      'test02',
      'test00',
    ),
    'tester03' => array(
      'test03',
      'test00',
    ),
  );

  public function __construct() {

    if (!\Sylma::isAdmin()) {

      $this->launchException('Must be root');
    }

    $this->setDirectory(__file__);
    $this->setNamespace(self::NS, 'self');

    $this->setArguments('../settings.yml');
    parent::__construct();

    //$dir = $this->getDirectory();

    //$this->setManager($this->createManager((string) $dir), 'fs/test');
    $this->setSettings(array());
    $this->setManager($this);

    $this->setFiles(array($this->getFile('security.xml')));
  }

  protected function createManager($sPath) {

    $result = $this->create('manager', array(\Sylma::ROOT));
    $result->loadDirectory($sPath);

    return $result;
  }

  public function getUser() {

    return $this->user;
  }

  public function setUser($sName) {

    if (!array_key_exists($sName, $this->aUsers)) {

      $this->throwException(sprintf('Unknown test user : %s', $sName));
    }

    $user = $this->getManager('user')->getManager();

    $aGroups = $this->aUsers[$sName];
    $this->user = $user->create('user', array($user, $sName, $aGroups));
  }

  public function onPrepared() {

    $this->getManager('fs/test')->setManager($this->getUser(), 'user');
  }

  protected function test(dom\element $test, $sContent, $manager, dom\document $doc, fs\file $file) {

    \Sylma::get('debug')->set('rights', false);
    $this->setManager($this->createManager(''), 'fs/test');

    $result = parent::test($test, $sContent, $this, $doc, $file);

    return $result;
  }
}


