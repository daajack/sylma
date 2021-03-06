<?php

use sylma\core, sylma\modules, sylma\dom, sylma\storage, sylma\core\argument;

class Sylma {

  const NS = 'http://www.sylma.org';

  const ROOT = sylma\ROOT; // ex: protected
  const PATH = sylma\SYLMA_PATH; // ex: /sylma
  const PATH_SYSTEM = sylma\SYSTEM_PATH;  // ex : /var/www/mysite or C:/xampp/htdocs/mysite
  const PATH_OPTIONS = '/core/sylma.yml';

  const PATH_CACHE = 'cache';
  const PATH_TRASH = 'trash';

  const MODE_EXECUTE = 1;
  const MODE_WRITE = 2;
  const MODE_READ = 4;

  const LOG_STATUT_DEFAULT = 'notice';

  protected static $SHORT_PATH;

  /**
   * @var core\argument
   */
  private static $settings = null;
  protected static $isAdmin = null;

  protected static $aManagers = array();
  protected static $aFiles = array();

  public static $sExceptionFile = 'core/exception/Basic.php';
  public static $sExceptionClass = '\sylma\core\exception\Basic';

  public static $sInitializerFile = 'core/Initializer.php';
  public static $sInitializerClass = '\sylma\core\Initializer';

  /**
   * Handle final result for @method render()
   * @var mixed
   */
  private static $result = null;

  public static function init($sServer = '') {

    self::$SHORT_PATH = substr(self::PATH, 1);

    require_once(self::$sExceptionFile);
    //xdebug_disable();
    set_error_handler(self::$sExceptionClass . "::loadError");

    $autoload = self::getManager('autoload');
    $autoload->register('sylma', self::$SHORT_PATH . '/');

    ini_set("default_charset", 'utf-8');
    mb_internal_encoding('utf-8');

    require_once(self::$sInitializerFile);

    //xdebug_start_code_coverage();

    $init = self::$aManagers['init'] = new self::$sInitializerClass;
    self::setManager('init', $init);

    try {

      self::$settings = $init->loadSettings($sServer, self::ROOT . self::PATH . self::PATH_OPTIONS);
      self::$result = $init->run(self::get('initializer'));
    }
    catch (core\exception $e) {

      $e->save();
      
      if (self::get('render')) 
      {
        self::get('render')->set('gzip', false);
      }

      self::$result = 'An error occured<br/><a href="/">Click here to go to homepage</a>';
      header('HTTP/1.0 404 Not Found');

      if (self::isAdmin()) {

        self::$result .= '<br/>' . $e;
      }
    }

    //var_dump(xdebug_get_code_coverage());
    //session_write_close();
  }

  public static function setManager($sName, $manager) {

    self::$aManagers[$sName] = $manager;
    return $manager;
  }

  public static function getManager($sName, $bLoad = true, $bDebug = true) {

    $manager = array_key_exists($sName, self::$aManagers) ? self::$aManagers[$sName] : null;

    if (!$manager && $bLoad) {

      $manager = self::loadManager($sName);
    }

    if (!$manager && $bLoad && $bDebug) {

      self::throwException(sprintf('Manager "%s" is not defined', $sName));
    }

    return $manager;
  }

  public static function getManagers() {

    return self::$aManagers;
  }

  public static function setManagers(array $aManagers) {

    self::$aManagers = $aManagers;
  }

  protected static function loadManager($sName) {

    $result = null;

    switch ($sName) {

      case 'autoload' :

        self::load('/core/Autoload.php');
        $result = new core\Autoload();

      break;

      /** Parsers **/

      case 'parser' :

        $result = new \sylma\parser\Manager;

      break;

      /** Others **/

      case 'fs/editable' :

        $result = new storage\fs\Manager(self::ROOT, true, true, true, 'editable');
        $result->loadDirectory();

      break;

      case 'fs/cache' :

        $result = new storage\fs\Manager(self::PATH_CACHE, true, true, false, 'cache');
        $result->loadDirectory('');

      break;

      case 'fs/trash' :

        $result = new storage\fs\Manager(self::PATH_TRASH, true, true, false, 'trash');
        $result->loadDirectory('');

      break;

      case 'fs/tmp' :

        $result = new storage\fs\Manager(self::read('directory/tmp'), true, true, false, 'tmp');
        $result->loadDirectory('');

      break;

      case 'fs/root' :

        $result = new storage\fs\Manager('/', true, true, false, '/');
        $result->loadDirectory('');

      break;

      case 'dom' :

        $result = new dom\Controler;

      break;

      case 'user' :

        $result = new core\user\Manager;
        $result = $result->getUser();

      break;

      case 'formater' :

        $result = new modules\formater\Controler;

      break;

      case 'redirect' :

        $init = self::getManager('init');
        $result = $init->loadRedirect();

      break;

      case 'mysql' :

        $result = new storage\sql\Manager(new core\argument\Readable(self::get('database')->query()));

      break;

      case 'locale' :

        $result = self::getManager('init')->create('locale', array(self::get('locale')));

      break;
    }

    if ($result) {

      self::setManager($sName, $result);
    }

    return $result;
  }

  public static function getSettings($sPath = '') {

    if ($sPath) return self::getSettings()->get($sPath);
    else return self::$settings;
  }

  public static function setSettings(core\argument $settings) {

    self::$settings = $settings;
  }

  public static function isAdmin() {

    if (self::$isAdmin === null) {
      
      $bResult = self::read('debug/enable');
      
      if (!$bResult and $user = self::getManager('user', false)) {

        $bResult = $user->getName() === 'root';
      }
    }
    else {

      $bResult = self::$isAdmin;
    }

    return $bResult;
  }

  public static function read($sPath = '', $bDebug = true) {

    if (self::getSettings()) return self::getSettings()->read($sPath, $bDebug);

    return false;
  }

  public static function get($sPath = '', $bDebug = true) {

    if (self::getSettings()) return self::getSettings()->get($sPath, $bDebug);

    return false;
  }

  public static function display($mValue) {

    $parser = self::getManager('parser');
    $context = $parser ? $parser->getContext('errors', false) : null;

    if ($context) {

      $context->add(array(
        'content' => '<div xmlns="http://www.w3.org/1999/xhtml" class="sylma-error" tabindex="0">' . $mValue . '</div>',
      ));
    }
    else if (self::read('debug/enable')) {

      echo $mValue . '<hr/>';
    }
  }

  public static function dsp() {

    foreach (func_get_args() as $mVal) {

      self::display(self::show($mVal, false));
    }
  }

  public static function show($mVal, $bToken = true) {

    $formater = self::getManager('formater');

    $result = $bToken ? $formater->asToken($mVal) : $formater->asHTML($mVal);

    //echo '<pre>' . $result . '</pre>';
    return $result;
  }

  public static function loadException(Exception $e) {

    $newException = new Sylma::$sExceptionClass($e->getMessage());
    $newException->loadException($e);

    return $newException;
  }

  public static function throwException($sMessage, array $aPath = array(), $iOffset = 1, array $aVars = array()) {

    $e = new Sylma::$sExceptionClass($sMessage);

    $e->setPath($aPath);
    $e->setVariables($aVars);
    $e->load($iOffset);

    throw $e;
  }

  public static function isWindows() {

    return PHP_OS == 'WINNT';
  }

  public static function createArgument($mContent = array()) {

    return new argument\Readable($mContent);
  }

  public static function includeFile($sFile, array $aSylmaArguments = array(), $bSylmaExternal = false) {

    extract($aSylmaArguments);

    return include($sFile);
  }

  public static function includeFileCheck($sFile, array $aSylmaArguments = array(), $bSylmaExternal = false) {

    if (file_exists($sFile)) {

      $result = self::includeFile($sFile, $aSylmaArguments, $bSylmaExternal);
    }
    else {

      $result = null;
    }

    return $result;
  }

  public static function load($sFile, $sDirectory = '') {

    $result = null;
    $bRoot = $sFile{0} == '/';

    if (!$sDirectory || $bRoot) {

      if ($bRoot) $sFile = substr($sFile, 1);
      $sPath = $sFile;
    }
    else {

      $aFile = explode('/', $sFile);
      $aDirectory = explode('/', $sDirectory);

      foreach ($aFile as $sName) {

        if ($sName == '..') {

          array_pop($aDirectory);
          array_shift($aFile);
        }
        else {

          $aDirectory[] = $sName;
        }
      }

      $sPath = implode('/', $aDirectory);
    }

    if (!array_key_exists($sPath, self::$aFiles)) {

      $result = require_once($sPath);
    }

    return $result;
  }

  public static function render() {

    if (!self::isAdmin() && self::read('render/gzip')) ob_start('ob_gzhandler');

    return self::$result;
  }
}