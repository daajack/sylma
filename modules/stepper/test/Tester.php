<?php

namespace sylma\modules\stepper\test;
use sylma\core, sylma\modules\stepper;

class Tester extends stepper\Browser {

  const NS = 'http://2013.sylma.org/modules/stepper';
  const DIRECTORY = '/test/tmp';

  public function __construct(core\argument $args, core\argument $post) {

    $this->setDirectory($this->getManager(self::FILE_MANAGER)->getDirectory()->addDirectory(self::DIRECTORY));

    parent::__construct($args, $post);
  }

  public function clearDirectory() {

    $dir = $this->getDirectory();
    $aItems = array_merge($dir->getFiles(), $dir->getDirectories());

    foreach ($aItems as $item) {

      //dsp($item);
      $item->delete();
    }
  }

  protected function getSamplesDirectory() {

    $current = $this->getManager('fs')->extractDirectory(__FILE__);

    return $current->getDirectory('samples');
  }

  public function prepareSample() {

    $samples = $this->getSamplesDirectory();

    if ($sFile = $this->read('file', false)) {

      $sample = $samples->getFile($sFile);
    }
    else {

      $sample = $samples->getFile('sample01.tml');
    }

    $doc = $sample->asDocument();

    if ($sPath = $this->read('path', false)) {

      $page = $doc->getx('//ns:page', array('ns' => self::NS));
      $page->setAttribute('url', $sPath);
    }

    $this->clearDirectory();
    $dir = $this->getDirectory();
    $samplecopy = $dir->createFile($sample->getName());

    $doc->saveFile($samplecopy);
  }

  public function prepareCollection() {

    $samples = $this->getSamplesDirectory();
    $sample = $samples->getFile('collection_01.tml');

    $this->clearDirectory();
    $dir = $this->getDirectory();

    $dir->addDirectory('sub1');
    $dir->addDirectory('sub2');

    $samplecopy = $dir->createFile($sample->getName());
    $samplecopy->saveText($sample->execute());
  }

  public function getTimeshift() {

    $result = new \DateTime;
    $result->sub(new \DateInterval('P4D'));

    return $this->formatDateObject($result);
  }

  public function formatDate($sValue) {

    $date = new \DateTime($sValue);

    return $this->formatDateObject($date);
  }

  public function formatDateObject(\DateTime $date) {

    return $date->format('Y-m-d H:i');
  }

  public function testTimeshift() {


  }
}

