<?php
/**
 * Classes de génération de code HTML
 * Auteur : Rodolphe Gerber
 */

/*
 * Tag
 **/
class HTML_Tag extends XML_Tag {
  
}

/*
 * Attribut style
 **/
class HTML__Style extends HTML_Tag {
  
  var $sName;
  var $content;
  
  public function __construct($sName, $content) {
    
    $this->name = $sName;
    $this->content = $content;
  }

  public function __toString() {
    
    return $this->name.' : '.$this->content.';';
  }
}

/*
 * HTML
 **/

class HTML_Template extends HTML_Tag {
  
  private $sPath = '';
  private $aArguments = array();
  
  public function __construct($sPath, $aArguments = array(), $sName = '') {
    
    parent::__construct($sName);
    
    $this->setTemplate($sPath);
    $this->setArguments($aArguments);
  }
  
  public function setArgument($sKey, $mValue) {
    
    $this->aArguments[$sKey] = $mValue;
  }
  
  public function setArguments($aArguments = array()) {
    
    if (is_array($aArguments)) $this->aArguments = $aArguments;
  }
  
  public function getArgument($sKey) {
    
    return isset($this->aArguments[$sKey]) ? $this->aArguments[$sKey] : null;
  }
  
  public function getArguments() {
    
    return $this->aArguments;
  }
  
  /*
   * Retourne le chemin du template
   **/
  
  public function getTemplate() {
    
    return $this->sPath;
  }
  
  public function setTemplate($sPath = '') {
    
    if (!$sPath) return false;
    else if ($sPath[0] == '/') $sPath = substr($sPath, 1);
    
    $sPath .= '.tpl.php';
    
    if (file_exists(Controler::getDirectory().$sPath)) $this->sPath = $sPath;
    else if (Controler::isAdmin()) Controler::addMessage(sprintf(t('Le template "%s" semble ne pas exister !'), new HTML_Strong($sPath)), 'error');
  }
  
  public function __toString() {
    
    if (!DEBUG) {
      
      ob_start();
      include($this->getTemplate());
      $this->addChild(ob_get_clean());
      
    } else {
      
      require($this->getTemplate());
    }
    
    return parent::__toString();
  }
}

class HTML extends HTML_Template {
  
  public function __construct($sTemplatePath) {
    
    parent::__construct($sTemplatePath);
    
    $this->addChild('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
    $this->setBloc('header', new HTML_Tag());
  }
  
  public function addJS($sHref) {
    
    $this->getBloc('header')->addChild(new HTML_Script($sHref));
  }
  
  public function addCSS($sHref = '') {
    
    $this->getBloc('header')->addChild(new HTML_Style($sHref));
  }
  
  public function addIECSS($sHref = '', $sVersion = '') {
    
    $this->getBloc('header')->addChild(new HTML_IEComment(new HTML_Style($sHref), $sVersion));
  }
}

class HTML_Script extends HTML_Tag {
  
  public function __construct($sSrc = '', $oChild = null) {
    
    parent::__construct('script');
    $this->setAttribute('type', 'text/javascript');
    $this->forceClosure();
    
    if ($sSrc) $this->setAttribute('src', $sSrc);
    else $this->addChild($oChild);
  }
}

class HTML_Style extends HTML_Tag {
  
  public function __construct($sHref = '', $oChild = null) {
    
    $this->setAttribute('type', 'text/css');
    
    if ($sHref) {
      
      parent::__construct('link');
      $this->setAttribute('rel', 'stylesheet');
      $this->setAttribute('href', $sHref);
      $this->setAttribute('media', 'screen, projection');
      
    } else {
      
      parent::__construct('style');
      $this->addChild($oChild);
    }
  }
}

class HTML_Comment {
  
  private $oValue = '';
  
  public function __construct($oValue = '') {
    
    $this->oValue = $oValue;
  }
  
  public function __toString() {
    
    return '<!--'.$this->oValue.'-->';
  }
}

class HTML_IEComment extends HTML_Comment {
  
  public function __construct($oValue, $sVersion = '') {
    
    parent::__construct("[if IE$sVersion]>$oValue<![endif]");
  }
}

class HTML_A extends HTML_Tag {
  
  public function __construct($sHref = '', $oChild = '', $aAttributes = array()) {
    
    parent::__construct('a', $oChild, $aAttributes);
    $this->setAttribute('href', $sHref);
  }
}

/*
 * Crée une image cliquable en attribuant 'title'
 **/
class HTML_Icone extends HTML_A {
  
  public function __construct($sHref = '', $sSrc = '', $sTitle = '', $aAttributes = array()) {
    
    parent::__construct($sHref, '', $aAttributes);
    $this->addChild(new HTML_Img($sSrc, $sTitle, array('title' => $sTitle)));
  }
}

class HTML_Strong extends HTML_Tag {
  
  public function __construct($oChild = '', $aAttributes = array()) {
    
    parent::__construct('strong', $oChild, $aAttributes);
  }
}

class HTML_Img extends HTML_Tag {
  
  public function __construct($sSrc, $sAlt = '', $aAttributes = array()) {
    
    parent::__construct('img', null, $aAttributes);
    $this->setAttribute('src', $sSrc);
    $this->setAttribute('alt', $sAlt);
  }
}

class HTML_Div extends HTML_Tag {
  
  public function __construct($mChildren = '', $aAttributes = array()) {
    
    parent::__construct('div', $mChildren, $aAttributes, true);
  }
}

/*
 * Divers
 **/

class HTML_Table extends HTML_Tag {


  function __construct() {
    
    parent::__construct('table');
  }

  function addRow() {
    
    $row = new HTML_TableRow();
    $this->addChild($row); 
    
    return $row;
  }
}

class HTML_TableRow extends HTML_Tag {


  function __construct() {
    
    parent::__construct('tr');
  }

  function addCell($content, $attributes = NULL) {
    
    $cell = new HTML_Tag('td');
    $this->addChild($cell);
    
    $cell->addChild($content);
    
    if (is_array($attributes)) foreach ($attributes as $key => $val) $cell->setAttribute($key, $val);
    
    return $cell;
  }

  function addMultiCell($cells) {
    
    if (is_array($cells)) {
    
      foreach($cells as $key => $val) {
        
        if (is_array($val)) $this->addCell($key, $val);
        else $this->addCell($val);
      }
    }
  }
}

class HTML_List extends HTML_Tag {
  
  function __construct($oContent = '', $aArguments = array()) {
    
    parent::__construct('ul', $oContent, $aArguments);
  }
  
  function addItem($sContent, $aAttributes = array()) {
    
    $oItem = new HTML_Tag('li', $sContent, $aAttributes);
    
    $this->addChild($oItem);
    
    return $oItem;
  }
}
