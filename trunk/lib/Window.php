<?php
/*
 * Classes des type de sorties
 **/

interface Main {
  
  public function loadAction($oAction);
}

class Any implements Main {
  
  private $oFile = null;
  
  public function loadAction($oFile) {
    
    $this->oFile = $oFile;
  }
  
  public function __toString() {
    
    if ($this->oFile) {
      
      $sPath = MAIN_DIRECTORY.'/'.$this->oFile;
      
      Controler::setContentType($this->oFile->getExtension());
      header('Content-Length: ' . $this->oFile->getSize());
      header('Content-Disposition: attachment; filename=' . basename($sPath));
      // header('Content-Description: File Transfer');
      readfile($sPath);
    }
    
    return '';
  }
}

class Img implements Main {
  
  private $oFile = null;
  
  public function loadAction($oFile) {
    
    if ($oFile instanceof XML_File) $this->oFile = $oFile;
  }
  
  public function __toString() {
    
    if ($this->oFile) {
      
      $sFilePath = (string) $this->oFile;
      
      $sExtension = strtolower($this->oFile->getExtension());
      if ($sExtension == 'jpg') $sExtension = 'jpeg';
      
      $aExtensions = array('jpeg', 'png', 'gif');
      
      if (in_array($sExtension, $aExtensions)) {
        
        Controler::setContentType($sExtension);
        
        $sFunction = 'imagecreatefrom'.strtolower($sExtension);
        
        $im = @$sFunction(MAIN_DIRECTORY.$sFilePath)
        or die("Cannot Initialize new GD image stream");
        
        // imagefilter($im, IMG_FILTER_GRAYSCALE);
        
        $sFunction = 'image'.$sExtension;
        
        $sFunction($im);
        imagedestroy($im);
      }
      
    } else Controler::error404();
  }
}

class Redirection implements Main {
  
  public function loadAction($oAction) {
    
    return Controler::errorRedirect('Redirection incorrecte !');
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

class Txt implements Main {
  
  private $sContent = '';
  
  public function loadAction($oAction) {
    
    if ($oAction) {
      
      if ($oAction instanceof XML_Action) {
        
        $this->sContent = (string) $oAction->parse();
        
      } else if ($oAction instanceof XML_File) {
        
        /*
        $oFinfo = new finfo(FILEINFO_MIME, ini_get('mime_magic.magicfile')); // Retourne le type mime
        
        if (!$oFinfo) {
          
          $this->sContent = "Échec de l'ouverture de la base de données fileinfo";
          
        } else {
          
          if($sMime = $oFinfo->file(MAIN_DIRECTORY.$sPath)) {
            
            $this->sContent = $sMime.'hello';
            $oFinfo->close();
            
          } else $this->sContent = 'Mime introuvable !';
          
        }
        */
        
        Controler::setContentType(Controler::getPath()->getExtension());
        
        $this->sContent = file_get_contents(MAIN_DIRECTORY.$oAction);
        
      } else $this->sContent = (string) $oAction;
      
    } else Controler::error404();
  }
  
  public function __toString() {
    
    return $this->sContent;
  }
}

class WindowAction extends XML_Document implements Main {
  
  public function loadAction($oAction) {
    
    Controler::setContentType('xml');
    
    $oRoot = $this->set('action');
    $oContent = $oRoot->addNode('content');
    
    if ($oAction instanceof XML_Action) {
      
      $oResult = $oAction->parse();
      $oContent->add($oResult);
      
    } else if ($oAction instanceof XML_File) {
      
      $oContent->add(new XML_Document((string) $oAction));
      
    } else {
      
      $oContent->add($oAction);
    }
    
    $oRoot->addNode('messages', Controler::getMessages()->parse());
  }
  
  public function __toString() {
    
    $oView = new XML_Document($this);
    $oView->formatOutput();
    
    return $oView->__toString();
  }
}

class Xml extends XML_Document implements Main {
  
  private $sMode = '';
  
  public function loadAction($oAction) {
    
    Controler::setContentType('xml');
    $this->sMode = Controler::getPath()->getAssoc('xml-mode');
    
    if ($oAction instanceof XML_Action) {
      
      $oResult = $oAction->parse();
      
      if (is_string($oResult)) $this->add('root', $oResult);
      else $this->set($oResult);
      
    } else if ($oAction instanceof XML_File) {
      
      $this->set(new XML_Document((string) $oAction));
      
    } else {
      
      $this->set(new XML_Element('root', (string) $oAction));
    }
  }
  
  public function __toString() {
    
    if ($this->sMode == 'html') {
      
      $oView = new XML_Document($this);
      $oView->formatOutput();
      
      return $oView->__toString(true);
    }
    
    else return parent::__toString();
  }
}


