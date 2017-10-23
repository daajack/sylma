<?php

namespace sylma\storage\xml\editor;
use sylma\core, sylma\dom, sylma\storage\fs;

class Editor extends core\module\Domed {

  const FACTORY_RELOAD = false;
  const FILE_MANAGER = 'fs/editable';

  const NS = 'http://2013.sylma.org/modules/stepper';
  
  protected $id;
  
  public function __construct(core\argument $args, core\argument $post, core\argument $contexts) {

    //$this->setDirectory(__DIR__);
    $this->setNamespace(self::NS);
    $this->loadDefaultSettings();

    $this->setSettings($post);
    $this->setSettings($args);
    
    $this->contexts = $contexts;

    if ($sDirectory = $this->read('dir', false)) {

      $this->setDirectory($this->getManager(self::FILE_MANAGER)->getDirectory($sDirectory));
    }
  }

  public function loadFile(fs\file $file)
  {
    $this->setDirectory(__FILE__);
    $this->setFile($file);
    
    $historyUpdate = $this->run('history/time', array('file' => (string) $file));
    $fileUpdate = $file->getUpdateTime();
    $historyUpdate = strtotime($historyUpdate);
    
    $doc = $this->loadLast($file);
//dsp($fileUpdate, $historyUpdate);
    if ($fileUpdate > $historyUpdate)
    {
      $id = $this->id;
      $user = $this->getManager('user');
      $this->setDocument($file->asDocument());
      
      $step = array(
        'file' => $id,
        'user' => (string) $user,
        'type' => 'revision',
        'document' => $this->asXML(),
        'display' => date('Y-m-d h:m:s', $fileUpdate),
      );
      
      $this->run('history/insert', array(), $step);
      $this->resetRevisionCount($id);
    }
    else
    {
      $this->setDocument($doc);
    }
  }
  
  protected function loadLast(fs\file $file)
  {
    $id = $this->checkFile($file);
    $last = $this->run('history/last', array('file' => $id, 'disabled' => 0));
    
    if (!$last)
    {
      $doc = $file->asDocument();
    }
    else
    {
      $pstep = $this->createArgument(current($last));
      $doc = $this->loadRevision($file, $pstep->read('id'));
    }
    
    $this->id = $id;
    
    return $doc;
  }

  public function getFile($sPath = '', $bDebug = true) {

    return parent::getFile($sPath, $bDebug);
  }

  public function getSchemas(dom\document $doc = null) {

    $this->setDirectory(__FILE__);
    
    if (!$doc)
    {
      $doc = $this->getDocument();
    }

    $ns = $doc->getRoot()->getNamespace();
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
      'http://2013.sylma.org/view/crud' => 'crud',
      'http://2013.sylma.org/template' => 'tpl',
      'http://2017.sylma.org/view' => 'tpl',
      'http://2013.sylma.org/action' => 'le',
      'http://2013.sylma.org/storage/sql' => 'sql',
      'http://2013.sylma.org/view' => 'view',
      'http://2013.sylma.org/template/binder' => 'js',
      'http://2013.sylma.org/core/factory' => 'cls',
      'http://2013.sylma.org/storage/xml' => 'xl',
      'http://www.w3.org/2001/XMLSchema' => 'xs',
    );
  }

  protected function run($path, array $arguments = array(), array $posts = array(), array $contexts = array()) {

    return $this->getScript($path, $arguments, $posts, $contexts);
  }

  public function openFileContainer($path)
  {
    $this->setDirectory(__FILE__);
    
    $file = $this->getFile($path);
    $doc = $file->asDocument();

    switch ($doc->getRoot()->getNamespace())
    {
      case 'http://2017.sylma.org/view' : $path = '/#sylma/view/editor/file-container.vml'; break;
//      default : $path = '/#sylma/view/editor/file-container'; break;
      default : $path = 'file-container.vml';
    }

    $result = $this->getScript($path, array('file' => (string) $file), array(), $this->contexts->query());
    
    return $result;
  }

  protected function loadRevision(fs\file $file, $step)
  {
    $filepath = (string) $file;

    $id = $this->run('file', array(
      'path' => $filepath,
    ));
    
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
    
    return $doc;
  }
  
  public function openRevision()
  {
    $this->setDirectory(__FILE__);
    $this->loadDefaultSettings();

    $file = $this->getFile($this->read('file'));
    $doc = $this->loadRevision($file, $this->read('step'));
    
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
  
  protected function checkFile(fs\file $file)
  {
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
    
    return $id;
  }
  
  public function update() 
  {
    $this->setDirectory(__FILE__);
    
    $result = false;
    
    $file = $this->getFile($this->read('file'));
    $id = $this->checkFile($file);
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
        $result = $this->updateDocument($id, $file); // , \Sylma::MODE_READ, true
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

  protected function updateDocument($id, fs\file $file) 
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
        }
        else
        {
          $args = $this->createArgument(json_decode($step->read('arguments'), true));
          $count = $this->run('file/steps', array('id' => $id));
          
          if ($count == 0)
          {
            $doc = $this->loadLast($file);
            $this->applyStep($doc, $step, $args);
            $step->set('document', (string) $doc);
            $this->resetRevisionCount($id);
          }

          $this->run('history/insert', array(), $step->asArray());
          $this->updateRevisionCount($id);
        }
      }
    }
//    dsp($doc, $step);
    return true;
  }
  
  protected function updateRevisionCount($id)
  {
    $connection = $this->getManager('mysql')->getConnection();
    
    $id = $connection->escape($id);
    $connection->execute("UPDATE `editor_file` SET steps = steps - 1 WHERE id = $id");
  }
  
  protected function resetRevisionCount($id)
  {
    $connection = $this->getManager('mysql')->getConnection();
    
    $id = $connection->escape($id);
    $connection->execute("UPDATE `editor_file` SET steps = 5 WHERE id = $id");
  }
  
  protected function undo($id, $step)
  {
    $last = $this->run('history/last', array('file' => $id, 'disabled' => 0));
    
    $pstep = $this->createArgument(current($last));
    $this->run('history/disable', array('id' => $pstep->read('id'), 'value' => 1));
  }
  
  protected function redo($id, $step)
  {
    $last = $this->run('history/first', array('file' => $id, 'disabled' => 1));
    $pstep = $this->createArgument(current($last));

    $this->run('history/disable', array('id' => $pstep->read('id'), 'value' => 0));
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
  
  public function publish()
  {
    $this->setDirectory(__FILE__);
    $file = $this->getFile($this->read('file'));
    $this->buildFiles($file);
    
    $doc = $this->loadLast($file);
    $doc->saveFile($file, true);
    
    return 1;
  }
  
  protected function buildFiles(fs\file $file)
  {
    $this->setDirectory(__FILE__);
    
    $files = $this->get('scripts');
    $parser = $this->getManager('parser');
    $caches = [];
    
    foreach ($files as $script)
    {
      $name = $script->read('name');
      
      if ($name === 'main')
      {
        $main = $script;
      }
      else
      {
        $cache = $parser->getCachedFile($file, '.' . $name . '.php');
        $cache->saveText("<?php\n" . $script->read('content'));
        $caches[$name] = $cache->getRealPath();
      }
    }
    
    $scripts = "\$scripts = array(\n";
    
    foreach ($caches as $key => $cache)
    {
      $scripts .= "'$key' => '$cache',\n";
    }
    
    $scripts .= ");\n";
    
    $cache = $parser->getCachedFile($file);
    $cache->saveText("<?php\n" . $scripts . $main->read('content'));
  }

  public function asXML()
  {
    $doc = $this->getDocument();
    
//    return (string) $doc;
    return $doc->asString();
//    return file_get_contents($this->getFile()->getRealPath());
  }
}

