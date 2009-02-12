<?php
/*
 * Classes des type de sorties
 **/

interface Main {
  
  public function setContent($mValue = '');
}

class Html extends HTML_Document {
  
  public function __construct() {
    
    // $oTemplate = ;
    // $oTemplate->set($this->get('//html'));
    parent::__construct('/xml/html.xml', null, 'file');
    
    $this->addJS('/web/global.js');
    $this->addCSS('/web/global.css');
    $this->addCSS('/web/main.css');
    
    // Préparation / insertion des blocs
    
    $aMenuPrimary = Controler::getRights();
    
    if (Controler::getUser()->isReal()) unset($aMenuPrimary['/utilisateur/login']);
    else unset($aMenuPrimary['/redirection/utilisateur/logout']);
    
    // Contenu
    
    // Info utilisateur
    
    if (Controler::getUser()->isReal()) {
      
      $oUserInfo = new XML_Tag('div', '', array('id' => 'user-info'));
      $oUserInfo->add(
        new HTML_A('/utilisateur/edit/'.Controler::getUser()->getArgument('id'), Controler::getUser()->getBloc('full_name')),
        ' ('.implode(', ', Controler::getUser()->getRoles()).')');
      
      $this->get("//div[@id='header']")->add($oUserInfo);
    }
    
    // Titre & menu
    
    $this->get('//h1//span')->set(SITE_TITLE);
    $this->get("//div[@id='sidebar']")->add(new AccessMenu('menu-primary', $aMenuPrimary));
    
    // Messages & contenu
    
    $this->setBloc('content-title', new XML_Tag('h2'));
    $this->setBloc('content', new HTML_Tag('div', '', array('id' => 'content')));
  }
  
  public function setContent($mValue = '') {
    
    $this->setBloc('action', $mValue);
  }
  
  public function __toString() {
    
    // Infos système
    
    $oMessages = new Messages(array('system'));
    $oMessages->addMessages(Controler::getMessages()->getMessages('system'));
    
    if (Controler::isAdmin() && $oMessages->hasMessages('system') && in_array('system', Controler::getMessages()->getAllowedMessages())) {
      
      $oSystem = new HTML_Div(new HTML_Strong(t('Infos système')));
      $oSystem->addStyle('margin', '5px');
      
      $oMessages = $oSystem->add($oMessages);
      $oMessages->setAttribute('style', 'margin-top: 5px;');
      
      $this->get("//div[@id='sidebar']")->shift($oSystem);
    }
    
    // Supression des messages système dans le panneau de messages principal
    
    if (in_array('system', Controler::getMessages()->getAllowedMessages()))
      Controler::getMessages()->getBloc('allowed')->get('//system')->remove();
    
    // Contenu
    
    $this->get('//title')->add(SITE_TITLE, ' - ', $this->getBloc('content-title')->read());
    
    $oContent = $this->getBloc('content');
    
    if (Controler::getMessages()->getMessages()) $oContent->add(Controler::getMessages());
    $oContent->add($this->getBloc('content-title'), $this->getBloc('action'));
    
    $this->get("//div[@id='center']")->add($oContent);
    
    // Controler::getMessages()->setMessages('system');
    
    return parent::__toString();
  }
}

class Redirection {
  
  public function setContent($mValue = '') {
    
    return Controler::errorRedirect('Redirection incorrecte !');
  }
}
class Popup extends HTML_Document implements Main {
  
  public function __construct() {
    
    parent::__construct('/template/popup');
    
    $this->addCSS('/web/global.css');
    $this->addCSS('/web/popup.css');
    
    // Contenu
    
    $oContent = new HTML_Tag('div', '', array('id' => 'content'));
    $oContent->setBloc('content-title', new HTML_Tag('h2'));
    $oContent->setBloc('message', Controler::getMessages()); // pointeur
    
    $this->setBloc('content-title', $oContent->getBloc('content-title'));
    $this->setBloc('content', $oContent);
  }
  
  public function setContent($mValue = '') {
    
    $this->getBloc('content')->setBloc('action', $mValue);
  }
  
  public function __toString() {
    
    // Supression des messages système dans le panneau de messages principal
    
    Controler::getMessages()->setMessages('system');
    
    // Contenu
    
    $this->getBloc('content')->addBloc('content-title');
    $this->getBloc('content')->addBloc('message');
    $this->getBloc('content')->addBloc('action');
    
    return parent::__toString();
  }
}

class Form extends HTML_Tag implements Main {
  
  private $oRedirect = null;
  
  public function __construct() {
    
    $this->setBloc('content-title', new HTML_Tag('h4', '', array('class' => 'ajax-title'), true));
  }
  
  public function setContent($mValue = '') {
    
    $this->setBloc('content', $mValue);
  }
  
  public function addJS($sHref) {
    
    $this->getBloc('header')->addChild(new HTML_Script($sHref));
  }
  
  public function addCSS($sHref) {
    
    $this->getBloc('header')->addChild(new HTML_Style($sHref));
  }
  
  public function isRedirect() {
    
    return $this->getRedirect();
  }
  
  public function setRedirect($oRedirect = null) {
    
    $this->oRedirect = $oRedirect;
  }
  
  public function getRedirect() {
    
    return $this->oRedirect;
  }
  
  public function __toString() {
    
    if ($this->isRedirect()) {
      
      $sAction = $this->getRedirect()->getArgument('action');
      
      $this->addChild($sAction.'<>');
      if ($sAction == 'script') $this->addChild($this->getRedirect()->getArgument('script'));
      else if ($sAction == 'redirect') $this->addChild($this->getRedirect());
      
    } else {
      
      Controler::getMessages()->setMessages('system');
      
      $this->addChild('display<>');
      $this->addChild($this->getBloc('content')->getAttribute('action')->getValue().'<>');
      
      // Suppression du nom du form pour empêcher l'affichage
      $this->getBloc('content')->setName();
      
      $this->addBloc('header');
      $this->addBloc('content-title');
      $this->addChild(new HTML_Div($this->getBloc('content'), array('class' => 'ajax-content')));
      $this->addChild(new HTML_Div('', array('class' => 'ajax-shadow')));
      $this->addChild(new HTML_Div('', array('class' => 'ajax-bulle')));
      $this->addChild(Controler::getMessages());
      
      // $this->addBloc('content');
    }
    
    return parent::__toString();
  }
}

class Simple extends XML_Tag implements Main {
  
  public function setContent($mValue = '') {
    
    $this->addChild($mValue);
  }
}

class Xml extends XML_Document implements Main {
  
  public function setContent($mValue = '') {
    
    header('Content-type: text/xml');
    if (is_string($mValue)) $this->add($mValue);
    else $this->set($mValue);
    
    if (!$this->getRoot()) $this->appendChild(new XML_Element('message', $mValue));
  }
}


