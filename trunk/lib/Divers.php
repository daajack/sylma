<?php
/**
 * Classe de template de page
 */

class AccessMenu extends HTML_List {
  
  public function __construct($sName = 'defaut', $aItems = Array()) {
    
    parent::__construct();
    $this->addAttribute('id', $sName);
    
    foreach ($aItems as $sItem => $aItem) {
      
      if (is_array($aItem) && array_key_exists('display', $aItem) && Controler::checkAuthentication($sItem)) {
        
        $oLink = new HTML_Tag('a');
        
        $oLink->addChild(t($aItem['display']));
        $oLink->addAttribute('href', $sItem);
        
        if (Controler::getAction() == $sItem) $aAttributes['class'] = 'item-current';
        else $aAttributes = array();
        
        $this->addItem($oLink, $aAttributes);
      }
    }
  }
}

class Menu extends HTML_List {

  public function __construct($sName = 'defaut', $aItems = Array()) {
    
    parent::__construct();
    $this->addAttribute('id', $sName);
    
    foreach ($aItems as $aItem) {
      
      if (isset($aItem['display']) && isset($aItem['href'])) {
        
        $oLink = new HTML_Tag('a');
        
        $oLink->addChild(t($aItem['display']));
        $oLink->addAttribute('href', $aItem['href']);
        
        $this->addItem($oLink);
      }
    }
  }
}
