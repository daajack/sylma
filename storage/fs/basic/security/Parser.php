<?php

namespace sylma\storage\fs\basic\security;
use \sylma\core, \sylma\dom, \sylma\storage\fs;

class Parser extends core\module\Argumented {

  public function __construct($manager) {

    $this->setManager($manager);
  }

  public function build(fs\file $file, fs\file $cache) {

    $settings = $this->loadDocument($file);
    $aResult = $settings->asArray();
    $sContent = var_export($settings->asArray(), true);

    $cache->saveText('<?php return ' . $sContent . '; ?>');

    return $aResult;
  }

  public function loadDocument(fs\file $file) {

    $doc = $file->getFreeDocument($this->getNS());
    $options = $this->getManager()->create('security/options', array($doc));

    $result = $this->createArgument(array());
    $aFiles = array();

    foreach ($options as $row) {

      $rights = $this->extractRights($row->get('security'));

      switch ($row->getName()) {

        case 'self' :

          $result->set('self', $rights);
          break;

        case 'propagate' :

          $result->set('propagate', $rights);
          break;

        case 'file' :

          $aFiles[$row->read('@name')] = $rights;
          break;

        default :

          $this->launchException('Unknown element in security file');
      }
    }

    if ($aFiles) {

      $result->set('files', $aFiles);
    }

    return $result;
  }

  protected function extractRights(core\argument $rights) {

    return array(
      'owner' => $rights->read('owner'),
      'group' => $rights->read('group'),
      'mode' => $rights->read('mode'),
    );
  }
}

