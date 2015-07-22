<?php

namespace sylma\modules\explorer;
use sylma\core;

class Explorer extends core\module\Filed
{
  public function __construct() {

    $this->setDirectory(__FILE__);
  }

  public function initDatas($sPath, $bEmpty = false) {

    return $bEmpty ? $this->buildDirectory($sPath) : array();
  }

  public function buildDirectory($sPath, array $aIncludes = array(), $aExcludes = array()) {

    $dir = $this->getDirectory($sPath);

    foreach ($aIncludes as &$sInclude) {

      $sInclude = "`$sInclude`";
    }
/*
    foreach ($aIncludes as &$sInclude) {

      $sInclude = "`" . $this->replaceFilter($sInclude) . "`";
    }
*/
    foreach ($aExcludes as &$sExclude) {

      $sExclude = "`" . $this->replaceFilter($sExclude) . "`";
    }

    if ($aIncludes) {

      $aContent = $dir->getFiles($aIncludes, $aExcludes, true);

      $aResult = array(
        //'file' => array_slice($aContent, 0, 10),
        'file' => $aContent,
      );
    }
    else {

      $aResult = $dir->browse(new core\argument\Readable(array(
        'depth' => false,
        'includes' => $aIncludes,
      )));

      if ($parent = $dir->getParent()) {

        $aParent = $parent->asArray();
        $aParent['name'] = '..';
        array_unshift($aResult['directory'], $aParent);
      }
    }

    return $aResult;
  }

  protected function replaceFilter($sValue) {

    return str_replace(array(
      '.',
      '*',
    ), array(
      '\\.',
      '.+',
    ), $sValue);
  }
}
