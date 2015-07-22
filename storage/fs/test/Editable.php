<?php

namespace sylma\storage\fs\test;
use \sylma\modules\tester, \sylma\core, \sylma\dom, \sylma\storage\fs;

class Editable extends tester\Profiler implements core\argumentable {

  const NS = 'http://www.sylma.org/storage/fs/test';

  protected $sTitle = 'Update';

  /**
   * @var fs\directory
   */
  protected $tmp;

  public function __construct() {

    $this->getManager('dom');

    $this->setDirectory(__file__);
    $this->setNamespace(self::NS, 'self');
    $this->setArguments('../settings.yml');
    parent::__construct();

    $fs = $this->getManager('fs/cache');
    //dspf($this->getDirectory((string) $user->getDirectory()));
    //$this->throwException('t');
    $dir = $fs->getDirectory()->createDirectory();
    $this->tmp = $dir;

    $controler = $this->create('manager', array(\Sylma::PATH_CACHE, true));
    $controler->loadDirectory((string) $dir);

    $this->setFiles(array($this->getFile('editable.xml')));

    $this->setManager($controler);
  }

  protected function onFinish() {

    if ($this->tmp) $this->tmp->delete();
  }
}


