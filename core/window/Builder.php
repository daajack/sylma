<?php

namespace sylma\core\window;
use sylma\core, sylma\storage\fs, sylma\template\binder, sylma\modules\less, sylma\modules\html;

class Builder extends core\module\Domed {

  const EXTENSION_DEFAULT = 'html';

  public function __construct(core\argument $args) {

    $this->setSettings($args);

    $this->setDirectory(__FILE__);
  }

  public function createWindow(fs\file $file, core\argument $images) {

    if (in_array($file->getExtension(), $images->query('extensions'))) {

      $window = $this->create('images', array($this->getInitializer(), $images));
      $sResult = $this->loadWindowFile($file, $window);
    }
    else {

      $sResult = $this->createWindowFile($file);
    }

    return $sResult;
  }

  protected function getInitializer() {

    return $this->getManager('init');
  }

  protected function createWindowFile(fs\file $file) {

    $sResult = '';

    switch ($file->getExtension()) {

      case 'php' :

        $this->throwException('Cannot read php files');

      default :

        $window = $this->create('window', array($this->getInitializer()));
        $sResult = $this->loadWindowFile($file, $window);

      break;
    }

    return $sResult;
  }

  protected function loadWindowFile(fs\file $file, core\window\file $window) {

    $window->setFile($file);
    return $window->asString();
  }
  
  protected function prepareRequest(core\request $path) {
    
    $result = false;
    
    if (\Sylma::read('locale/enable')) {
      
      $result = $this->getManager('locale')->loadRequest($path);
    }
    
    return $result;
  }
  
  public function buildWindow(core\request $path, core\argument $exts, core\argument $fusion, $bUpdate = null, $bRun = true) {
    
    $result = '';
    $redirect = $this->prepareRequest($path);

    if ($redirect) 
    {
      $redirect->run();
    }
    else
    {
      $this->setSettings($exts);

      $sExtension = strtolower($path->getExtension());
      if (!$sExtension) $sExtension = self::EXTENSION_DEFAULT;

      $settings = $this->get($sExtension);
      $sCurrent = (string) $path;

      $path->parse();

      $aPaths = $this->buildWindowStack($settings, $sCurrent);
      $aPaths[] = (string) $path->asFile();

      $this->prepareStack($aPaths);
      $result = $this->runWindow($aPaths, $path, $fusion, $bUpdate, $bRun);
    }
    
    return $result;
  }
  
  protected function prepareStack(array &$aPaths) 
  {
    $bAccess = true;

    foreach ($aPaths as $sFile) {

      $file = $this->getFile($sFile, false);

      if (!$file || !$file->checkRights(\Sylma::MODE_EXECUTE)) {

        $bAccess = false;
        break;
      }
    }

    if (!$bAccess) {

      $this->getInitializer()->send404();

      $row = $this->createArgument(array($this->get('error')));

      $aPaths = $this->buildWindowStack($row, '');
      $aPaths[] = $this->read('error/path');
    }
  }
  
  protected function runWindow(array $aPaths, $path, core\argument $fusion, $bUpdate, $bRun) 
  {
    $aPaths = array_reverse($aPaths);
    $sMain = array_pop($aPaths);

    $window = $this->getFile($sMain);

    $args = $path->getArguments();
    $post = \Sylma::getManager('init')->loadPost(true);
    $contexts = $this->prepareContexts($fusion);

    $args->set('sylma-paths', $aPaths);

    $builder = $this->getManager(self::PARSER_MANAGER);

    return $builder->load($window, array(
      'arguments' => $args,
      'post' => $post,
      'contexts' => $contexts,
    ), $bUpdate, $bRun, true);
  }

  protected function prepareContexts(core\argument $fusion) {

    $messages = $this->initMessages();

    $load = new binder\context\Load;
    $js = new html\context\JS(array(
      //'classes' => new \sylma\template\binder\context\Classes(),
      'load' => $load,
    ), $fusion);

    $result = $this->createArgument(array(
      'title' => array(),
      'css' => new less\Context(array(), $fusion, $js),
      'js-common' => new html\context\JS(array(), $fusion),
      'js' => $js,
      'errors' =>  $messages,
    ));

    $load->set('objects', new \sylma\template\binder\context\Objects());

    return $result;
  }

  protected function initMessages() {

    if (!$messages = $this->getManager('parser')->getContext('errors', false)) {

      $messages = new \sylma\modules\html\context\Messages;
      $this->getManager('parser')->setContext('errors', $messages);
    }

    return $messages;
  }

  protected function getErrorWindow() {

    return $this->getFile($this->read('error/window'));
  }

  protected function getErrorPath() {

    return $this->read('error/path');
  }

  protected function buildWindowStack(core\argument $arg, $sPath) {

    $aResult = array();

    do {

      if ($content = $this->lookupRoute($arg, $sPath)) {

        $aResult[] = $content->read('action');
        $arg = $content->get('sub', false);
      }
      else {

        $arg = null;
      }

    } while ($arg);

    return array_filter($aResult);
  }

  /**
   * @return \sylma\core\argument
   */
  protected function lookupRoute(core\argument $args, $sCurrent) {

    $result = null;
    $iLast = 0;
    $iKey = 0;

    foreach ($args as $test=>$alt) {

      if (is_object($alt)) {

        $iWeight = $this->testRoute($alt, $sCurrent, $iKey + 1);

        if ($iWeight > $iLast) {

          $result = $alt;
          $iLast = $iWeight;
        }
      }

      $iKey++;
    }

    return $result;
  }

  protected function testRoute(core\argument $alt, $sCurrent, $iKey) {

    $sPattern = $alt->read('pattern', false);

    $iResult = 0;

    if (!$sPattern || preg_match($sPattern, $sCurrent)) {

      $iWeight = $alt->read('weight', false);
      $iResult = $iWeight ? $iWeight : $iKey;
    }

    return $iResult;
  }

  public function loadObject(core\request $path, $window, core\argument $fusion) {

    $this->prepareRequest($path);
    $path->parse();
    
    $window->setScript($path, $this->prepareContexts($fusion));

    return $window->asString();
  }
}
