<?php

namespace sylma\modules\html\context;
use sylma\core, sylma\dom, sylma\storage\fs;

class CSS extends Basic implements dom\domable {

  const EXTENSION = 'css';

  protected $aImports = array();

  public function asDOM($sParentNamespace = '') {

    $result = null;

    if ($aStyles = $this->loadContent()) {

      $bChildren = false;
      $doc = $this->buildDocument($aStyles, \Sylma::read('namespaces/html'), $bChildren);
      $result = $bChildren ? $doc->getChildren(): $doc->getRoot();
    }

    return $result;
  }

  protected function addFile(fs\file $file, $bReal = false) {

    $aResult = array('link' => array(
      '@href' => $bReal ? '/' . $file->getRealPath() : (string) $file,
      '@type' => 'text/css',
      '@media' => 'all',
      '@rel' => 'stylesheet',
    ));

    return $aResult;
  }

  protected function readFile(fs\file $file) {

    try {

      $sResult = $this->parse(parent::readFile($file), $file->getParent());

    } catch (core\exception $e) {

      $e->addPath($file->asToken());
      throw $e;
    }

    return "/** @file $file **/\n\n" . $sResult;
  }

  protected function getCache(array $aFiles, $aContent) {

    if ($aImports = $this->aImports) {

      array_unshift($aContent, implode("\n", $aImports));
    }

    return parent::getCache($aFiles, $aContent);
  }

  protected function parse($sValue, fs\directory $dir) {

    return $this->parseImports($this->parseURL($sValue, $dir));
  }

  protected function parseURL($sValue, fs\directory $dir) {

    $fs = \Sylma::getManager('fs');

    $sResult = preg_replace_callback("/url\((?:'|\")?([^\)'\"]+)('|\")?\)/", function($matches) use ($fs, $dir) {

      $match = $matches[1];
      
      // check only local path
      if (!preg_match('`https?://`', $match)) {

        if ($match{0} === '/') {

          $sURL = $match;
        }
        else {
          
          // extract arguments
          preg_match('`([^\?#]+)([\?#].+)?`', $match, $matches);
          
          $path = $matches[1];
          $arguments = '';

          if ( isset($matches[2]) ) $arguments = $matches[2];
          
          // check for file existence
          // concat final url
          $sURL = (string) $fs->getFile($path, $dir, false) . $arguments;

          if (\Sylma::isAdmin() && !$sURL) {

            dsp('Cannot find file : ' . $match);
          }
        }
      }
      else {

        $sURL = $match;
      }

      return "url('$sURL')";

    }, $sValue);

    return $sResult;
  }

  protected function parseImports($sValue) {

    $sImport = '';

    $sValue = preg_replace_callback("/@import [^;]+;\s*/", function($aMatches) use (&$sImport) {

      $sImport = trim($aMatches[0]);

      return '';

    }, $sValue);

    if ($sImport) {

      $this->aImports[] = $sImport;
    }

    return $sValue;
  }

  protected function addText($sValue) {

    return array('style' => array(
      '@type' => 'text/css',
      $sValue,
    ));
  }
}

