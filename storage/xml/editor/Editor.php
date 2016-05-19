<?php

namespace sylma\storage\xml\editor;
use sylma\core, sylma\dom, sylma\storage\fs;

class Editor extends core\module\Domed {

  const FACTORY_RELOAD = false;
  const FILE_MANAGER = 'fs/editable';

  const NS = 'http://2013.sylma.org/modules/stepper';

  public function __construct(core\argument $args, core\argument $post) {

    //$this->setDirectory(__DIR__);
    $this->setNamespace(self::NS);
    $this->loadDefaultSettings();

    $this->setSettings($post);
    $this->setSettings($args);

    if ($sDirectory = $this->read('dir', false)) {

      $this->setDirectory($this->getManager(self::FILE_MANAGER)->getDirectory($sDirectory));
    }
  }

  public function init(fs\file $file) {

    $this->setFile($file);
    $this->setDocument($file->asDocument());
  }

  public function getFile($sPath = '', $bDebug = true) {

    return parent::getFile($sPath, $bDebug);
  }

  protected function buildElement(dom\element $el) {

    $aResult = array(
      '_alias' => 'element',
      'namespace' => $el->getNamespace(),
      'prefix' => $el->getPrefix(),
      'name' => $el->getName(),
      'attribute' => array(),
      'format' => $el->isComplex() ? 'complex' : (strlen($el->read()) < 100 ? 'text' : 'complex'),
    );

    foreach ($el->getAttributes() as $attr) {

      $aResult['attribute'][] = array(
        'prefix' => $attr->getPrefix(),
        'name' => $attr->getName(),
        'value' => (string) $attr,
      );
    }

    $aChildren = array();

    \Sylma::load('core/functions/Global.php');

    foreach ($el->getChildren() as $child) {

      if ($child instanceof dom\element) {

        $aChildren[] = $this->buildElement($child);
      }
      else if ($child instanceof \DOMComment) {

        $aChildren[] = array(
          '_alias' => 'comment',
          'content' => \sylma\core\functions\xmlize($this->trim((string) $child)),
        );
      }
      else {

        $sContent = $this->trim((string) $child);
        //$sContent = trim(preg_replace(array('/[\t ]+/', '/\n\s*/'), array(' ', "\n"), $sContent));

        $aChildren[] = array(
          '_alias' => 'text',
          'content' => $sContent,
        );
      }
    }

    if ($aChildren) {

      $aResult['children'] = array(
        array(
          '_all' => $aChildren
        ),
      );
    }

    return $aResult;
  }

  public function getSchemas() {

    $this->setDirectory(__FILE__);
    $result = $this->buildSchema($this->getFile('/#sylma/view/parser/crud.xsd'));
//dsp($result);
//dsp(json_encode($result));

    return $result;
  }

  public function getUpdate() {

    return time();
  }

  protected function buildSchema(fs\file $file) {

    $builder = $this->getManager(self::PARSER_MANAGER)->loadBuilder($file);
    $schema = $builder->getSchema();

    try {

      $arg = $this->createArgument($schema->asArray());
      $aResult = $arg->asArray();

    } catch (core\exception $e) {

      $e->addPath($file->asToken());
      throw $e;
    }

//dsp($aResult);
    return $aResult;
  }

  protected function trim($sContent) {

    if (preg_match('/^[ ]+/m', $sContent, $matches)) {

      $i = strlen($matches[0]);

      $sContent = preg_replace("/^[\s\t]{" . $i . "}/m", '', $sContent);
    }

    return trim($sContent);
  }

  public function getRights() {

    $file = $this->getFile();

    return $file->getRights();
  }

  public function getNamespaces() {

    return array(
      'crud' => 'http://2013.sylma.org/view/crud',
      'tpl' => 'http://2013.sylma.org/template',
      'le' => 'http://2013.sylma.org/action',
      'sql' => 'http://2013.sylma.org/storage/sql',
      'view' => 'http://2013.sylma.org/view',
      'js' => 'http://2013.sylma.org/template/binder',
      'cls' => 'http://2013.sylma.org/core/factory',
    );
  }

  protected function run($path, array $arguments = array(), array $posts = array(), array $contexts = array()) {

    return $this->getScript($path, $arguments, $posts, $contexts);
  }

  public function update() {

    $result = false;

    $this->setDirectory(__FILE__);

    $file = $this->getFile($this->read('file'));
    $filepath = (string) $file;

    $id = $this->run('file', array(
      'file' => $filepath,
    ));

    if (!$id) {

      $id = $this->run('file/insert', array(
        'file' => $filepath,
      ));
    }

    $update = $this->run('history', array('file' => $id));

    if (!$this->run('file/locked', array('id' => $id))) {

      if ($this->read('update') < $update) {

        dsp('Send new rows');
      }
      //if ($file->getUpdateTime() < $update) {

      $this->run('file/lock', array('id' => $id));

      try {

        $result = $this->updateDocument($id, $file, $file->asDocument($this->getNS()));
      }
      catch (core\exception $e) {

        //dsp($e->getMessage());
        throw $e;
      }

      $this->run('file/unlock', array('id' => $id));
    }

    return $result;
  }

  protected function updateDocument($id, fs\file $file, dom\document $doc) {

    //$this->set('file', $id);

    $steps = $this->get('steps');
    $user = (string) $this->getManager('user');

    $this->setNamespaces($this->getNamespaces());
//dsp($this->getNS());
    foreach ($steps as $step) {

      $step->set('file', $id);
      $step->set('user', $user);
      $args = $this->createArgument(json_decode($step->read('arguments'), true));

      $this->run('history/insert', array(), $step->asArray());
      $path = explode('/', $step->read('path'));

      $el = $this->findElement($doc->getRoot(), $path);

      switch ($args->read('type')) {

        case 'element' : $this->updateElement($el, $step, $args); break;
        case 'text' : $this->updateText($el, $step, $args); break;
        case 'attribute' : $this->updateAttribute($el, $step, $args); break;
        default : $this->launchException('Unknown step type');
      }
    }
//dsp('Save : ' . $file);
//dsp($doc);
    $doc->saveFile($file, true);

    return true;
  }

  protected function updateElement(dom\element $el, core\argument $step, core\argument $args) {

    switch ($step->read('type')) {

      case 'add' :

        $position = $args->read('position');
        $content = $this->createDocument($step->read('content'));
//dsp($step, $el);
        if ($position !== null) {

          $el->insert($content, $el->getChildren()->item($position));
        }
        else {

          $el->add($content);
        }

        break;

      case 'remove' :

        $el->remove();
        break;

      default : $this->launchException('Unknown step type');
    }
  }

  protected function updateText(dom\element $el, core\argument $step, core\argument $args) {

    switch ($step->read('type')) {

      case 'update' :

        $el->set($step->read('content'));
        break;
/*
      case 'remove' :

        $el->set($step->read('value'));
        break;
*/
      default : $this->launchException('Unknown step type');
    }
  }

  protected function updateAttribute(dom\element $el, core\argument $step, core\argument $args) {

    switch ($step->read('type')) {

      case 'add' :
      case 'update' :

        //$el->createAttribute($args->read('name'), $args->read('value'), $args->read('namespace', false));
        $el->setAttribute($args->read('name'), $step->read('content'));
        break;

      case 'remove' :

        //$el->setAttribute($args->read('name'), '', $args->read('namespace', false));
        $attribute = $el->loadAttribute($args->read('name'), $args->read('namespace', false));
        $attribute->remove();
        break;

      default : $this->launchException('Unknown step type');
    }
  }

  protected function findElement($result, $path) {

    $position = next($path);

    while ($result && $position !== false) {

      $children = $result->getChildren();
      $result = $children->item($position);
//dsp($result);
      $position = next($path);
    }

    if (!$result) {

      $this->launchException('Cannot find element in : ' . implode('/', $path), get_defined_vars());
    }

    return $result;
  }

  public function asJSON() {

    $doc = $this->getDocument();

    $aResult = array('element' => array($this->buildElement($doc->getRoot())));

    return $aResult;
  }
}

