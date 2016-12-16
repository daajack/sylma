<?php

namespace sylma\storage\sql\crud;
use sylma\core, sylma\dom, sylma\storage\fs;

class Export extends core\module\Domed
{
  const FILE_MANAGER = 'fs/editable';
  
  public function __construct($args) {

    $this->loadDefaultSettings();
    $this->setDirectory(__FILE__);

    $this->name = $args['name'];
    $path = $args['path'];
    
    $doc = $this->getScript($path);
    $content = $doc->asString(dom\handler::STRING_HEAD); // | dom\handler::STRING_INDENT

    $source = $this->getFile('default.xlsx');
    
    $dir = $this->getManager('fs/cache')->getDirectory();
    $file = $dir->getFile(uniqid('export-'), fs\file::DEBUG_EXIST);
    $file->saveText($source->execute());
    
    $zip = new \ZipArchive;
//dsp($content);
    if ($zip->open($file->getRealPath()) === TRUE) {

      $zip->addFromString('xl/worksheets/sheet1.xml', $content);
      $zip->close();
      
    } else {
      
      $this->launchException('Cannot find xlsx base file');
    }
    
    $this->file = $file;
  }
  
  public function __toString() {

    $name = 'export-' . $this->name . '.xlsx';

    header('Content-Disposition: attachment; filename="' . $name);
    header("Pragma: no-cache"); 
    header("Expires: 0");

    $file = $this->file;
    
    $content = $file->execute();
    $file->delete();

    return $content;
  }
}

