<?php

namespace sylma\modules\tester;
use sylma\core, sylma\dom, sylma\storage\fs;

abstract class Prepare extends Profiler {

  const DB_ARGUMENTS = '/#sylma/view/test/database.xml';
  const DB_CONNECTION = 'test';

  protected function prepareTest(dom\element $test, $manager) {

    $bResult = true;

    if ($prepare = $test->getx('self:prepare', array(), false)) {

      if (is_null(eval('$closure = function($controler) { $manager = $controler; ' . $prepare->readx() . '; };'))) {

        $this->evaluate($closure, $manager);
        $this->onPrepared();
      }
      else {

        $bResult = false;
      }

      $prepare->remove();
    }

    return $bResult;
  }

  protected function test(dom\element $test, $sExpected, $manager, dom\document $doc, fs\file $file) {

    $bResult = false;
    $bReady = true;

    $this->resetCount();

    $sExpected = $test->readx('self:expected', array(), false);

    try {

      $this->prepareTest($test, $manager);

      if ($bReady) {

        if ($sExpected) {

          if (is_null(eval($this->buildClosure($sExpected)))) {

            $bResult = $this->evaluate($closure, $manager);
          }
        }
        else {

          $bResult = $this->testNode();
        }
      }
    }
    catch (core\exception $e) {

      $bResult = $this->catchException($test, $e, $file);
    }

    $this->saveProfile();

    return $bResult;
  }

  protected function testNode() {

    $result = $this->getArgument('result', false);

    if ($node = $this->getArgument('node', false)) {

      $bResult = $this->compareNodes($node, $result);
    }
    else {

      $bResult = true;
    }

    return $bResult;
  }

  protected function onPrepared() {


  }

  public function resetDB($sAlias = '') {

    if (!$sAlias) {

      $sAlias = static::DB_ARGUMENTS;
    }

    $arg = $this->createArgument($sAlias);
    $db = $this->getManager(static::DB_MANAGER)->getConnection(static::DB_CONNECTION);

    try {

      $db->query($arg->read('script'), false);
    }
    catch (core\exception $e) {

    }
  }
}