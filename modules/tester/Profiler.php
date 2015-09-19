<?php

namespace sylma\modules\tester;

use sylma\core,
    sylma\dom,
    sylma\storage\fs,
    sylma\modules;

class Profiler extends Stepper
{

  const PROFILE_ENABLE = true;

  protected $profiler;

  protected function onStart() {

    $this->initProfile();
  }

  protected function useProfile(dom\element $test = null) {

    return $this->readArgument('profile') || $test && $test->readx('@profile', array(), false);
  }

  protected function initProfile(dom\element $test = null) {

    $bResult = $this->useProfile($test);

    if ($bResult) {

      $this->loadProfiler();
    }

    return $bResult;
  }

  protected function test(dom\element $test, $sContent, $manager, dom\document $doc, fs\file $file) {

    $result = parent::test($test, $sContent, $manager, $doc, $file);
    $this->saveProfile();

    return $result;
  }

  protected function evaluate(\Closure $closure, $manager) {

    $this->startProfile();
    $result = parent::evaluate($closure, $manager);
    $this->stopProfile();

    return $result;
  }

  public function loadProfiler() {

    $this->profiler = $this->create('profiler');
  }

  public function startProfile() {
//dsp('start');
    if (!$this->profiler) {

      return;
    }

    $this->profiler->start();
  }

  public function stopProfile() {
//dsp('stop');
    if (!$this->profiler) {

      return;
    }

    $this->profiler->stop();
  }

  protected function saveProfile() {

    $aResult = array();

    if ($this->profiler) {

      $this->profiler->stop(true);
      $aResult = $this->profiler->save();
    }

    return $aResult;
  }

}
