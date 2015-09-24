<?php

namespace sylma\storage\fs\basic\security;
use \sylma\core, \sylma\dom, \sylma\storage\fs;

class Cached extends core\module\Argumented implements fs\security\manager {

  const FILENAME = 'directory.sml';
  const USER_CONTROLER = 'user';

  private $parent;
  protected $self = array();
  protected $propagate = array();
  protected $files = array();

  public function __construct(fs\directory $dir) {

    $this->setParent($dir);
    $this->loadSettings();
  }

  protected function loadSettings() {

    require_once(dirname(dirname(__dir__)) . '/resource.php');

    if ($file = $this->getParent()->getFreeFile(self::FILENAME, fs\resource::DEBUG_NOT)) {

      $cache = $this->getManager('parser')->getCachedFile($file);

      if (\Sylma::isAdmin()) {

        $aSettings = $this->loadSettingsAdmin($file, $cache);
      }
      else {

        $aSettings = $this->loadSettingsOther($cache);
      }

      $this->self = array_key_exists('self', $aSettings) ? $aSettings['self'] : array();
      $this->propagate = array_key_exists('propagate', $aSettings) ? $aSettings['propagate'] : array();
      $this->files = array_key_exists('files', $aSettings) ? $aSettings['files'] : array();
    }
  }

  protected function loadSettingsAdmin(fs\file $file, fs\editable\file $cache) {

    if (!$cache->doExist() || $cache->getUpdateTime() < $file->getUpdateTime()) {

      $aResult = $this->buildSettings($file, $cache);
    }
    else {

      $aResult = $this->includeSettings($cache);
    }

    return $aResult;
  }

  protected function loadSettingsOther(fs\editable\file $cache) {

    if (!$cache->doExist()) {

      $this->launchException('Missing security file');
    }

    $aResult = $this->includeSettings($cache);

    return $aResult;
  }

  protected function includeSettings(fs\file $cache) {

    return include($cache->getRealPath());
  }

  protected function buildSettings(fs\file $file, fs\file $cache) {

    $parser = $this->getManager()->create('security/parser', array($this->getManager()));
    $aResult = $parser->build($file, $cache);

    return $aResult;
  }

  protected function getManager($sName = '', $bDebug = true) {

    if (!$sName) {

      $result = $this->getParent()->getManager();
    }
    else {

      $result = parent::getManager($sName, $bDebug);
    }

    return $result;
  }

  protected function getParent() {

    return $this->parent;
  }

  protected function setParent($dir) {

    $this->parent = $dir;
  }

  public function getDirectory() {

    return $this->checkRights($this->self);
  }

  public function getPropagation() {

    return $this->checkRights($this->propagate);
  }

  public function getFile($sName) {

    $aFiles = $this->files;

    if (array_key_exists($sName, $aFiles)) {

      $aResult = $this->checkRights($aFiles[$sName]);
    }
    else {

      $aResult = array();
    }

    return $aResult;
  }

  /*
   * Extract and check validity of security datas in element
   *
   * @param* dom\element $el The element to extract the values from
   * @return an array of validated security parameters
   * - @key user-mode will indicate the user's current rights on the file
   **/
  protected function checkRights(array $aRights = null) {

    $aResult = array();

    if ($aRights) {

      $sOwner = $aRights['owner'];
      $sGroup = $aRights['group'];
      $sMode = $aRights['mode'];

      $user = $this->getManager()->getManager(self::USER_CONTROLER);
      $iMode = $user->getMode($sOwner, $sGroup, $sMode);

      if ($iMode !== null) {

        $aResult = array(
          'owner' => $sOwner,
          'group' => $sGroup,
          'mode' => $sMode,
          'user-mode' => $iMode
        );
      }
    }

    return $aResult;
  }
}

