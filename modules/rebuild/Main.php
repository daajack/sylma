<?php

namespace sylma\modules\rebuild;
use sylma\core, sylma\dom;

class Main extends core\module\Domed implements dom\domable {

  const PARSER_ACTION = 'action';

  public function __construct($args, $post, $contexts) {

    $this->setDirectory(__file__);

    $this->setSettings(\Sylma::get('modules/rebuild'));
    $this->aContexts = array(
      'contexts' => $contexts,
      'post' => $post,
      'arguments' => $args,
    );
  }

  /**
   *
   * @param type $sPath
   * @return string
   */
  public function load($sPath, $run = true, $force = true) {
    
    $run = is_null($run) ? true : $run;
    if ($force === 'null') $force = null;
    
    $this->aContexts['arguments']->set('path');

    $file = $this->getFile($sPath);
    //$parent = $this->getControler('parser')->getContext('action/current');
    
    $this->getManager('parser')->getContext('messages')->add(array(
      'content' => "Building complete : $sPath"
    ));
    
    if ($run)
    {
      dsp($sPath);
    }

    if (!in_array((string) $file, $this->get('exclude/run')->query())) {

      $manager = $this->getManager(self::PARSER_MANAGER);
      $manager->load($file, $this->aContexts, $force, $run);
    }
//$parent->getContexts()->get('message');
    return '1';
  }

  public function asDOM() {

    $this->loadDefaultSettings();

    $dir = $this->getDirectory('/');
    $files = $dir->getFiles(array(
      '/\.vml$/'
    ), array(
      '|^/users|',
      '/\.svn/',
      '|^/sylma/modules/tester|',
      '|^/sylma/samples|',
      '|/test|',
    ), true);

    foreach ($files as $file) {

      $aFiles[] = array(
        'path' => (string) $file,
        'action-path' => $file->asPath()
      );
    }

    $controller = $dir->getManager();

    //$files = $dir->browse($root);
    $doc = $this->createArgument(array('directory' => array(
      '#file' => $aFiles
    )), $controller::NS)->asDOM();

    return $this->getTemplate('source.xsl')->parseDocument($doc);
  }
}

