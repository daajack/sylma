<?php

namespace sylma\modules\tester;
use sylma\core, sylma\dom;

class Initializer extends Parser implements core\argumentable {

  protected $aManagers = array();
  protected $tmpArguments;

  public function createDocument($mContent = null) {

    return parent::createDocument($mContent);
  }

  public function createAction($sPath) {

    $this->loadDefaultSettings();

    return $this->create('action', array($this->getFile($sPath)));
  }

  public function createUser($sAlias = '', $bPrivate = false) {

    $manager = $this->getManager('user')->getManager();

    if (!$sAlias) {

      $result = $manager->create('user', array($manager));
      $result->loadPublic();
    }
    else {

      if (!array_key_exists($sAlias, $this->aUsers)) {

        $this->launchException('Unknown test user : ' . $sAlias);
      }

      $aGroups = $this->aUsers[$sAlias];

      $result = $manager->create('user', array($manager, $sAlias, $aGroups, $bPrivate));
    }

    return $result;
  }

  public function clearSylma(core\Initializer $init, core\user $user = null) {

    if (!$user) {

      $user = $this->createUser();
    }

    $this->setManagers(\Sylma::getManagers());

    \Sylma::setManagers(array(
      'parser' => $this->read('parser') ? \Sylma::getManager('parser') : null,
      'init' => $init,
      'user' => $user,
    ));
  }

  public function getFile($sPath = '', $bDebug = true) {

    return parent::getFile($sPath, $bDebug);
  }

  public function getManager($sName = '', $bDebug = true) {

    return parent::getManager($sName, $bDebug);
  }

  public function createArgument($mArguments, $sNamespace = '') {

    return parent::createArgument($mArguments, $sNamespace);
  }

  protected function onStart() {

    $this->setManagers(\Sylma::getManagers());
    $this->tmpArguments = \Sylma::getSettings();

    parent::onStart();
  }

  protected function saveProfile() {

    $this->restoreSylma();

    return parent::saveProfile();
  }

  public function restoreSylma() {

    \Sylma::setManagers($this->getManagers());
    \Sylma::setSettings($this->tmpArguments);
  }

  protected function onFinish() {

    header('HTTP/1.1 200 OK');
  }

  public function asArgument() {

    //$this->setManagers(\Sylma::getManagers());
    //$args = \Sylma::getSettings();
    //$this->tmpArguments = \Sylma::getSettings();
    //$args->set('debug/enable', false);

    $result = parent::asArgument();

    $this->restoreSylma();
    $init = $this->getManager('init');
    $init->setHeaderContent($init->getMime('html'));
    //$args->set('debug/enable', true);

    //\Sylma::setSettings($this->tmpArguments);

    return $result;
  }
}

