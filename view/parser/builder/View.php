<?php

namespace sylma\view\parser\builder;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\storage\fs, sylma\parser\languages\common, sylma\view\parser;

class View extends Variabled {

  const ARGUMENT_METHOD = 'getFirst';

  const DO_ARGUMENTS = '../do.xml';
  const VIEW_ARGUMENTS = '../view.xml';

  protected $reflector;
  protected $resourceWindow;

  public function build() {

    $this->setDirectory(__FILE__);

    $doc = $this->getDocument();

    $result = $this->buildView($doc, $this->loadSelfTarget($this->getFile()));

    return $result;
  }

  public function getLogger($bDebug = true) {

    return parent::getLogger($bDebug);
  }

  protected function buildView(dom\handler $doc, fs\file $target, $sAlias = '') {

    $this->loadLogger();
    $sMode = $this->loadDocument($doc);

    $window = $this->prepareWindow($doc, $sMode);

    switch ($sMode) {

      case 'delete' :
      case 'insert' :
      case 'update' :

        $return = $this->buildDoView($doc, $window);
        break;

      case 'hollow' :
      case 'view' :

        $return = $this->buildDisplayView($doc, $window, $sAlias);
        break;

      default :

        $this->launchException(sprintf('Unexpected mode : ""%s', $sMode), get_defined_vars());
    }

    $result = $this->createFile($target, $return);

    $this->loadLog($doc);
    $this->clearLogger();

    return $result;
  }

  /**
   * @return common\_window
   */
  protected function prepareWindow(dom\handler $doc, $sMode) {

    $this->setDocument($doc, false);
    $window = $this->createDocumentWindow();

    switch ($sMode) {

      case 'delete' :
      case 'insert' :
      case 'update' :

        $this->setArguments(self::DO_ARGUMENTS);

        break;

      case 'hollow' :
      case 'view' :

        $this->setArguments(self::VIEW_ARGUMENTS);

        break;

      default :

        $this->launchException('Unexpected mode : ' . $sMode, get_defined_vars());
    }

    $this->prepareFormed($window);
    $window->createVariable('bSylmaExternal', 'php-boolean');

    return $window;
  }

  /**
   * @usedby \sylma\template\binder\component\Script::build()
   * @return common\_window
   */
  public function getResourceWindow() {

    if (!$this->resourceWindow) {

      $this->launchException('No resource window defined');
    }

    return $this->resourceWindow;
  }

  protected function buildDoView(dom\document $doc, common\_window $window) {

    $resourceWindow = $this->createWindow();
    $this->resourceWindow = $resourceWindow;

    $this->checkVariable($resourceWindow, 'contexts', '\\' . get_class($this->create('argument')));

    $content = $this->reflectMain($doc, $this->getFile(), $window);
    $return = $this->buildSimple($content, $window);

    return $return;
  }

  protected function buildDisplayView(dom\document $doc, common\_window $window, $sAlias) {

    $resourceWindow = $this->createWindow();
    $this->resourceWindow = $resourceWindow;

    $this->checkVariable($resourceWindow, 'contexts', '\\' . get_class($this->create('argument')));

    $file = $this->getResourceFile($this->getSourceFile(), $sAlias);
    $this->includeFile($file, $window);

    $content = $this->reflectMain($doc, $this->getFile(), $window);
    $return = $this->buildInstanciation($content, $window);

    $resources = $resourceWindow->asDOM();
    $this->createFile($file, $resources);

    return $return;
  }

  public function includeFile(fs\file $file, common\_window $window) {

    $call = $window->callFunction('require', 'php-string', array($file->getRealPath()));
    $window->add($call);
  }

  public function getResourceFile(fs\file $file, $sAlias) {

    $parser = $this->getManager(self::PARSER_MANAGER);
    $result = $parser->getCachedFile($file, ($sAlias ? '.' . $sAlias : '') . '-ext.php');

    return $result;
  }

  public function getExternal() {

    return $this->getWindow()->getVariable('bSylmaExternal');
  }

  protected function getReflector() {

    return $this->reflector;
  }

  protected function setReflector(parser\Elemented $reflector) {

    $this->reflector = $reflector;
  }

  protected function parseReflector(reflector\domed $reflector, dom\document $doc) {

    return $reflector->parseRoot($doc->getRoot(), $this->getMode());
  }

  public function loadDocument(dom\handler $doc) {

    if ($sMode = $this->loadMode($doc)) {

      $this->setMode($sMode);
    }
    else {

      $this->setMode(self::MODE_DEFAULT);
    }

    $this->importView($doc, $doc);

    return $this->getMode();
  }

  protected function importView(dom\node $source, dom\node $target) {

    if ($sPath = $source->readx('@extends', array(), false)) {

      $file = $this->getSourceFile($sPath);
      $new = $this->importDocument($file->asDocument(array(), \Sylma::MODE_EXECUTE, false), $file);
      $target->shift($new->getRoot()->getChildren());

      $this->importView($new, $target);
    }
  }

  protected function loadMode(dom\handler $doc) {

    return $doc->readx('@mode', array(), false);
  }

  public function callScript(fs\file $file, common\_window $window, $return = null, $bReturn = true) {

    $arguments = $window->getVariable('aSylmaArguments');

    //$closure = $window->createClosure(array($arguments));
    //$closure->addContent($window->callFunction('include', $return, array($file->getName())));

    $call = $window->createCall($window->getSylma(), 'includeFile', $return, array($file->getRealPath(), $arguments, $window->getVariable('bSylmaExternal')));

    if ($bReturn) {

      $result = $window->createAssign($window->getVariable('result'), $call);
    }
    else {

      $result = $call;
    }

    return $result;
  }

  public function aliasFromRequest(core\request $path) {

    return '';
  }

  public function asPath() {

    return $this->getSourceFile()->asPath();
  }
}

