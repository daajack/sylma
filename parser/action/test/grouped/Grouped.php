<?php

namespace sylma\parser\action\test\grouped;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs, \sylma\parser\action;

class Grouped extends tester\Basic implements core\argumentable {

  const NS = 'http://www.sylma.org/parser/action/test/grouped';

  protected $sTitle = 'Grouped';
  protected $exportDirectory;

  public function __construct($sFile = '') {

    $this->getManager('dom');

    $this->setDirectory(__file__);
    $this->setNamespaces(array(
        'self' => self::NS,
        'le' => action\handler::NS,
    ));

    $controler = $this->getManager('action');

    if ($sFile) $this->setFiles(array($this->getFile($sFile)));
    $this->setControler($controler);

    $cache = $this->getManager('fs/cache');
    $this->exportDirectory = $cache->getDirectory()->addDirectory((string) $this->getDirectory());

    $this->setArguments(array());
    //$this->setFiles(array($this->getFile('basic.xml')));
  }

  public function getResult($sType) {

    $action = $this->getArgument('action');

    switch ($sType) {

      case 'dom' : $result = $action->asDOM(); break;
      case 'txt' : $result = $action->asString(); break;
      case 'object' : $result = $action->asObject(); break;
      case 'array' : $result = $action->asArray(); break;
      default :

        $this->throwException(sprintf('Unknown action type : %s', $sType));
    }

    if (is_null($result)) {

      $this->launchException('No result', get_defined_vars());
    }

    return $result;
  }

  protected function prepareTest(dom\element $test) {

    $mResult = null;

    if ($sPrepare = $test->readx('self:prepare', array(), false)) {

      if (eval('$closure = function($controler) { ' . $sPrepare . '; };') === null) {

        $mResult = $this->evaluate($closure, $this);
      }
    }

    return $mResult;
  }

  protected function loadArguments() {

    $aResult = array();

    if ($args = $this->getArgument('arguments', false)) {

      $this->setArgument('arguments', array());
      $aResult = $args->query();
    }

    return $aResult;
  }

  protected function test(dom\element $test, $sContent, $controler, dom\document $doc, fs\file $file) {

    $bResult = null;
    $node = $test->getx('le:action');

    require_once('core/functions/Path.php');
    $sName = core\functions\path\urlize($file->getName() . '-' . $test->readAttribute('name'));

    if ($sException = $test->readAttribute('catch', null, false)) {

      try {

        $this->prepareTest($test);
        $aArguments = $this->loadArguments();

        $action = $controler->buildAction($this->createDocument($node), $aArguments, $this->exportDirectory, $this->getDirectory(), $sName);
        $action->useExceptions(true);

        $action->asArray();

        $bResult = false;
      }
      catch (core\exception $e) {

        $bResult = true;
      }
    }
    else {

      if ($nodeResult = $test->getx('self:node', array(), false)) {

        $this->setArgument('node', $nodeResult->getFirst());
      }

      try {

        $this->prepareTest($test);
        $aArguments = $this->loadArguments();

        $action = $controler->buildAction($this->createDocument($node), $aArguments, $this->exportDirectory, $file->getParent(), $sName);
        $this->setArgument('action', $action);

        if ($expected = $test->getx('self:expected', array(), false)) {

          $bResult = parent::test($test, $expected->read(), $this, $doc, $file);
        }
        else {

          $action->asArray();
          $bResult = true;
        }
      }
      catch (core\exception $e) {

        $e->save(false);
      }
    }

    //if ($bRun)

    //$dir->delete();

    return $bResult;
  }

  public function getDirectory($sPath = '', $bDebug = true) {

    return parent::getDirectory($sPath, $bDebug);
  }

  public function getFile($sPath = '', $bDebug = true) {

    return parent::getFile($sPath, $bDebug);
  }

  public function getArgument($sPath, $bDebug = true, $mDefault = null) {

    return parent::getArgument($sPath, $bDebug, $mDefault);
  }

  public function setArgument($sPath, $mValue) {

    return parent::setArgument($sPath, $mValue);
  }
}

