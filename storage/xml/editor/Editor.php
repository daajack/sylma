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

  public function init(fs\file $file)
  {
    $this->setFile($file);
    $this->setDocument($file->asDocument()); // , \Sylma::MODE_READ, true
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
      'tpl2' => 'http://2013.sylma.org/template',
      'tpl' => 'http://2017.sylma.org/view',
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

  public function loadDocument()
  {
    $this->setDirectory(__FILE__);
    $this->loadDefaultSettings();

    $file = $this->getFile($this->read('file'));
    $filepath = (string) $file;

    $id = $this->run('file', array(
      'path' => $filepath,
    ));
    
    $step = $this->read('step');
    $last = $this->createArgument($this->run('history/document', array('file' => $id, 'from' => $step))[0]);
    
    $doc = $this->createDocument($last->read('document'));
    
    if ($last->read('id') != $step)
    {
      $steps = $this->run('history/range', array('file' => $id, 'from' => $last->read('id'), 'to' => $step));
      
      if (!$steps)
      {
        $this->launchException('No range found');
      }
      
      $steps = $this->createArgument($steps);
      
      foreach ($steps as $step)
      {
        $args = $this->createArgument(json_decode($step->read('arguments'), true));
        $this->applyStep($doc, $step, $args);
      }
    }
    
    
//    $user = (string) $this->getManager('user');

    
    return (string) $doc;
  }
  
  protected function applyStep(dom\document $doc, core\argument $step, core\argument $args) {
    
    $el = $this->findElement($doc->getRoot(), $step->read('path'));

    switch ($args->read('type')) 
    {
      case 'element' :
      case 'text' : $this->updateNode($doc, $el, $step, $args); break;
      case 'attribute' : $this->updateAttribute($el, $step, $args); break;
      default : $this->launchException('Unknown step type');
    }
  }
  
  public function update() 
  {
    $result = false;

    $this->setDirectory(__FILE__);

    $file = $this->getFile($this->read('file'));
    $filepath = (string) $file;

    $id = $this->run('file', array(
      'path' => $filepath,
    ));

    if (!$id) 
    {
      $id = $this->run('file/insert', array(
        'path' => $filepath,
      ));
    }
//$this->launchException('test');
    $update = $this->run('history/time', array('file' => $id));
    $messages = $this->getManager(self::PARSER_MANAGER)->getContext('messages');

    if (0 && $this->run('file/locked', array('id' => $id))) 
    {
      $messages->add(array('content' => 'File locked'));
    }
    else 
    {

      if ($this->read('update') < $update) {

        //dsp('Send new rows');
      }
      //if ($file->getUpdateTime() < $update) {

      $this->run('file/lock', array('id' => $id));

      try 
      {

        $result = $this->updateDocument($id, $file, $file->asDocument($this->getNS())); // , \Sylma::MODE_READ, true
//        $result = 1;
        
        if (!$result)
        {
          dsp('Error on update');
        }
      }
      catch (core\exception $e) 
      {
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
    
    foreach ($steps as $step)
    {
      if ($step->read('type') === 'clear')
      {
        $this->run('history/clear', array('file' => $id));
      }
      else
      {
        $step->set('file', $id);
        $step->set('user', $user);
        
        $type = $step->read('type');

        if ($type === 'undo' || $type === 'redo')
        {
          $step = $this->$type($id, $step);
          $args = $step->get('arguments');
          
          $this->applyStep($doc, $step, $args);
        }
        else
        {
          $args = $this->createArgument(json_decode($step->read('arguments'), true));
          $this->applyStep($doc, $step, $args);
          
          $connection = $this->getManager('mysql')->getConnection();
          $count = $this->run('file/steps', array('id' => $id));
          
          if ($count == 0)
          {
            $step->set('document', (string) $doc);
            $connection->execute("UPDATE `editor_file` SET steps = 5 WHERE id = $id");
          }

          $this->run('history/insert', array(), $step->asArray());
          
          $id = $connection->escape($id);
          $connection->execute("UPDATE `editor_file` SET steps = steps - 1 WHERE id = $id");
        }
      }
    }

//    dsp($doc, $step);
    $doc->saveFile($file, true);

    return true;
  }
  
  protected function undo($id, $step)
  {
    $last = $this->run('history/last', array('file' => $id, 'disabled' => 0));
    
    $pstep = $this->createArgument(current($last));
    $this->run('history/disable', array('id' => $pstep->read('id'), 'value' => 1));
    
    $args = $this->createArgument(json_decode($pstep->read('arguments'), true));
    
    $step->set('arguments', $args);

    switch ($pstep->read('type'))
    {
      case 'add' :

        $step->set('type', 'remove'); 
        
        switch ($args->read('type'))
        {
          case 'text' :
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
          case 'text' : 
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

      case 'move' :

        $p = $args->read('parent');
        $sourcePath = ($p !== '/' ? $p . '/' : $p) . $args->read('position');
        $source = explode('/', $sourcePath);
        $target = explode('/', $pstep->read('path'));

        $position = array_pop($target);
        
        $step->set('type', 'move');
        $step->set('path', implode('/', $source));
        $args->set('parent', implode('/', $target));
        $args->set('position', $position);

      break;

      default : $this->launchException('Uknown step type');
    }

    return $step;
  }
  
  protected function redo($id, $step)
  {
    $last = $this->run('history/first', array('file' => $id, 'disabled' => 1));
    $pstep = $this->createArgument(current($last));
    $args = $this->createArgument(json_decode($pstep->read('arguments'), true));
    
    $pstep->set('arguments', $args);

    $this->run('history/disable', array('id' => $pstep->read('id'), 'value' => 0));
    
    return $pstep;
  }

  protected function updateNode(dom\document $doc, dom\node $node, core\argument $step, core\argument $args) {

    switch ($step->read('type')) {

      case 'update' :

        $node->nodeValue = $step->read('content');
        break;
      
      case 'add' :

        $position = $args->read('position');
        
        if ($args->read('type') === 'element')
        {
          $content = $this->createDocument($step->read('content'));
        }
        else
        {
          $content = $step->read('content');
        }
        
        if ($position !== null) {

          $node->insert($content, $node->getChildren()->item($position));
        }
        else {

          $node->add($content);
        }

        break;

      case 'move' :
        
        $path = $args->read('parent');

        $node->remove();
        
        $parent = $path === '/' ? $doc->getRoot() : $this->findElement($doc->getRoot(), $path);
        $position = $args->read('position');

        try
        {
          $parent->insert($node, $parent->getChildren()->item($position));
        }
        catch (\DOMException $e)
        {
          dsp($step, $node, $parent, $position);
          $this->launchException($e->getMessage());
        }
        
        break;

      case 'remove' :

        $node->remove();
        break;

      default : $this->launchException('Unknown step type');
    }
  }

  protected function updateAttribute(dom\element $el, core\argument $step, core\argument $args) {
    
    $prefix = $args->read('prefix', false);
    
    switch ($step->read('type')) 
    {
      case 'add' :
      case 'update' :

        //$el->createAttribute($args->read('name'), $step->read('content'), $args->read('namespace', false));

        if ($prefix) 
        {
          $el->setAttributeNS($args->read('namespace'), $prefix . ':' . $args->read('name'), $step->read('content'));
        }
        else 
        {
          $el->setAttribute($args->read('name'), $step->read('content'));
        }

        break;

      case 'remove' :
        
        if ($prefix) 
        {
          $attribute = $el->loadAttribute($args->read('name'), $args->read('namespace', false));
        }
        else 
        {
          $attribute = $el->loadAttribute($args->read('name'));
        }
        
        $attribute->remove();
        break;

      default : $this->launchException('Unknown step type');
    }
  }

  protected function findElement(dom\element $result, $spath) {

    $path = explode('/', $spath);
    $position = next($path);

    while ($result && $position !== false) {
      
      if ($result instanceof dom\text)
      {
        $result = null;
        break;
      }
      
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
    $doc = $this->getDocument();
    
//    return (string) $doc;
    return $doc->asString();
//    return file_get_contents($this->getFile()->getRealPath());
  }
}

