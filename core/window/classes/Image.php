<?php

namespace sylma\core\window\classes;
use sylma\core, sylma\storage\fs;

class Image extends File {

  public function asString() {

    if ($sSize = $this->getManager('path')->readArgument('format', false)) {

      $size = $this->get('format/' . $sSize);

      $file = $this->getFile();

      $fs = $this->getControler('fs/cache');
      $dir = $fs->getDirectory()->addDirectory((string) $file->getParent());

      $sName = "{$file->getSimpleName()}_$sSize.{$file->getExtension()}";
      $cache = $dir->getFile($sName, fs\resource::DEBUG_EXIST);

      if (!$cache->doExist() || $cache->getUpdateTime() < $file->getUpdateTime() || $this->read('rebuild')) {

        $builder = $this->create('builder', array($file));
        $bCrop = $size->read('crop', false) !== false;
        $builder->build($cache, $size->read('width'), $size->read('height'), $size->read('filter', false), $bCrop);
      }

      $this->setFile($cache);
    }

    return parent::asString();
  }
}
