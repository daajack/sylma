<?php
    return new \sylma\core\argument\parser\Cached(array(
'debug' => array(
  'show' => '0'),
'cache' => array(
  'class' => '\sylma\core\argument\parser\Cached'),
'template' => '/#sylma/core/argument/parser/compiler/basic.xsl',
'classes' => array(
  'elemented' => array(
    'file' => '\sylma\core\argument\parser\compiler\Elemented.php',
    'name' => '\sylma\core\argument\parser\compiler\Elemented',
    'importer' => '\sylma\core\argument\Importer',
    'classes' => array(
      'component' => array(
        'classes' => array(
          'import' => array(
            'file' => '\sylma\core\argument\parser\compiler\component\Import.php',
            'name' => '\sylma\core\argument\parser\compiler\component\Import'))))),
  'window' => array(
    'file' => '\sylma\parser\languages\php\basic\Window.php',
    'name' => '\sylma\parser\languages\php\basic\Window')),
'php' => array(
  'classes' => array(
    'string' => array(
      'file' => '\sylma\parser\languages\php\basic\instance\_String.php',
      'name' => '\sylma\parser\languages\php\basic\instance\_String'),
    'array' => array(
      'file' => '\sylma\parser\languages\php\basic\instance\_Array.php',
      'name' => '\sylma\parser\languages\php\basic\instance\_Array'),
    'numeric' => array(
      'file' => '\sylma\parser\languages\php\basic\instance\_Numeric.php',
      'name' => '\sylma\parser\languages\php\basic\instance\_Numeric'),
    'object' => array(
      'file' => '\sylma\parser\languages\php\basic\instance\_Object.php',
      'name' => '\sylma\parser\languages\php\basic\instance\_Object'),
    'class' => array(
      'file' => '\sylma\parser\languages\php\basic\instance\_Class.php',
      'name' => '\sylma\parser\languages\php\basic\instance\_Class'),
    'call-method' => array(
      'file' => '\sylma\parser\languages\php\basic\CallMethod.php',
      'name' => '\sylma\parser\languages\php\basic\CallMethod'),
    'call' => array(
      'file' => '\sylma\parser\languages\php\basic\_Call.php',
      'name' => '\sylma\parser\languages\php\basic\_Call'),
    'object-var' => array(
      'file' => '\sylma\parser\languages\php\basic\_ObjectVar.php',
      'name' => '\sylma\parser\languages\php\basic\_ObjectVar'),
    'simple-var' => array(
      'file' => '\sylma\parser\languages\php\basic\_ScalarVar.php',
      'name' => '\sylma\parser\languages\php\basic\_ScalarVar'),
    'template' => array(
      'file' => '\sylma\parser\languages\php\basic\Template.php',
      'name' => '\sylma\parser\languages\php\basic\Template'),
    'function' => array(
      'file' => '\sylma\parser\languages\php\basic\_Function.php',
      'name' => '\sylma\parser\languages\php\basic\_Function'),
    'closure' => array(
      'file' => '\sylma\parser\languages\php\basic\_Closure.php',
      'name' => '\sylma\parser\languages\php\basic\_Closure'),
    'instanciate' => array(
      'file' => '\sylma\parser\languages\php\basic\Instanciate.php',
      'name' => '\sylma\parser\languages\php\basic\Instanciate'),
    'assign' => array(
      'file' => '\sylma\parser\languages\common\basic\Assign.php',
      'name' => '\sylma\parser\languages\common\basic\Assign'),
    'assign-concat' => array(
      'file' => '\sylma\parser\languages\php\basic\assign\Concat.php',
      'name' => '\sylma\parser\languages\php\basic\assign\Concat'),
    'insert' => array(
      'file' => '\sylma\parser\languages\php\basic\Insert.php',
      'name' => '\sylma\parser\languages\php\basic\Insert'),
    'interface' => array(
      'file' => '\sylma\parser\languages\php\basic\_Interface.php',
      'name' => '\sylma\parser\languages\php\basic\_Interface'),
    'method' => array(
      'file' => '\sylma\parser\languages\php\basic\Method.php',
      'name' => '\sylma\parser\languages\php\basic\Method'),
    'line' => array(
      'file' => '\sylma\parser\languages\php\basic\_Line.php',
      'name' => '\sylma\parser\languages\php\basic\_Line'),
    'null' => array(
      'file' => '\sylma\parser\languages\php\basic\instance\_Null.php',
      'name' => '\sylma\parser\languages\php\basic\instance\_Null'),
    'boolean' => array(
      'file' => '\sylma\parser\languages\php\basic\instance\_Boolean.php',
      'name' => '\sylma\parser\languages\php\basic\instance\_Boolean'),
    'concat' => array(
      'file' => '\sylma\parser\languages\php\basic\Concat.php',
      'name' => '\sylma\parser\languages\php\basic\Concat'),
    'condition' => array(
      'file' => '\sylma\parser\languages\php\basic\Condition.php',
      'name' => '\sylma\parser\languages\php\basic\Condition'),
    'switch' => array(
      'file' => '\sylma\parser\languages\php\basic\_Switch.php',
      'name' => '\sylma\parser\languages\php\basic\_Switch'),
    'case' => array(
      'file' => '\sylma\parser\languages\php\basic\_Case.php',
      'name' => '\sylma\parser\languages\php\basic\_Case'),
    'test' => array(
      'file' => '\sylma\parser\languages\php\basic\Test.php',
      'name' => '\sylma\parser\languages\php\basic\Test'),
    'loop' => array(
      'file' => '\sylma\parser\languages\php\basic\_Foreach.php',
      'name' => '\sylma\parser\languages\php\basic\_Foreach'),
    'cast' => array(
      'file' => '\sylma\parser\languages\php\basic\Cast.php',
      'name' => '\sylma\parser\languages\php\basic\Cast')))));
  