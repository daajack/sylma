<?php

namespace sylma\device;
use sylma\core, sylma\template\binder;

class JSON extends binder\context\JSON
{
  const DEVICE_SETTINGS = 'device';
  
  public function __construct() {
  
    parent::__construct();
    $this->setupDevice();
  }
  
  protected function setupDevice() {

    $args = \Sylma::get(self::DEVICE_SETTINGS);

    if ($args->read('enable', false)) {

      $device = $this->getManager('init')->create('device');
      \Sylma::setManager('device', $device);

      $device->setSettings($args);
    }
  }
}