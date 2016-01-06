<?php

namespace sylma\parser\compiler;
use sylma\core, sylma\dom, sylma\storage\fs;

abstract class Manager extends core\module\Domed {

  const EXTENSION_DEFAULT = '.php';
  const ARGUMENTS = 'manager.xml';

  protected $baseDirectory = null;
  protected $aChecked = array();
  public static $aLoaded = array();

  const DEPENDENCY_SUFFIX = '.dependencies.php';

  /**
   * order of arguments merge : domed, argument, directory
   * @param $arg
   */
  public function __construct(core\argument $arg = null) {

    //$this->loadDefaultArguments();
    //if ($arg) $this->setArguments($arg);
/*
    if ($arg && $sDirectory = $arg->read('directory', null, false)) {

      $dir = $this->getManager(self::FILE_MANAGER)->getDirectory($sDirectory);
      $this->setDirectory($dir);
    }

    if ($this->getDirectory('', false)) {

      if ($file = $this->getFile(static::ARGUMENTS, false)) {

        $manager = $this->getManager(self::ARGUMENT_MANAGER);
        $this->setArguments($manager->createArguments($file));
      }
    }
 */
  }

  public function getClassName($sClass) {

    $sClass = 'classes/' . $sClass . '/name';

    return $this->readArgument($sClass);
  }

  protected function getCachedDirectory(fs\file $file) {

    $fs = $this->getControler('fs/cache');

    return $fs->getDirectory()->addDirectory((string) $file->getParent());
  }

  /**
   * @return \sylma\storage\fs\editable\file
   */
  public function getCachedFile(fs\file $file, $sExtension = self::EXTENSION_DEFAULT, $iDebug = fs\resource::DEBUG_EXIST) {

    $sName = $file->getName() . $sExtension;

    return $this->getCachedDirectory($file)->getFile($sName, $iDebug);
  }

  public function load(fs\file $file, array $aArguments = array(), $bUpdate = null, $bRun = true, $bExternal = false) {

    $result = null;
    $cache = $this->loadCache($file, $bUpdate);

    if ($this->readArgument('debug/run') && $bRun) {

      if (!$cache) {

        $this->launchException('No cache file found for ' . $file, get_defined_vars());
      }

      $result = $this->createCache($cache, $aArguments, $bExternal);

      if (\Sylma::isAdmin()) {

        $sPath = (string) $cache;
        if (isset(self::$aLoaded[$sPath])) self::$aLoaded[$sPath]++;
        else self::$aLoaded[$sPath] = 1;
      }
    }

    return $result;
  }

  /**
   * Load cache file. Built it if it doesn't exists
   *
   * @param $file
   * @return fs\file
   */
  protected function loadCache(fs\file $file, $bUpdate = null) {

    if (!$file->checkRights(\Sylma::MODE_EXECUTE)) {

      $this->launchException('Unauthorized access', get_defined_vars());
    }

    if ((!$result = $this->getCache($file, $bUpdate)) && $this->getControler('user')->isPrivate()) {

      if ($bUpdate !== false) {

        $result = $this->build($file, $file->getParent());
      }
    }

    return $result;
  }

  abstract function build(fs\file $file, fs\directory $dir);

  protected function createBuilder($sClass, fs\file $file = null, fs\directory $dir = null, core\argument $args = null, dom\document $doc = null) {

    //$class = $this->getFactory()->findClass($sClass);
    //$class->merge($args);
    $class = $args ? $args : $this->getFactory()->findClass($sClass);
    $result = $this->create($sClass, array($this, $file, $dir, $class, $doc));

    return $result;
  }

  /**
   * Search then read cache file if exists
   *
   * @param $file
   * @return fs\file
   */
  protected function getCache(fs\file $file, $bUpdate = null) {

    $result = null;

    $tmpDir = $this->getCachedDirectory($file);
    $tmpFile = null;

    if ($tmpDir) {

      $tmpFile = $this->getCachedFile($file, static::EXTENSION_DEFAULT, fs\resource::DEBUG_NOT);
    }

    if (\Sylma::isAdmin() && $bUpdate !== false) {

      $bUpdate = !$tmpFile || $bUpdate || $this->readArgument('debug/update') || $tmpFile->getUpdateTime() < $file->getUpdateTime();

      if (!$bUpdate) {

        $bUpdate = $this->checkDependencies($tmpDir, $file, $tmpFile->getUpdateTime());
      }

      if ($tmpFile && !$bUpdate) {

        $result = $tmpFile;
      }
    }
    else {

      $result = $tmpFile;
    }

    return $result;
  }

  protected function checkDependencies(fs\directory $dir, fs\file $file, $iCurrent) {

    $bResult = false;
    $deps = $dir->getFile($file->getName() . self::DEPENDENCY_SUFFIX, fs\resource::DEBUG_EXIST);

    if ($deps->doExist()) {

      $aDependencies = include($deps->getRealPath());
      //$iCurrent = $file->getUpdateTime();

      $bResult = $this->checkFileDependencies($aDependencies['file'], $iCurrent);

      if (!$bResult && !\Sylma::read('debug/dependency')) {

        $this->checkScriptDependencies($aDependencies['script']);
      }
    }

    return $bResult;
  }

  protected function checkFileDependencies(array $aFiles, $iCurrent) {

    $bResult = false;

    foreach($aFiles as $sDependency) {

      $dep = $this->getFile($sDependency);

      if ($dep->getUpdateTime() > $iCurrent) {

        $bResult = true;
        break;
      }
    }

    return $bResult;
  }

  protected function checkScriptDependencies(array $aFiles) {

    $builder = $this->getManager(self::PARSER_MANAGER);

    foreach($aFiles as $sDependency) {

      $dep = $this->getFile($sDependency);

      if (!in_array($dep, $this->aChecked)) {

        $this->aChecked[] = $dep;
        $builder->load($dep, array(), null, false);
        array_pop($this->aChecked);
      }
/*
      if ($dep->getUpdateTime() > $iCurrent) {

        $bResult = true;
      }
*/
    }
  }

  protected function buildDependencies(fs\directory $dir, fs\file $file, array $aDependencies) {

    $cache = $this->getCachedDirectory($file)->getFile($file->getName() . self::DEPENDENCY_SUFFIX, fs\resource::DEBUG_EXIST);
    $sContent = '<?php return ' . var_export($aDependencies, true) . ';';

    $cache->saveText($sContent);
  }

  /**
   * Create cache object
   *
   * @param $file Script
   * @param array $aArguments
   * @return \sylma\parser\cached\documented
   */
  protected function createCache(fs\file $file, array $aArguments, $bExternal = false) {

    return \Sylma::includeFile($file->getRealPath(), $aArguments, $bExternal);
  }
}