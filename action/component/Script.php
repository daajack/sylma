<?php

namespace sylma\action\component;
use sylma\core, sylma\parser\languages\common, sylma\storage\fs;

class Script extends Caller implements common\arrayable {

  public function build() {

    $path = $this->loadPath($this->readx('@path'));

    $parser = $this->getWindow()->addManager(self::PARSER_MANAGER);
    $fs = $this->getWindow()->addManager(self::FILE_MANAGER);

    if ($sMode = $this->readx('@mode')) {

      if (!in_array($sMode, array('get', 'post', 'context'))) {

        $this->launchException('Mode unknown for script call');
      }
    }
    else {

      $sMode = 'get';
    }

    $args = $post = null;

    if ($sMode === 'get') {

      $args = $this->createObject('argument', array(array_merge($path->getArguments()->query(), $this->loadArguments())), null, false);
      $post = $this->createObject('argument', array(array()), null, false);
    }
    else {

      $args = $this->createObject('argument', array($path->getArguments()->query()), null, false);
      $post = $this->createObject('argument', array($this->loadArguments()), null, false);
    }

    $file =  $path->asFile(true);
    $builder = $this->getManager(self::PARSER_MANAGER)->loadBuilder($file);

    $resourceFile = $builder->findResourceFile($path);
    $this->getRoot()->addResourceCall($resourceFile);

    $this->addDependency($file);

    $this->rebuild($file);

    $result = $parser->call('load', array($fs->call('getFile', array((string) $file)), array_filter(array(
      'arguments' => $args,
      'post' => $post,
      'contexts' => $this->getWindow()->getVariable('contexts'),
    ))));

    if ($this->readx('@hollow')) {

      $result = $this->getWindow()->createInstruction($result);
    }

    return array($result);
  }

  protected function addDependency(fs\file $file) {

    $this->getRoot()->addDependency($file, true);
  }

  protected function rebuild(fs\file $file) {

    if ((string) $file !== (string) $this->getSourceFile()) {

      $builder = $this->getManager(self::PARSER_MANAGER);
      $builder->load($file, array(), null, false);
    }

  }

  public function asArray() {

    return $this->build();
  }
}

