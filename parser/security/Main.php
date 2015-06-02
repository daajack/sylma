<?php

namespace sylma\parser\security;
use sylma\core, sylma\parser\reflector, sylma\view;

abstract class Main extends reflector\handler\Elemented {

  protected $parent;

  protected function reflectTest(array $aRights) {

    $window = $this->getWindow();

    $user = $window->addControler('user');
    $call = $window->createCall($user, 'getMode', 'php-boolean', array($aRights['user'], $aRights['group'], $aRights['mode']));

    $mode = $window->argToInstance(\Sylma::MODE_EXECUTE);
    $test = $window->createTest($call, $mode, '&');

    return $test;
  }

  protected function reflectRights($result, array $aRights) {

    $window = $this->getWindow();
    $parser = $this->getParent(); // view\parser\Elemented
    $test = $this->reflectTest($aRights);

    //return $window->createCondition($this->reflectTest($aRights), $result);
    return $window->createCaller(function() use ($window, $aRights, $result, $parser, $test) {

      return $window->createCondition($test, $parser->addToResult($result, false));
    });
  }
}