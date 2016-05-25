<?php

namespace sylma\storage\fs\basic;
use sylma\core, sylma\dom, sylma\storage\fs, sylma\core\functions;

class Directory extends Resource implements fs\directory {

  const NS = 'http://www.sylma.org/storage/fs/basic/directory';
  const USER_CONTROLER = 'user';
  CONST FILE_ALIAS = 'file';
  const DIRECTORY_ALIAS = 'directory';

  public $aDirectories = array();
  private $aFiles = array();
  private $settings = null;

  private $aChildrenRights = null;

  public function __construct($sName, fs\directory $parent = null, array $aRights = array(), fs\Manager $controler = null) {

    $this->sFullPath = $parent ? $parent. '/' .$sName : '';
    $this->controler = $controler;
    $this->parent = $parent;
    $this->sName = $sName;

    // try open_basedir restriction

    try {

      $this->bExist = is_dir($this->getRealPath());
    }
    catch (core\exception $e) {

      $this->bExist = true;
    }

    $this->aRights = $this->aChildrenRights = $aRights;

    if ($this->doExist()) {

      $this->loadRights();
    }
  }

  private function getChildrenRights() {

    return $this->aChildrenRights;
  }

  protected function setSettings(fs\security\manager $settings) {

    $this->settings = $settings;
  }

  /**
   * Get security XML_Document (eg: directory.sml)
   * @param boolean $bRecursive Get last setting file from parents
   * @return XML_SFile|null
   */
  public function getSettings($bRecursive = false) {

    if ($bRecursive) {

      if ($this->getParent()) return $this->getParent()->getSettings(true);
      else $this->throwException(t('No security file in parent directory'));
    }

    return $this->settings;
  }

  private function loadRights() {

    if ($this->getManager()->mustSecure()) {

      $settings = $this->getManager()->createSettings($this);
      $this->setSettings($settings);

      // self rights
      $aRights = $this->setRights($this->getSettings()->getDirectory());

      // children rights
      if ($aChildrenRights = $this->getSettings()->getPropagation()) {

        $this->aChildrenRights = $aChildrenRights;

      } else {

        $this->aChildrenRights = $aRights;
      }
    }
  }

  protected function scan() {

    $aChildren = scandir($this->getRealPath(), 0);

    foreach ($aChildren as $sFile) {

      if ($sFile != '.' && $sFile != '..') {

        if (!$this->getFile($sFile, self::DEBUG_NOT)) {

          $this->getDirectory($sFile);
        }
      }
    }
  }

  /**
   * Get a tree of directories and files
   *
   * @param \sylma\core\argument $arg
   * @return array of fs\directory and fs\file
   */
  public function browse(core\argument $arg = null) {

    $tmp = $this->getManager()->getArgument('browse');
    $tmp->merge($arg);
    $arg = $tmp;

    $bDepth = $arg->read('depth');
    $aIncludes = $arg->query('includes', false);
    $aExcludes = $arg->query('excludes', false);

    return $this->browseRun($bDepth, $aIncludes, $aExcludes);
  }

  protected function browseRun($bDepth, array $aIncludes, array $aExcludes) {

    $this->scan();

    $aResult = $bDepth ? array() : $this->asArray();

    $aDirectories = $this->getDirectories($aExcludes);

    usort($aDirectories, function($a, $b) {

      $aname = $a->getName();
      $bname = $b->getName();
      
      return ($aname[0] < $bname[0]) ? -1 : 1;
    });

    if ($bDepth) {

      $aResult['directory'] = array();

      foreach ($aDirectories as $dir) {

        $aResult['directory'][] = $dir->browseRun(true, $aIncludes, $aExcludes);
      }
    }
    else {

      $aResult['directory'] = $aDirectories;
    }

    $aResult['file'] = $this->getFiles($aIncludes);

    return $aResult;
  }

  /**
   * Get a list of files inside this directory and optionally sub-directories
   *
   * @param array $aExcludes File's extensions to be included, if empty all are used
   * @param string $sPreg Regular expression validation
   * @param integer $bDepth If TRUE, get sub-directories files
   * @return array of fs\file
   */
  public function getFiles(array $aIncludes = array(), array $aExcludes = array(), $bDepth = false) {

    $this->scan();

    $aResult = array();

    // Files of current directory

    if ($aExcludes || $aIncludes) {

      foreach ($this->aFiles as $file) {

        $bValid = true;
        $sFile = (string) $file;

        foreach ($aIncludes as $sInclude) {

          if (!$bValid = preg_match($sInclude, $sFile)) {

            break;
          }
        }

        if ($bValid) {

          foreach ($aExcludes as $sExclude) {
//dsp(preg_match($sExclude, $sFile), $sExclude, $sFile);
            if (!$bValid = !preg_match($sExclude, $sFile)) {

              break;
            }
          }
        }

        if ($bValid) {

          $aResult[] = $file;
        }
      }

    } else {

      $aResult = array_values($this->aFiles);
    }

    // Recursion in sub-directory

    if ($bDepth) {

      foreach ($this->getDirectories() as $dir) {

        $aResult = array_merge($aResult, $dir->getFiles($aIncludes, $aExcludes, $bDepth));
      }
    }

    return $aResult;
  }

  /**
   * Unload then reload file
   */
  public function updateFile($sName) {

    if (array_key_exists($sName, $this->aFiles)) unset($this->aFiles[$sName]);

    return $this->getFile($sName, self::DEBUG_NOT);
  }

  /**
   * Unload then reload directory
   */
  public function updateDirectory($sName) {

    if (array_key_exists($sName, $this->aDirectories)) unset($this->aDirectories[$sName]);

    return $this->getDirectory($sName, self::DEBUG_NOT);
  }

  public function getFreeFile($sName, $iDebug = self::DEBUG_LOG) {

    $result = null;

    if (array_key_exists('file', $this->aFiles)) {

      $result = $this->aFiles[$sName];
    }
    else {

      $result = $this->loadFreeFile($sName, $iDebug);
    }

    return $result;
  }

  protected function loadFreeFile($sName, $iDebug) {

    $result = null;

    $file = $this->getManager()->create('file', array(
        $sName,
        $this,
        $this->getRights(),
        $iDebug,
      ));

    if ($file->doExist() || $iDebug & self::DEBUG_EXIST) {

      $result = $file;
    }

    if (!$file->doExist() && $iDebug & self::DEBUG_LOG) {

      $this->throwException(t('File does not exist'));
    }

    if ($result) $this->aFiles[$sName] = $result;

    return $result;
  }

  /**
   * Build a file, check existenz and right access
   * If @controler user is not set, then file is returned without rights check but not cached
   *
   * @param $sName The name + extension of the file
   * @param $iDebug send an error message if no access is found see @class fs\directory
   * @return null|fs\file the file requested
   */
  public function getFile($sName, $iDebug = self::DEBUG_LOG) {

    $result = null;

    if ($sName && is_string($sName)) {

      if (array_key_exists($sName, $this->aFiles)) {

        // yet builded
        $file = $this->aFiles[$sName];

        if (!$file) {

          $this->throwException(sprintf('File lost : %s', (string) $this . '/' . $sName));
        }

        $result = $file;
      }
      else {

        // not yet builded, build it
        $file = $this->loadFreeFile($sName, $iDebug);

        if ($file) {

          $this->secureFile($file);
          $result = $file;
        }
        else {

          if ($iDebug & self::DEBUG_EXIST) $result = $file;
        }
      }
    }

    return $result;
  }

  protected function secureFile(fs\file $file) {

    //dspf($file);

    if ($this->getManager()->mustSecure()) {

      if (!$this->getSettings() or !$aRights = $this->getSettings()->getFile($file->getName())) {

        $aRights = $this->getChildrenRights();
      }

      $file->setRights($aRights);
    }
    else {

      $file->setRights($this->getChildrenRights());
    }
  }

  public function getDirectory($sName, $iDebug = self::DEBUG_LOG) {

    $result = null;

    if (!$sName) {

      $this->throwException('Cannot get a directory without name');
    }

    if ($sName == '.') {

      $result = $this;
    }
    else if ($sName == '..') {

      $result = $this->getParent();
    }
    else {

      if (array_key_exists($sName, $this->aDirectories)) {

        // yet builded
        $result = $this->aDirectories[$sName];
      }
      else {

        // not yet builded, build it
        $result = $this->loadDirectory($sName, $iDebug);
      }

    }

    if (!$result && ($iDebug & self::DEBUG_LOG)) {

      $this->throwException(sprintf('@directory %s does not exists in %s', $sName, $this->getRealPath()));
    }

    return $result;
  }

  public function getDirectories(array $aExcluded = array()) {

    $aResult = array();

    foreach ($this->aDirectories as $dir) {

      $bValid = true;

      foreach ($aExcluded as $sExclude) {

        switch ($sExclude{0}) {

          case '/' : $bValid = $sExclude !== $dir->getFullPath(); break;
          default : $bValid = $sExclude !== $dir->getName(); break;
        }
      }

      if ($bValid) {

        $aResult[] = $dir;
      }
    }

    return $aResult;
  }

  /**
   *
   * @param type $sName
   * @param type $iDebug
   * @return fs\directory|null
   */
  protected function loadDirectory($sName, $iDebug) {

    $result = null;

    $dir = $this->getManager()->create('directory', array(
      $sName,
      $this,
      $this->getChildrenRights(),
    ));

    if ($dir->doExist()) {

      $result = $dir;
      $this->aDirectories[$sName] = $result;
    }
    else if ($iDebug & self::DEBUG_EXIST) {

      $result = $dir;
    }

    return $result;
  }

  public function getDistantFile(array $aPath, $iDebug = self::DEBUG_LOG) {

    $result = null;

    if ($aPath) {

      if (count($aPath) == 1) {

        return $this->getFile($aPath[0], $iDebug);

      } else {

        $sName = array_shift($aPath);

        $dir = $this->getDirectory($sName, $iDebug);

        if ($dir) {

          $result = $dir->getDistantFile($aPath, $iDebug);
        }
        else if ($iDebug & self::DEBUG_LOG) {

          $this->throwException(sprintf('Directory %s does not exists', $sName));
        }
      }
    }

    return $result;
  }

  public function getDistantDirectory($mPath, $iDebug = self::DEBUG_LOG) {

    $result = null;
    if (is_string($mPath)) $mPath = explode('/', $mPath);

    if ($mPath) {

      $sName = array_shift($mPath);

      if ($sub = $this->getDirectory($sName, $iDebug)) {

        $result = $sub->getDistantDirectory($mPath, $iDebug);
      }
    } else {

      $result = $this;
    }

    return $result;
  }

  public function getSystemPath() {

    return $this->getManager()->getSystemPath() . '/' . $this->getRealPath();
  }

  public function getRealPath() {

    return $this->getParent() ?
           $this->getParent()->getRealPath() . '/' . $this->getName() :
           $this->getManager()->getPath() . $this->getName();
  }

  public function asToken() {

    return '@directory ' . (string) $this;
  }

  public function asArray() {

    $sName = $this->getParent() ? $this->getName() : '/';
    $sPath = $this->getParent() ? $this->getFullPath() : '/';

    require_once('core/functions/Global.php');

    return array(
      'path' => $sPath,
      'owner' => $this->getOwner(),
      'group' => $this->getGroup(),
      'mode' => $this->getMode(),
      'read' => functions\booltostr($this->checkRights(\Sylma::MODE_READ)),
      'write' => functions\booltostr($this->checkRights(\Sylma::MODE_WRITE)),
      'execution' => functions\booltostr($this->checkRights(\Sylma::MODE_EXECUTE)),
      'name' => $sName,
    );
  }

  public function __toString() {

    return $this->getFullPath();
  }
}

