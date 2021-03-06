<?php

namespace sylma\core\window\classes;
use sylma\core, sylma\dom, sylma\storage\fs, sylma\core\functions, sylma\modules\html;

class Container extends core\module\Domed {

  const SETTINGS_FACTORY_PRIORITY = true;

  const CONTENT_ARGUMENT = 'sylma-paths';
  const CONTENT_SUB = 'content';

  protected $contexts;
  protected $content;

  public function __construct(core\argument $args, core\argument $post, core\argument &$contexts) {

    $this->setSettings($this->getManager('init')->getArgument('window'));
    $this->setArguments($args);
    $this->setPost($post);

    $this->setPaths($this->getArgument(self::CONTENT_ARGUMENT)->query());
    $this->setArgument(self::CONTENT_ARGUMENT, null);

    //$this->content = $this->buildWindowScript($this->getPaths());
  }

  protected function setPaths(array $aPaths) {

    $this->aPaths = $aPaths;
  }

  protected function getPaths() {

    return $this->aPaths;
  }

  protected function getContexts() {

    return $this->contexts;
  }

  protected function setContexts(core\argument $contexts) {

    $this->contexts = $contexts;
  }

  protected function setPost(core\argument $val) {

    $this->post = $val;
  }

  protected function getPost() {

    return $this->post;
  }

  protected function loadContexts(dom\document $doc) {

    foreach ($this->getContexts()->query() as $sName => $context) {

      $this->loadContext($sName, $context, $doc);
    }
  }

  protected function loadContext($sName, $context, dom\document $doc) {

    // TODO : define behaviour
  }

  protected function loadInfos(dom\handler $doc) {

    \Sylma::load('/sylma/core/functions/Numeric.php');

    $parser = $this->getManager('parser');
    $path = $this->getManager('path');
    $user = $this->getManager('user');
    $init = $this->getManager('init');

    $js = $this->getContexts()->get('js-common');
    $js->add($this->getFile('/#sylma/ui/mootools-min.js'));
    $js->add($this->getFile('/#sylma/ui/Main.js'));

    $aBuilded = $parser->aBuilded;
    $aLoaded = $parser::$aLoaded;

    $iLoaded = 0;
    array_walk($aLoaded, function (&$item, $key) use (&$iLoaded) {
      $iLoaded += $item;
      $item = "$key : ($item)";
    });

    $file = $path->asFile();

    $sDevice = '[unknown]';

    if ($device = $this->getManager('device', false)) {

      $sDevice = $device->isDevice('tablet') ? 'tablet' : ($device->isDevice('mobile') ? 'mobile' : 'desktop');
    }
    
    $link = $file ? $file->getSystemPath() : '';

    $content = $this->createArgument(array(
      'ul' => array(
        '#li' => array(
          array(
            '#button' => array(
              array(
                '@type' => 'button',
                '@onclick' => "sylma.ui.send('/sylma/modules/rebuild/standalone', {path : '$file', force : true}, null, true);",
                'rebuild'
              ),
              array(
                '@type' => 'button',
                '@onclick' => "window.location = 'netbeans://" . $link . "'",
                (string) $file,
              ),

              array(
                '@type' => 'button',
                '@onclick' => "sylma.ui.debugSource();",
                'source'
              )
            )
          ),
          'user : ' . $user->getName(),
          'groups : ' . implode(',', $user->getGroups()),
          'time : ' . functions\numeric\formatFloat($init->getElapsedTime()),
          'device : ' . $sDevice,
          'builded : ' . count($aBuilded),
          array(
            'ul' => array(
              '#li' => array_map('strval', $aBuilded),
            ),
          ),
          'loaded : ' . $iLoaded,
          array(
            'ul' => array(
              '#li' => $aLoaded,
            ),
          ),
        ),
      ),
    ), $this->getNamespace('html'));

    return $content;
  }

  protected function parseDOM(dom\domable $val) {

    return $val->asDOM();
  }

  public function getContent() {

    return $this->runStack($this->getPaths());
  }

  protected function send404() {

    $this->getManager('init')->send404();
  }

  protected function runStack(array $aPaths) {

    $this->setDirectory(__FILE__);

    $args = $this->getArguments();
    $post = $this->getPost();

    if (!$file = $this->getFile(current($aPaths), false)) {

      if (\Sylma::isAdmin()) {

        dsp('Action not found');
      }

      $this->send404();
      $content = $this->getError();
    }
    else {

      try {

        $content = $this->prepareMain($file, $args, $post);
      }
      catch (core\exception $e) {

        $e->save(false);
        $content = $this->getError();
      }
    }

    while (next($aPaths)) {

      $sPath = current($aPaths);

      $args->set(self::CONTENT_SUB, $content);
      $content = $this->getManager(self::PARSER_MANAGER)->load($this->getFile($sPath), array(
        'arguments' => $args,
        'post' => $post,
        'contexts' => $this->getContexts(),
      ), null, true, true);
    }

    return $content;
  }

  public function getError() {

    $this->loadDefaultSettings();

    return $this->runScript($this->getFile($this->read('error/path')));
  }

  protected function prepareMain(fs\file $file, core\argument $args, core\argument $post) {

    switch ($file->getExtension()) {

      case 'vml' : $result = $this->runScript($file, $args, $post); break;
      default :

        $this->launchException('Unknown extension for window content');
    }

    if (!$result) {

      $result = '';
      //$this->launchException('No content for main window');
    }

    return $result;
  }

  protected function runScript(fs\file $file, core\argument $args = null, core\argument $post = null, core\argument $debug = null) {

    if (!$debug) {

      $debug = $this->getSettings();
    }

    $builder = $this->getManager(self::PARSER_MANAGER);

    $result = $builder->load($file, array(
      'arguments' => $args,
      'post' => $post,
      'contexts' => $this->getContexts(),
    ), $debug->read('debug/update', false), $debug->read('debug/run', false), true);

    return $result;
  }

  protected function cleanResult(dom\handler $doc, fs\file $file = null) {

    $cleaner = new html\Cleaner($file);

    return $cleaner->clean($doc);
  }

  public function prepare($sContent) {

    return $sContent;
  }
}
