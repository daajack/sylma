<?php
/*
 * Classes des type de sorties
 **/

interface Main {
  
  public function loadAction($oAction);
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

class Redirection implements Main {
  
  public function loadAction($oAction) {
    
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
  
  public function loadAction($oAction) {
    
    // Supression des messages système dans le panneau de messages principal
    
    Controler::getMessages()->setMessages('system');
    
    // Contenu
    
    $this->getBloc('content')->addBloc('content-title');
    $this->getBloc('content')->addBloc('message');
    $this->getBloc('content')->add($oAction);
    
    $this->addBloc('content');
  }
}

class Form extends XML_Helper implements Main {
  
  private $oRedirect = null;
  
  public function __construct() {
    
    parent::__construct();
    $this->setBloc('content-title', new HTML_Tag('h4', '', array('class' => 'ajax-title'), true));
  }
  
  public function loadAction($oAction) {
    
    $this->setBloc('content', $oAction);
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

class Simple extends XML_Element implements Main {
  
  public function loadAction($oAction) {
    
    $this->add($oAction);
  }
}

class Xml extends XML_Document implements Main {
  
  public function loadAction($oAction) {
    
    header('Content-type: text/xml');
    
    $oResult = $oAction->parse();
    
    if (is_string($oResult)) $this->add('root', $oResult);
    else $this->set($oResult);
  }
}


