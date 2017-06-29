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

  public function getSchemas() {

    $this->setDirectory(__FILE__);

    $ns = $this->getDocument()->getRoot()->getNamespace();
    $this->setNamespace('urn:oasis:names:tc:entity:xmlns:xml:catalog', 'cat');

    $doc = $this->getDocument('/#sylma/catalog.xml');
    $uri = $doc->getRoot()->readx("//cat:uri[@name='$ns']/@uri", array(), false);

    $result = null;

    if ($uri) {

      $result = $this->buildSchema($this->getFile('/#sylma/' . $uri));
    }
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
      'xl' => 'http://2013.sylma.org/storage/xml',
      'xs' => 'http://www.w3.org/2001/XMLSchema',
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
      'path' => $filepath,
    ));

    if (!$id) {

      $id = $this->run('file/insert', array(
        'path' => $filepath,
      ));
    }

    $update = $this->run('history/time', array('file' => $id));
    $messages = $this->getManager(self::PARSER_MANAGER)->getContext('messages');

    if (0 && $this->run('file/locked', array('id' => $id))) {
      
      $messages->add(array('content' => 'File locked'));
    }
    else {

      if ($this->read('update') < $update) {

        //dsp('Send new rows');
      }
      //if ($file->getUpdateTime() < $update) {

      $this->run('file/lock', array('id' => $id));

      try {

        $result = $this->updateDocument($id, $file, $file->asDocument($this->getNS()));
//        $result = 1;
        
        if (!$result) {
          
          dsp('Error on update');
        }
      }
      catch (core\exception $e) {

        dsp($e->getMessage());
        throw $e;
      }

      $this->run('file/unlock', array('id' => $id));
    }

    return $result;
  }

  protected function updateDocument($id, fs\file $file, dom\document $doc) 
  {
    $steps = $this->get('steps');
    $user = (string) $this->getManager('user');

    $this->setNamespaces($this->getNamespaces());
    
    foreach ($steps as $step) {
      
      if ($step->read('type') === 'clear')
      {
        $this->run('history/clear', array('file' => $id));
      }
      else
      {
        $step->set('file', $id);
        $step->set('user', $user);

        if ($step->read('type') === 'undo')
        {
          $step = $this->undo($id, $step);
          $args = $step->get('arguments');
        }
        else if ($step->read('type') === 'redo')
        {
          $step = $this->redo($id, $step);
          $args = $step->get('arguments');
        }
        else
        {
          $this->run('history/insert', array(), $step->asArray());
          $args = $this->createArgument(json_decode($step->read('arguments'), true));
        }
  //dsp($args);
        $el = $this->findElement($doc->getRoot(), $step->read('path'));
  //dsp($el, $args);
        switch ($args->read('type')) {

          case 'element' : $this->updateElement($doc, $el, $step, $args); break;
          case 'text' : $this->updateText($el, $step, $args); break;
          case 'attribute' : $this->updateAttribute($el, $step, $args); break;
          default : $this->launchException('Unknown step type');
        }
      }
    }
    
    $doc->saveFile($file, true);

    return true;
  }
  
  protected function undo($id, $step)
  {
    $last = $this->run('history/last', array('file' => $id, 'disabled' => 0));
    
    $pstep = $this->createArgument(current($last));
    $args = $this->createArgument(json_decode($pstep->read('arguments'), true));
    
    $step->set('arguments', $args);

    switch ($pstep->read('type'))
    {
      case 'add' :

        $step->set('type', 'remove'); 
        
        switch ($args->read('type'))
        {
          case 'element' : $step->set('path', $pstep->read('path') . '/' . $args->read('position')); break;
          case 'attribute' : $step->set('path', $pstep->read('path')); break;
        }
        
        break;

      case 'update' :

        $step->set('type', 'update');
        $step->set('path', $pstep->read('path'));
        $step->set('content', $args->read('previous'));
        break;
      
      case 'remove' :
        
        switch ($args->read('type'))
        {
          case 'element' : 

            $path = explode('/', $pstep->read('path'));
            $position = (int) array_pop($path);

            $args->set('position', $position);

            $step->set('type', 'add');
            $step->set('path', implode('/', $path));
            $step->set('content', $pstep->read('content'));
            break;
          
          case 'attribute' :
            
            $step->set('type', 'add');
            $step->set('path', $pstep->read('path'));
            $step->set('content', $pstep->read('content'));
            break;
          
          default : $this->launchException('Uknown node type');
        }
        
        break;
      
      default : $this->launchException('Uknown step type');
    }

    $this->run('history/disable', array('id' => $pstep->read('id'), 'value' => 1));

    return $step;
  }
  
  protected function redo($id, $step)
  {
    $last = $this->run('history/last', array('file' => $id, 'disabled' => 1));
//dsp($last);
    $pstep = $this->createArgument(current($last));
    $args = $this->createArgument(json_decode($pstep->read('arguments'), true));
    
    $pstep->set('arguments', $args);

    $this->run('history/disable', array('id' => $pstep->read('id'), 'value' => 0));
    
    return $pstep;
  }

  protected function updateElement(dom\document $doc, dom\element $el, core\argument $step, core\argument $args) {

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

      case 'move' :

        $parent = $this->findElement($doc->getRoot(), $args->read('parent'));
        $position = $args->read('position');
        
        $el->remove();
        $parent->insert($el, $parent->getChildren()->item($position));
        break;

      case 'remove' :

        $el->remove();
        break;

      default : $this->launchException('Unknown step type');
    }
  }

  protected function updateText(dom\element $el, core\argument $step, core\argument $args) {

    switch ($step->read('type')) {

      case 'add' :

        $position = $args->read('position');
        $el->insert($step->read('content'), $el->getChildren()->item($position));
        break;

      case 'update' :

        $position = $args->read('position');
        $el->getChildren()->item($position)->nodeValue = $step->read('content');
        break;

      case 'remove' :

        $position = $args->read('position');
        $el->getChildren()->item($position)->remove();
        break;

      default : $this->launchException('Unknown step type');
    }
  }

  protected function updateAttribute(dom\element $el, core\argument $step, core\argument $args) {

    switch ($step->read('type')) {

      case 'add' :
      case 'update' :

        //$el->createAttribute($args->read('name'), $step->read('content'), $args->read('namespace', false));

        if (strpos($args->read('name'), ':') !== false) {

          $el->setAttributeNS($args->read('namespace'), $args->read('name'), $step->read('content'));
        }
        else {
          
          $el->setAttribute($args->read('name'), $step->read('content'));
        }
        
        //$el->setAttribute($args->read('name'), $args->read('content'));

        break;

      case 'remove' :
//dsp($step, $args);
        //$el->setAttribute($args->read('name'), '', $args->read('namespace', false));
        $attribute = $el->loadAttribute($args->read('name'), $args->read('prefix', false) ? $args->read('namespace', false) : '');
        $attribute->remove();
        break;

      default : $this->launchException('Unknown step type');
    }
  }

  protected function findElement($result, $path) {

    $path = explode('/', $path);
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

  public function asXML()
  {
    return (string) $this->getDocument();
  }
}

