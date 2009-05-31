<?php
/*
 * Classes des type de sorties
 **/

interface Main {
  
  public function setContent($mValue = '');
}

class Img {
  
  public function __toString() {
    
    $sFilePath = MAIN_DIRECTORY.Controler::getAction();
    $aAllowedExtensions = array('jpg', 'png', 'gif');
    
    if (file_exists($sFilePath)) {
      
      $iExtensionPosition = strrpos($sFilePath, '.');
      $sExtension = $iExtensionPosition ? substr($sFilePath, $iExtensionPosition + 1) : '';
      if ($sExtension == 'jpg') $sExtension = 'jpeg';
      
      header("Content-type: image/".$sExtension);
      
      $sFunction = 'imagecreatefrom'.strtolower($sExtension);
      
      $im = @$sFunction($sFilePath)
      or die("Cannot Initialize new GD image stream");
      
      // imagefilter($im, IMG_FILTER_GRAYSCALE);
      
      $sFunction = 'image'.$sExtension;
      
      $sFunction($im);
      imagedestroy($im);
    }
  }
}

class Html extends HTML_Document implements Main {
  
  public function configure() {
    
    // $oTemplate = ;
    // $oTemplate->set($this->get('//html'));
    parent::__construct('/xml/html.xml');
    
    $this->addJS('/web/global.js');
    $this->addCSS('/web/global.css');
    $this->addCSS('/web/main.css');
    
    // Préparation / insertion des blocs
    
    $aMenuPrimary = Controler::getRights();
    
    if (Controler::getUser()->isReal()) unset($aMenuPrimary['/utilisateur/login']);
    else unset($aMenuPrimary['/redirection/utilisateur/logout']);
    
    // Contenu
    
    // Info utilisateur
    
    if (Controler::getUser()->isReal()) $this->get("//ns:div[@id='header']")->add(Controler::getUser());
    
    // Titre & menu
    
    $this->get('//ns:h1//ns:span')->set(SITE_TITLE);
    $this->get("//ns:div[@id='sidebar']")->add(new AccessMenu('menu-primary', $aMenuPrimary));
    
    // Messages & contenu
    
    $this->setBloc('content-title', new XML_Tag('h2'));
    $this->setBloc('content', new HTML_Tag('div', '', array('id' => 'content')));
  }
  
  public function setContent($mValue = '') {
    
    $this->setBloc('action', $mValue);
  }
  
  public function __toString() {
    
    // Infos système
    
    if (Controler::isAdmin() && Controler::getMessages()->getBloc('allowed')->get('//system/*')) {
      
      $oMessages = new Messages(array('system'), Controler::getMessages()->getMessages('system'));
      
      $oSystem = new HTML_Div();
      $oSystem->addStyle('margin', '10px 5px');
      
      $oMessages = $oSystem->add($oMessages);
      $oMessages->setAttribute('style', 'margin-top: 5px;');
      
      $this->get("//ns:div[@id='sidebar']")->shift($oSystem);
    }
    
    // Supression des messages système dans le panneau de messages principal
    
    if ($oSystem = Controler::getMessages()->getBloc('allowed')->get('//system')) $oSystem->remove();
    
    // Contenu
    
    $this->get('//ns:title')->add(SITE_TITLE, ' - ', $this->getBloc('content-title')->read());
    
    $oContent = $this->getBloc('content');
    
    if (!$this->getBloc('content-title')->isEmpty()) $oContent->add($this->getBloc('content-title'));
    $oContent->add($this->getBloc('action'));
    $oContent->shift(XML_Controler::getMessages(), Action_Controler::getMessages(), Controler::getMessages());
    
    $this->get("//ns:div[@id='center']")->add($oContent);
    
    // Controler::getMessages()->setMessages('system');
    
    return parent::__toString();
  }
}

class Redirection implements Main {
  
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

class Form extends XML_Helper implements Main {
  
  private $oRedirect = null;
  
  public function __construct() {
    
    parent::__construct();
    $this->setBloc('content-title', new HTML_Tag('h4', '', array('class' => 'ajax-title'), true));
  }
  
  public function setContent($mValue = '') {
    
    $this->setBloc('content', $mValue);
  }
  
  public function addJS($sHref) {
    
    $this->getBloc('header')->add(new HTML_Script($sHref));
  }
  
  public function addCSS($sHref) {
    
    $this->getBloc('header')->add(new HTML_Style($sHref));
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
      
      $this->add($sAction.'<>');
      if ($sAction == 'script') $this->add($this->getRedirect()->getArgument('script'));
      else if ($sAction == 'redirect') $this->add($this->getRedirect());
      
    } else {
      
      Controler::getMessages()->setMessages('system');
      
      $this->add('display<>');
      $this->add($this->getBloc('content')->getAttribute('action')->getValue().'<>');
      
      // Suppression du nom du form pour empêcher l'affichage
      $this->getBloc('content')->setName();
      
      $this->addBloc('header');
      $this->addBloc('content-title');
      $this->add(new HTML_Div($this->getBloc('content'), array('class' => 'ajax-content')));
      $this->add(new HTML_Div('', array('class' => 'ajax-shadow')));
      $this->add(new HTML_Div('', array('class' => 'ajax-bulle')));
      $this->add(Controler::getMessages());
      
      // $this->addBloc('content');
    }
    
    return parent::__toString;
  }
}

class Simple extends XML_Tag implements Main {
  
  public function setContent($mValue = '') {
    
    $this->add($mValue);
  }
}

class Xml extends XML_Document implements Main {
  
  public function setContent($mValue = '') {
    
    header('Content-type: text/xml');
    if (is_string($mValue)) $this->addNode('root', $mValue);
    else $this->set($mValue);
    
    if (!$this->getRoot()) $this->appendChild(new XML_Element('message', $mValue));
  }
}


