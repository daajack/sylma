<?php

namespace sylma\parser\reflector\builder;
use \sylma\core, sylma\parser\languages\common, sylma\dom, sylma\parser\reflector, sylma\storage\fs, sylma\template;

class Documented extends Logger implements reflector\documented {

  const PHP_TEMPLATE = '/#sylma/parser/languages/php/source.xsl';
  const WINDOW_ARGS = 'php';

  const BUILD_NS = 'http://2013.sylma.org/parser/reflector/builder';
  const BUILD_PREFIX = 'build';

  const MODE_DEFAULT = 'view';

  protected $reflector;
  protected $sourceDir;
  protected $window;
  protected $sReturn;
  protected $sMode = self::MODE_DEFAULT;

  protected $bThrow = true;
  protected $aElements = array();
  protected $aDependencies = array(
    'file' => array(),
    'script' => array(),
  );

  public function __construct($manager, fs\file $file = null, fs\directory $dir = null, core\argument $args = null, dom\document $doc = null) {

    $this->setManager($manager);
    if ($file) $this->setFile($file);

    $this->setNamespace(self::BUILD_NS, self::BUILD_PREFIX);

    if ($doc) {

      $this->setDocument($doc);
    }
    else if ($file) {

      $this->setDocument($file->asDocument(array(), \Sylma::MODE_EXECUTE));
    }

    $this->loadDefaultArguments();
    if ($args) $this->setArguments($args);
    $this->loadArguments($args);
    $this->loadLogger();

    $this->setDirectory(__FILE__);
    if ($dir) $this->setSourceDirectory($dir);
  }

  protected function loadArguments(core\argument $arg = null) {

    if ($arg and $sArguments = $arg->read('arguments', null, false)) {

       $this->setArguments($sArguments);
    }
  }

  protected function setDocument(dom\handler $doc, $bImport = true) {

    //$doc->registerNamespaces($this->getNS());

    parent::setDocument($doc);

    if ($this->getDocument()->isEmpty()) {

      $this->throwException('Empty document');
    }

    return $bImport ? $this->importDocument($doc, $this->getFile(), true) : $doc;
  }

  public function importDocument(dom\handler $doc, fs\file $file, $bSelf = false) {

    $bElement = \Sylma::read('template/debug/source');

    if (!$bSelf) {

      $this->addDependency($file);
    }

    if (!$file->getManager()->getName()) {

      foreach ($doc->queryx('//*') as $el) {

        $el->createAttribute('build:source', (string) $file, $this->getNamespace());

        if ($bElement) {

          $el->createAttribute('build:element', $this->getManager('formater')->buildLink($el->asLink()), $this->getNamespace());
        }
      }
    }

    return $doc;
  }

  protected function setSourceDirectory(fs\directory $sourceDirectory) {

    $this->sourceDir = $sourceDirectory;
  }

  /**
   * Get the source file's directory
   * @return fs\directory
   */
  public function getSourceDirectory($sPath = '') {

    if (!$this->sourceDir) {

      $this->throwException('No source directory defined');
    }

    return $sPath ? $this->sourceDir->getDirectory($sPath) : $this->sourceDir;
  }

  /**
   * Get a file relative to the source file's directory
   * @param string $sPath
   * @return fs\file
   */
  public function getSourceFile($sPath = '') {

    if ($sPath) {

      $result = $this->getManager(static::FILE_MANAGER)->getFile($sPath, $this->getSourceDirectory());
    }
    else {

      $result = $this->getFile();
    }

    return $result;
  }

  protected function getClass(dom\handler $doc) {

    if (!$sResult = $doc->getRoot()->readx('@build:class', array(self::BUILD_PREFIX => self::BUILD_NS), false)) {

      $sResult = $this->readArgument('cache/class');
    }

    return $sResult;
  }

  /**
   * @param common\_window $window
   */
  public function setWindow(common\_window $window) {

    $this->window = $window;
  }

  public function getSchema() {

    $this->launchException('No schema defined');
  }

  /**
   *
   * @return common\_window
   */
  public function getWindow($bDebug = true) {

    if (!$this->window) {

      if ($bDebug) $this->throwException('No window defined');
      $result = null;
    }
    else {

      $result = $this->window;
    }

    return $result;
  }

  protected function getTemplatePath() {

    if (!$sResult = $this->readArgument('template', null, false)) {

      $sResult = static::PHP_TEMPLATE;
    }

    return $sResult;
  }

  public function build() {

    return $this->buildDefault();
  }
/*
  public function reflect() {

    return $this->reflectMain($this->getDocument());
  }
*/
  protected function buildDefault(common\_window $window = null) {

    $file = $this->getFile();
    $doc = $this->getDocument();
    $cached = $this->loadTarget($doc, $file);

    $mContent = $this->reflectMain($doc, $file, $window);
    $content = $this->buildInstanciation($mContent);

    $this->loadLog($doc);

    return $this->createFile($cached, $content);
  }

  /**
   *
   * @param \sylma\storage\fs\file $file
   * @param \sylma\dom\document $doc
   * @return dom\handler
   */
  protected function buildSimple($mContent, common\_window $window = null) {

    if (!$window) $window = $this->getWindow();

    $window->setReturn($mContent);

    return $this->buildWindow($window);
  }

    /**
   *
   * @param \sylma\storage\fs\file $file
   * @param \sylma\dom\document $doc
   * @return dom\handler
   */
  protected function buildInstanciation($mContent, common\_window $window = null) {

    if (!$window) $window = $this->getWindow();

    $this->createInstanciation($window, array($mContent));

    return $this->buildWindow($window);
  }

  protected function buildWindow(common\_window $window) {

    try {

      $result = $window->asDOM();
    }
    catch (core\exception $e) {

      //dsp($window->asArgument());
      $e->addPath($this->getSourceFile() ? $this->getSourceFile()->asToken() : '[no-file]');
      throw $e;
    }

    return $result;
  }

  protected function reflectMain(dom\document $doc, fs\file $file = null, common\_window $window = null) {

    try {

      $reflector = $this->buildReflector($window);
      $mContent = $this->parseReflector($reflector, $doc);

      $this->finishReflector($reflector);
    }
    catch (core\exception $e) {

      $this->log($this, $e->getMessage());
      $this->loadLog($doc);

      $this->catchException($e, $file);
      $mContent = null;
    }

    return $mContent;
  }

  public function finishReflector($reflector) {

    $reflector->onFinish();
  }

  protected function catchException(core\exception $e, fs\file $file = null) {

    if ($this->useLog()) $this->getLogger()->addException($e->getMessage());

    if ($file) $e->addPath($file->asToken());
    else $e->addPath ('No file defined');

    if ($this->throwExceptions()) throw $e;
    else $e->save(false);
  }

  protected function showResult($content) {

    if ($this->readArgument('debug/show')) {

      dsp($this->getFile()->asToken());
      dsp($content);
    }
  }

  protected function createFile(fs\editable\file $result, $content) {

    $this->showResult($content);

    $template = $this->getTemplate($this->getTemplatePath());

    $sContent = $template->parseDocument($content, false);
    $result->saveText($sContent);

    return $result;
  }

  public function addDependency(fs\file $file, $bScript = false) {

    $sFile = (string) $file;
    $sCurrent = $this->getFile('', false);

    if (!in_array($sFile, $this->aDependencies) && (!$sCurrent || $sFile !== $sCurrent)) {

      $this->aDependencies[$bScript ? 'script' : 'file'][] = $sFile;
    }
  }

  public function getDependencies() {

    return $this->aDependencies;
  }

  protected function loadTarget(dom\document $doc, fs\file $file) {

    if ($sTarget = $doc->getRoot()->readx('@build:target', array('build' => self::BUILD_NS), false)) {

      if ($sTarget{0} == '[') {

        switch (substr($sTarget, 1, -1)) {

          case 'current()' : $sTarget = (string) $file->getParent(); break;
          default : $this->throwException(sprintf('Cannot handler build option %s', $sTarget));
        }
      }

      $dir = $this->getManager('fs/editable')->getDirectory($sTarget);
      $result = $dir->getFile($file->getName() . '.php', fs\resource::DEBUG_EXIST);
    }
    else {

      $result = $this->loadSelfTarget($file);
    }

    return $result;
  }

  protected function loadSelfTarget(fs\file $file) {

    return $this->getManager()->getCachedFile($file);
  }

  protected function buildReflector(common\_window $window = null) {

    $result = $this->createReflector();

    if (!$window) {

      $window = $this->createDocumentWindow();
    }

    $this->setWindow($window);

    return $result;
  }

  /**
   *
   * @return common\_window
   */
  protected function createDocumentWindow() {

    $root = $this->getDocument()->getRoot();
    $sOutput = $root->readx('@build:output', $this->getNS(), false);

    return $this->createWindow($sOutput);
  }

  /**
   * @param $sOutput A value in the following list : array, result, dom or empty
   * @return common\_window
   */
  protected function createWindow($sOutput = '') {

    $sInstance = $this->getClass($this->getDocument());
    $result = $this->create('window', array($this, $this->getArgument(static::WINDOW_ARGS), $sInstance));

    switch ($sOutput) {

      case 'array' : $iValue = $result::MODE_ARRAY; break;
      case 'result' : $iValue = $result::MODE_RESULT; break;
      case 'dom' :
      default :

        $iValue = $result::MODE_DOM;
        break;
    }

    $result->setMode($iValue);

    return $result;
  }

  protected function createReflector() {

    $class = $this->getFactory()->findClass('elemented');

    return $this->create('elemented', array($this, null, $class));
  }

  protected function parseReflector(reflector\domed $reflector, dom\document $doc) {

    return $reflector->parseRoot($doc->getRoot());
  }

  public function setReturn($sValue) {

    $this->sReturn = $sValue;
  }

  protected function getReturn() {

    if (is_null($this->sReturn)) {

      $this->sReturn = $this->getDocument()->getRoot()->readx('@build:return', $this->getNS(), false);
    }

    return $this->sReturn;
  }

  protected function createInstanciation(common\_window $window, array $aArguments) {

    switch ($this->getReturn()) {

      case 'result' : $sReturn = current($aArguments); break;
      case 'array' : $sReturn = $window->argToInstance($aArguments); break;

      case '' :
      case 'default' :

        $sReturn = $window->createInstanciate($window->getSelf()->getInstance(), $aArguments);
        break;

      default :

        $this->launchException("Unknown @return value : " . $this->getReturn());
    }

    $window->setReturn($sReturn);
  }

  /**
   * @usedby sylma\template\binder\component\Event::addToClass()
   */
  public function getCurrentElement($bDebug = false) {

    if (!$this->aElements && $bDebug) {

      $this->launchException('No element defined');
    }

    return end($this->aElements);
  }

  /**
   * @usedby \sylma\template\parser\component\element\Domed::start()
   */
  public function startElement(template\element $el) {

    //$this->startComponentLog($el, $el->asToken());
    $this->aElements[] = $el;
  }

  public function stopElement() {

    //$this->stopComponentLog();
    array_pop($this->aElements);
  }

  public function getMode() {

    return $this->sMode;
  }

  public function setMode($sMode) {

    $this->sMode = $sMode;
  }

  public function throwExceptions($mValue = null) {

    if (!is_null($mValue)) $this->bThrow = $mValue;

    return $this->bThrow;
  }
}
