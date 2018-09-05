<?php

namespace sylma\modules\html\context;
use sylma\core, sylma\storage\fs, sylma\modules\less;

class Sass extends less\Context 
{
  protected function readFile(fs\file $file) {

    $sResult = '';

    switch ($file->getExtension()) {

      case 'scss' : $sResult = $this->parse($this->parseSass($file), $file->getParent()); break;
      default : $sResult = parent::readFile($file);
    }

    return $sResult;
  }

  protected function parseSass(fs\file $file) {
    
    require_once "scssphp/scss.inc.php";
    
    $sResult = '';

    $scss = new \Leafo\ScssPhp\Compiler;
    $scss->setFormatter( '\Leafo\ScssPhp\Formatter\Crunched' );
    $scss->setImportPaths( array( $file->getDirectory()->getRealPath() ) );
    
    try {
      
      $sResult = $scss->compile($file->execute());
    }
    catch (\Exception $e) {

      if (\Sylma::isAdmin()) {

        dsp('Sass error : ' . $e->getMessage() . ' in ' . $file->asToken());
      }
    }

    return $sResult;
  }
}

