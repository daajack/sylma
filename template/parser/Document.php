<?php

namespace sylma\template\parser;
use sylma\core, sylma\template, sylma\storage\xml;

class Document  extends xml\tree\Argument implements template\parser\tree
{
  public function reflectApplyFunction($sName, array $path, $sMode, $bRead = false, $sArguments = '', array $aArguments = array()) {

    $result = null;

    switch ($sName) {

      case 'url' : $result = $this->reflectFunctionURL(); break;
      case 'root' : $result = $this->reflectFunctionRoot($path, $sMode, $bRead, $aArguments); break;
      case 'sylma' : $result = $this->reflectFunctionSylma($path); break;
      case 'post' : $result = $this->reflectFunctionArgument('post', $path, $sArguments); break;
      case 'get' :
      // @deprecated, use 'get' instead
      case 'argument' : $result = $this->reflectFunctionArgument('arguments', $path, $sArguments); break;
      case 'locale' : $result = $this->reflectFunctionLocale($path, $sMode, $bRead, $aArguments); break;

      default :

        $this->launchException('Unknown function name');
    }

    return $result;
  }

  /**
   * Read YML config file. @warning allow credentials access
   */
  protected function reflectFunctionSylma(array $aPath) {

    return $this->getWindow()->getSylma()->call('read', array(implode('/', $aPath), true));
  }

  protected function reflectFunctionRoot(array $aPath, $sMode, $bRead = false, array $aArguments = array()) {

    $tree = $this->getParser()->getResource()->getTree();
    $pather = $this->getParser()->getCurrentTemplate()->getPather();
    $pather->setSource($tree);

    return $pather->parsePathToken($aPath, $sMode, $bRead, $aArguments);
  }

  protected function reflectFunctionURL() {

    $window = $this->getWindow();
    $init = $window->addControler('init');

    return $init->call('getURL');
  }

  protected function reflectFunctionArgument($name, array $path, $arguments) {
    
    $arg = $this->getWindow()->getVariable($name);
    
    if ($path) {

      $result = $arg->call('read', $path);
    }
    else if ($arguments) {

      $pather = $this->getParser()->getCurrentTemplate()->getPather();
      $result = $arg->call('read', $pather->parseArguments($arguments));
    }
    else {

      $this->launchException('Argument name is missing');
    }

    return $result;
  }
  
  protected function reflectFunctionLocale(array $aPath, $sMode, $bRead = false, array $aArguments = array()) {
    
    $tree = $this->getManager('locale')->create('tree');
    $pather = $this->getParser()->getCurrentTemplate()->getPather();
    
    $tree->init($pather, $this->getWindow());
    $pather->setSource($tree);

    return $pather->parsePathToken($aPath, $sMode, $bRead, $aArguments);
  }
}
