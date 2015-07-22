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

  protected function initProfile(dom\element $test = null) {
//dsp('init');
    $bResult = $this->readArgument('profile') || $test && $test->readx('@profile', array(), false);

    if ($bResult) {

      $this->loadProfiler();
    }

    return $bResult;
  }

  protected function test(dom\element $test, $sContent, $controler, dom\document $doc, fs\file $file) {

    $result = parent::test($test, $sContent, $controler, $doc, $file);
    $this->saveProfile();

    return $result;
  }

  protected function evaluate(\Closure $closure, $controler) {

    $this->startProfile();
    $result = parent::evaluate($closure, $controler);
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
//dsp('save');
    if (!$this->profiler) {

      return;
    }

    $this->profiler->stop(true);
    $this->profiler->save();
  }

}
