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
  public function load($sPath, $bRun = true) {

    $this->aContexts['arguments']->set('path');

    $file = $this->getFile($sPath);
    //$parent = $this->getControler('parser')->getContext('action/current');

    dsp("Rebuild : $sPath");

    if (!in_array((string) $file, $this->get('exclude/run')->query())) {

      $manager = $this->getManager(self::PARSER_MANAGER);
      $manager->load($file, $this->aContexts, true, is_null($bRun) ? true : $bRun);
    }
//$parent->getContexts()->get('message');
    return '1';
  }

  public function asDOM() {

    $this->loadDefaultSettings();

    $common = $this->createArgument(array(
      'includes' => array('/\.vml$/'),
      'excludes' => array('/sylma', '/users', '/\.svn/'),
      'depth' => true,
    ));

    $root = $common;

    $dir = $this->getDirectory('/');
    $files = $dir->getFiles(array(
      '/\.vml$/'
    ), array(
      '|^/users|',
      '/\.svn/',
      '|^/sylma/modules/tester|',
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

