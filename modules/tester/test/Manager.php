<?php

namespace sylma\modules\tester\test;
use sylma\core, sylma\modules\tester, sylma\dom, sylma\storage\fs;

class Manager extends tester\Prepare implements core\argumentable {

  const SETTINGS_PATH = 'manager.xml';

  protected $sTitle = 'Test';
  protected $tester;

  public function __construct() {

    $this->setDirectory(__file__);
    $this->setNamespaces(array(
      'self' => self::NS,
    ));

    $this->setManager($this);

    $this->setSettings(self::SETTINGS_PATH);
    $this->tester = $this->create('handler');

    //$this->setFiles(array($this->getFile('basic.xml')));

    parent::__construct();
  }

  protected function test(dom\element $test, $sContent, $manager, dom\document $doc, fs\file $file) {

    //$this->startProfile();
//dsp($test->getx('self:document'));
    $bResult = $this->tester->test($test->getx('self:document/self:test'), $sContent, $manager, $doc, $file);
    //$this->stopProfile();

    $this->set('result', $bResult);
    $this->set('handler', $this->tester);

    return parent::test($test, $sContent, $manager, $doc, $file);
  }
}

