<?php

namespace sylma\modules\tester\stepper;
use sylma\core, sylma\dom, sylma\storage\fs, sylma\modules\tester;

class Standalone extends tester\Parser implements dom\domable {

  //const NS = 'http://www.sylma.org/parser/js/binder/test';
  //const DEBUG_RUN = false;

  protected $iTestKey;

  public function __construct(core\argument $args, core\argument $post, core\argument $contexts) {

    $this->contexts = $contexts;

    //$this->initArguments();
    $this->setSettings(\Sylma::get('tester'));
    $this->initProfile();
  }

  public function initTest(fs\file $file, $iTest) {

    $this->setDirectory($file->getParent());
    $this->setNamespace(self::NS, 'self');

    $this->setManager($this);

    //$this->resetDB();

    $this->setFile($file);
    $this->setTestKey($iTest);
    //$this->setSettings(array());

    $this->exportDirectory = $this->loadCacheDirectory($this->getDirectory());
  }

  public function getTestKey() {

    return $this->iTestKey;
  }

  public function setTestKey($iTestKey) {

    $this->iTestKey = $iTestKey;
  }

  protected function loadTest(dom\handler $doc) {

    $iTest = $this->getTestKey();
    $result = $doc->getx("self:test[position() = $iTest]");

    return $result;
  }

  public function getScript($sPath, array $aArguments = array(), array $aContexts = array(), array $aPosts = array(), $bRun = true, $bUpdate = true) {

    return parent::getScript($sPath, $aArguments, $this->contexts->query(), $aPosts, $bRun, $bUpdate);
  }

  public function asDOM() {

    $doc = $this->getFile()->getDocument($this->getNS());

    $test = $this->loadTest($doc);
    $this->prepareTest($test, $this);

    $aResult = $this->parseResult($test, $this->getFile(), array('contexts' => $this->contexts));
    $result = $aResult['content'];

    if ($aResult['result'] && !$result) {

      $this->prepareTest($test, $this);
      $result = $this->get(self::RESULT_ARGUMENT);
    }

    if (!$aResult['result']) {

      $this->launchException('Test result is FALSE');
    }

    $bCallback = 0;

    if (!$expected = $test->getx('self:expected', array(), false)) {

      $expected = $test->getx('self:callback');
      $bCallback = 1;
    }

    $sExpected = $expected->readx();

    $sBind = $expected->readx('@bind', array(), false);
    if (!$sBind) $sBind = 'example';

    $this->contexts->get('js/load')->add("$(window).addEvent('load', function() { sylma.tester.main.run(function() { $sExpected; }, $sBind, $bCallback); });");

    $this->saveProfile();

    return $result;
  }
}

