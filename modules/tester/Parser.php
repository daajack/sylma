<?php

namespace sylma\modules\tester;
use sylma\core, sylma\dom, sylma\storage\fs;

class Parser extends Prepare {

  const NS = 'http://www.sylma.org/modules/tester/parser';
  const TRASH_MANAGER = 'fs/trash';
  const DEBUG_RUN = true;
  const RESULT_ARGUMENT = 'result';
  const PARSER_EXCEPTION = 'exception-parser';
  const BUILD_EXCEPTION = 'build-exception';

  protected static $sArgumentClass = 'sylma\core\argument\Readable';
  protected $exportDirectory;

  public function __construct() {

    $this->setNamespace(self::NS, self::PREFIX);

    $this->setControler($this);

    $this->exportDirectory = $this->loadCacheDirectory($this->getDirectory());

    if ($samples = $this->getExportDirectory()->getDirectory('samples', false)) {

      $samples->delete();
    }

    //$this->setArguments(array());
    //$this->setSettings($this->getArguments());
    //parent::__construct();
    $this->initSettings();

    $this->initProfile();

    //$this->setFiles(array($this->getFile('basic.xml')));
  }

  protected function loadCacheDirectory(fs\directory $dir) {

    $cache = $this->getManager('fs/cache');
    $result = $cache->getDirectory()->addDirectory((string) $dir);

    return $result;
  }

  public function getExportDirectory() {

    if (!$this->exportDirectory) {

      $this->throwException('No export directory');
    }

    return $this->exportDirectory;
  }

  public function setExportDirectory(fs\directory $exportDirectory) {

    $this->exportDirectory = $exportDirectory;
  }

  protected function parseResult(dom\element $test, fs\file $file, array $aArguments = array()) {

    $aResult = array(
      'content' => null,
      'result' => true,
    );

    if ($document = $test->getx('self:document', array(), false)) {

      require_once('core/functions/Path.php');
      $sName = core\functions\path\urlize($file->getName() . '-' . $test->readx('@name'));

      $cache = $this->getExportDirectory()->createFile($sName);
      $cache->saveText((string) $this->createDocument($document->getFirst()));

      $manager = $this->getManager(self::PARSER_MANAGER);
      $aResult = $this->buildResult($test, $manager, $cache, $aArguments);

      $this->set(self::RESULT_ARGUMENT, $aResult['content']);
    }

    return $aResult;
  }

  protected function buildResult(dom\element $test, $manager, fs\file $file, array $aArguments) {

    $bResult = false;
    $result = null;

    try
    {
      $this->startProfile();
      $manager->build($file, $this->getDirectory());
      $this->stopProfile();

      if ($sLoad = $test->readx('self:load', array(), false)) {

        if (is_null(eval('$closure = function($manager) { ' . $sLoad . '; };'))) {

          $aArguments = $this->evaluate($closure, $this);
        }
      }

      $bExpected = !$test->getx('self:expected', array(), false);
      $sRun = $test->readx('@run', array(), false);

      $result = $this->loadResult($manager, $file, $aArguments, $sRun !== 'false', $bExpected);

      $bResult = true;

    } catch (core\exception $e) {

      $bResult = $this->catchParserException($test, $e, $file);
    }

    return array(
      'content' => $result,
      'result' => $bResult,
    );
  }

  protected function catchParserException(dom\element $test, core\exception $e, fs\file $file) {

    $bResult = $this->catchExceptionCheck(
      $test->readAttribute(self::PARSER_EXCEPTION, null, false),
      $test,
      $e,
      $file,
      $test->readAttribute(self::BUILD_EXCEPTION, null, false)
    );

    $test->setAttribute(self::PARSER_EXCEPTION, null);
    $test->setAttribute(self::BUILD_EXCEPTION, null);

    return $bResult;
  }

  protected function loadResult($manager, fs\file $file, array $aArguments, $bRun = true, $bDelete = true) {

    $result = null;
    $this->setFile($file);

    if ($bRun) {

      $this->startProfile();
      $result = $manager->load($file, $this->checkArguments($aArguments), false, static::DEBUG_RUN, true);
      $this->stopProfile();

      if ($bDelete) $file->delete();
    }

    return $result;
  }

  /**
   * Complete array of arguments with empty arguments
   * using keys : arguments, post, contexts
   * @return array
   */
  protected function checkArguments(array &$aArguments) {

    if (!isset($aArguments['arguments'])) {

      $aArguments['arguments'] = $this->createArgument(array());
    }

    if (!isset($aArguments['post'])) {

      $aArguments['post'] = $this->createArgument(array());
    }

    if (!isset($aArguments['contexts'])) {

      $aArguments['contexts'] = $this->createArgument(array());
    }

    return $aArguments;
  }

  protected function loadResultNode(dom\element $test) {

    $this->setArgument('node', null);

    foreach($test->queryx('self:node', array(), false) as $node) {

      if ($sName = $node->readx('@name', array(), false)) $sName = "node/$sName";
      else $sName = 'node';

      if ($node->countChildren() > 1) {

        $this->set($sName, $node->getChildren());
      }
      else {

        $this->set($sName, $node->getFirst());
      }

    }
  }

  protected function test(dom\element $test, $sContent, $controler, dom\document $doc, fs\file $file) {

    $bResult = false;
    $this->set(self::RESULT_ARGUMENT, null);

    try {

      $this->prepareTest($test, $controler);
      $aResult = $this->parseResult($test, $file);
      $bResult = $aResult['result'];
    }
    catch (core\exception $e) {

      $bResult = false;

      $e->addPath('Test ID : ' . $test->readx('@name'));
      $e->addPath($file->asToken());

      $e->save(false);
    }

    if ($bResult) {

      if ($test->readAttribute(self::PARSER_EXCEPTION, null, false) ||
          $test->readAttribute(self::BUILD_EXCEPTION, null, false))
      {
        $this->saveProfile();
        $this->launchException('An exception should occured');
      }
      else {

        $this->loadResultNode($test);
        $bResult = parent::test($test, $sContent, $controler, $doc, $file);
      }
    }
    else {

      $this->saveProfile();
    }

    return $bResult;
  }

  public function loadScript(array $aArguments = array(), array $aPosts = array(), array $aContexts = array()) {

    $this->startProfile();

    $result = $this->getManager(self::PARSER_MANAGER)->load(
      $this->getFile(),
      $this->buildScriptArguments($aArguments, $aContexts, $aPosts),
      null,
      true,
      true
    );

    $this->stopProfile();

    return $result;
  }

  public function getScript($sPath, array $aArguments = array(), array $aContexts = array(), array $aPosts = array(), $bRun = true, $bUpdate = true) {

    $this->startProfile();

    $result = $this->getManager(self::PARSER_MANAGER)->load(
      $this->getFile($sPath),
      $this->buildScriptArguments($aArguments, $aContexts, $aPosts),
      $bUpdate,
      $bRun,
      true
    );

    $this->stopProfile();

    return $result;
  }

  public function buildScript($sPath, array $aArguments = array(), array $aContexts = array(), array $aPosts = array()) {

    return $this->getScript($sPath, $aArguments, $aPosts, $aContexts, false);
  }

  public function set($sPath, $mValue = null) {

    return parent::set($sPath, $mValue);
  }

  public function get($sPath, $bDebug = true) {

    return parent::get($sPath, $bDebug);
  }

  public function read($sPath, $bDebug = true) {

    return parent::read($sPath, $bDebug);
  }

  public function getArgument($sPath, $bDebug = true, $mDefault = null) {

    return $this->get($sPath, $bDebug);
  }

  public function readArgument($sPath, $bDebug = true, $mDefault = null) {

    return $this->read($sPath, $bDebug);
  }

  public function setArgument($sPath, $mValue) {

    return $this->set($sPath, $mValue);
  }
}

