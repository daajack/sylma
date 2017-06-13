<?php

namespace sylma\modules\inspector\php\import;
use sylma\core, sylma\storage\fs;

class Importer extends core\module\Domed
{

  const FORCE_UPDATE = false;

  protected $iSystem = 0;
  protected $aClasses = array();

  public function __construct() {

    $this->setDirectory(__FILE__);
    $this->loadDefaultSettings();

    $this->iSystem = strlen($this->getDirectory('/')->getSystemPath());

    if (self::FORCE_UPDATE) {

      $this->reset();
    }
  }

  protected function reset() {

    $this->getManager('mysql')->getConnection('test')->execute($this->getFile('empty.sql')->read());
  }

  protected function runAll() {

    $dir = $this->getDirectory('/#sylma');
    $this->reset();

    $iStart = microtime(true);

    $aFiles = $dir->getFiles(array('php'), null, true);

    foreach ($aFiles as $file) {

      if (in_array((string) $file, array(
        '/sylma/modules/install/skeleton/index.php',
        '/sylma/core/module/Extension.php',
        '/sylma/core/module/test/samples/Domed.php',
        '/sylma/parser/reflector/component/Simple.php',
        '/sylma/template/parser/Texted.php',
      ))) continue;

      require_once($file->getRealPath());
    }

    $this->buildClasses(get_declared_classes());

    dsp(round(microtime(true) - $iStart) . ' seconds elapsed');
    //$this->buildClasses(get_declared_interfaces());
  }

  protected function buildClasses(array $aClasses) {

    $iCount = 0;
    $iMax = 1000;

    foreach ($aClasses as $sClass) {

      if (strtolower(substr($sClass, 0, 5)) === 'sylma') {
      //if (preg_match('/core\\\module/', $sClass)) {

        $this->buildClass($sClass);
        $iCount++;
        if ($iCount > $iMax) break;
      }
    }

    dsp($iCount . ' classes imported');
  }

  public function inspect($sFile) {

    $result = null;
    $file = $this->getFile($sFile);

    $iFile = $this->getScript('update/file-time', array(
      'name' => $sFile,
    ));

    $updateDB = $updateFile = 0;

    if ($iFile) {

      $updateDB = new \DateTime($iFile);
      $updateFile = $file->getUpdateTime();
    }

    if (!$iFile || $updateFile > $updateDB->getTimestamp() || self::FORCE_UPDATE) {

      //$sClass = $this->findClass($file);
      $sClass = str_replace('/', '\\', substr($file, 1, -4));

      if ((string) $file === '/sylma/core/Sylma.php') {

        $sClass = 'Sylma';
      }

      require_once($file->getRealPath());

      $result = $this->initClass($sClass);
    }
  }

  protected function initClass($sClass) {

    if (!array_key_exists($sClass, $this->aClasses)) {

      $class = new \ReflectionClass($sClass);

      if (!$class->isInternal()) {

        $sID = $this->getScript('update/class-select', array('name' => $sClass));

        if ($sID) {

          $this->aClasses[$sClass] = $this->getScript('update/class', array('name' => $sClass));
        }
        else {

          $this->aClasses[$sClass] = $this->buildClass($class);
        }
      }
      else {

        $this->aClasses[$sClass] = array();
      }
    }

    return $this->aClasses[$sClass];
  }

  protected function buildClass(\ReflectionClass $class) {

    $sFile = substr($class->getFileName(), $this->iSystem);
    $sFile = str_replace('\\', '/', $sFile);

    $iFile = $this->getScript('update/file-id', array(
      'name' => $sFile,
    ));

    if ($iFile) {

      $now = new \DateTime;

      $this->getScript('update/file-update', array(), array(
        'id' => $iFile,
        'update' => $now->format('Y-m-d H:i:s'),
      ));
    }
    else {

      $iFile = $this->getScript('update/file-insert', array(), array(
        'name' => $sFile,
      ));
    }

    $iNamespace = null;
    $sNamespace = $class->getNamespaceName();

    if ($sNamespace) {

      preg_match('/[\w]+$/', $sNamespace, $aMatch);
      $sShortNamespace = $aMatch[0];

      $iNamespace = $this->getScript('update/namespace', array(
        'name' => $sNamespace,
        'name_short' => $sShortNamespace,
      ));

      if (!$iNamespace) {

        $iNamespace = $this->getScript('update/namespace/insert', array(), array(
          'name' => $class->getNamespaceName(),
          'name_short' => $sShortNamespace,
        ));
      }
    }

    $sComment = $class->getDocComment();

    $iClass = $this->getScript('update/class/insert', array(), array(
      'name' => $class->getName(),
      'name_short' => $class->getShortName(),
      'description' => $sComment ? $sComment : null,

      'file' => $iFile,
      'namespace' => $iNamespace,
      //'extends' => $aExtends,
    ));

    $parent = $class->getParentClass();

    if ($parent) {

      $aExtends = $this->initClass($parent->getName());
    }
    else {

      $aExtends = array();
    }

    array_unshift($aExtends, $iClass);

    $this->getScript('update/class-update', array(), array(
      'id' => $iClass,
      'extends' => $aExtends,
    ));

    //array_unshift($aExtends, $iClass);
    $db = $this->getManager('mysql')->getConnection('test');

    $iMethods = 0;

    foreach ($class->getMethods() as $method) {

      if ($method->getDeclaringClass()->getName() !== $class->getName()) continue;

      $sComment = $method->getDocComment();

      $iMethod = $this->getScript('update/method', array(), array(
        'name' => $method->getShortName(),
        'fullname' => $class->getName() . '::' . $method->getShortName(),
        'modifiers' => $method->getModifiers(),
        'description' => $sComment ? $sComment : null,

        'class' => $iClass,
      ));

      $iMethods++;
    }

    //dsp($sClass . ' imported with ' . $iMethods . ' methods');

    return $aExtends;
  }

  /**
   * from http://stackoverflow.com/a/7153391/4011283
   */
  protected function findClass(fs\file $file) {

    $fp = fopen($file->getSystemPath(), 'r');
    $class = $namespace = $buffer = '';
    $i = 0;
    while (!$class) {
        if (feof($fp)) break;

        $buffer .= fread($fp, 512);

        try {
          $tokens = token_get_all($buffer);
        }
        catch (\Exception $e) {

        }

        if (strpos($buffer, '{') === false) continue;

        for (;$i<count($tokens);$i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                for ($j=$i+1;$j<count($tokens); $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                         $namespace .= '\\'.$tokens[$j][1];
                    } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                         break;
                    }
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                for ($j=$i+1;$j<count($tokens);$j++) {
                    if ($tokens[$j] === '{') {
                        $class = $tokens[$i+2][1];
                    }
                }
            }
        }
    }

    return $namespace . '\\' . $class;
  }

}
