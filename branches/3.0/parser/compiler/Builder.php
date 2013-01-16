<?php

namespace sylma\parser\compiler;
use sylma\core, sylma\parser, sylma\storage\fs, sylma\dom, sylma\parser\languages\php;

abstract class Builder extends Manager {

  const PHP_TEMPLATE = '/#sylma/parser/languages/php/source.xsl';
  const WINDOW_ARGS = 'classes/php';

  protected function getClass(dom\handler $doc) {

    if (!$sResult = $doc->getRoot()->readAttribute('class', null, false)) {

      $sResult = $this->readArgument('cache/class');
    }

    return $sResult;
  }

  protected function build(fs\file $file, fs\directory $dir) {

    $doc = $file->getDocument(array(), \Sylma::MODE_EXECUTE);

    $reflector = $this->createReflector($doc, $dir);
    $content = $this->runReflector($reflector, $this->getClass($doc), $file);

    if ($this->readArgument('debug/show')) {

      $tmp = $this->createDocument($window);
      echo '<pre>' . $file->asToken() . '</pre>';
      echo '<pre>' . str_replace(array('<', '>'), array('&lt;', '&gt'), $tmp->asString(true)) . '</pre>';
    }

    $result = $this->getCachedFile($file);
    $template = $this->getTemplate(static::PHP_TEMPLATE);

    $sContent = $template->parseDocument($content, false);
    $result->saveText($sContent);

    return $result;
  }

  protected function createReflector(dom\document $doc, fs\directory $base) {

    $result = $this->create('reflector', array($this, $doc, $base));

    return $result;
  }
  /**
   * Build window, then return result as PHP DOM Document
   *
   * @param $reflector
   * @param string $sInstance
   * @param $file
   *
   * @return dom\handler
   */
  protected function runReflector(parser\reflector\documented $reflector, $sInstance, fs\file $file) {

    try {

      $window = $this->create('window', array($reflector, $this->getArgument(static::WINDOW_ARGS), $sInstance));
      $reflector->setWindow($window);

      $result = $reflector->asDOM();
    }
    catch (core\exception $e) {

      $e->addPath($file->asToken());
      throw $e;
    }

    return $result;
  }
}