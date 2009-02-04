<?php
/**
 * Classe de template de page
 */

class AccessMenu extends HTML_Ul {
  
  public function __construct($sName = 'defaut', $aItems = Array()) {
    
    parent::__construct();
    
    $this->setAttribute('id', $sName);
    foreach ($aItems as $sItem => $aItem) {
      
      if (is_array($aItem) && array_key_exists('display', $aItem) && Controler::checkAuthentication($sItem)) {
        
        $oLink = new HTML_Tag('a');
        
        $oLink->add(t($aItem['display']));
        $oLink->setAttribute('href', $sItem);
        
        if (Controler::getAction() == $sItem) $aAttributes['class'] = 'item-current';
        else $aAttributes = array();
        
        $this->addItem($oLink, $aAttributes);
      }
    }
  }
}

class Menu extends HTML_Ul {

  public function __construct($sName = 'defaut', $aItems = Array()) {
    
    parent::__construct();
    $this->setAttribute('id', $sName);
    
    foreach ($aItems as $aItem) {
      
      if (isset($aItem['display']) && isset($aItem['href'])) {
        
        $oLink = new HTML_Tag('a');
        
        $oLink->addChild(t($aItem['display']));
        $oLink->setAttribute('href', $aItem['href']);
        
        $this->addItem($oLink);
      }
    }
  }
}
