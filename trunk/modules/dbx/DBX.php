<?php

class DBX_Module extends Module {
  
  private $oEmpty = null;
  private $sPath = '';
  private $sExtension = '';
  private $sRootName = '';
  private $sParentPath = '';
  private $sParentName = '';
  
  private $bSelfDirectory = true;
  private $oSelfDirectory = null;
  private $oExtendDirectory = null;
  
  public function __construct(XML_Directory $oDirectory, XML_Document $oSchema, XML_Document $oOptions) {
    
    $this->setDirectory(__file__);
    $this->oSelfDirectory = $this->getDirectory();
    $this->oExtendDirectory = $oDirectory;
    
    $this->switchDirectory();
    
    $this->oSchema = $oSchema;
    
    $this->setNamespace('http://www.sylma.org/modules/dbx', 'dbx', false);
    
    $this->sParentName = $oOptions->readByName('parent', $this->getNamespace('dbx'));
    $this->sParentPath = $oOptions->readByName('parent-path', $this->getNamespace('dbx'));
    $this->sRootName = $oOptions->readByName('name', $this->getNamespace('dbx'));
    $this->sPath = $oOptions->readByName('path', $this->getNamespace('dbx'));
    $this->sExtension = $oOptions->readByName('extension', $this->getNamespace('dbx'));
    $sPrefix = $oOptions->readByName('prefix', $this->getNamespace('dbx'));
    
    $this->setNamespace($oSchema->getAttribute('targetNamespace'), $sPrefix);
    $this->setNamespace(SYLMA_NS_SCHEMAS, 'lc', false);
    
    // options
    
    // $this->oEmpty = new XML_Document($oOptions->getByName('empty', $this->getNamespace('dbx'))->getFirst());
    
    //dspf($oOptions->getByName('empty')->getFirst()->getNamespace());
  }
  
  private function getExtension() {
    
    return $this->sExtension;
  }
  
  private function getParentName() {
    
    return $this->sParentName;
  }
  
  private function getParent() {
    
    return $this->sParentPath.$this->getParentName();
  }
  
  private function getPath() {
    
    return $this->sPath;
  }
  
  private function getEmpty() {
    
    $oResult = new XML_Document();
    $oResult->addNode($this->getFullPrefix().$this->getRootName(), null, null, $this->getNamespace());
    
    return $oResult;
  }
  
  private function getRootName() {
    
    return $this->sRootName;
  }
  
  private function getExtendDirectory() {
    
    return $this->oExtendDirectory;
  }
  
  private function switchDirectory() {
    
    if ($this->bSelfDirectory) $this->oDirectory = $this->oExtendDirectory;
    else $this->oDirectory = $this->oSelfDirectory;
    
    $this->bSelfDirectory = !$this->bSelfDirectory;
  }
  
  protected function getDB() {
    
    return Controler::getDatabase();
  }
  
  public function run(Redirect $oRedirect, $sAction, $sID = '') {
    
    $mResult = null;
    $this->switchDirectory();
    
    switch ($sAction) {
      
      case 'add' :
        
        if ($oPost = $oRedirect->getDocument('post')) {
          
          if (!$oValues = $this->buildValues($oPost))
            dspm(xt('Erreur dans la conversion des valeurs de %s à %s', view($oPost), view($oValues)), 'error');
          
        } else $oValues = $this->getEmpty();
        
        $oModel = $oValues->getModel($this->getSchema(), (bool) $oPost);
        $oTemplate = $this->getDocument('form.xsl', true);
        $oTemplate->setParameter('action', $this->getPath().'/add-do.redirect');
        
        $oPath = new XML_Path($this->getDirectory().'/form.eml', array(
          'form' => $oModel->parseXSL($oTemplate)), true, false);
        
        $mResult = new XML_Action($oPath);
        
      break;
      
      case 'add-do' :
        
        if (!$this->add($oRedirect)) {
          
          $oRedirect->setPath($this->getPath().'/add'.$this->getExtension());
          $mResult = $oRedirect;
          //dspf($this->getPath().'/add'.$this->getExtension());
        } else {
          
          $oRedirect->setPath($this->getExtendDirectory().$this->getExtension());
          $mResult = $oRedirect;
        }
        
      break;
      
      case 'list' :
        
        $iStart = 0;
        $iLength = 10;
        
        $mResult = new XML_Document($this->getParentName());
        
        $sQuery = $this->getParent().'/'.$this->getFullPrefix().$this->getRootName();
        
        if (!($sResult = $this->query($sQuery)) || !($oResult = strtoxml($sResult))) {
          
          dspm(xt('Aucun résultat'), 'warning');
          
        } else $mResult->add($oResult);
        
      break;
    }
    
    $this->switchDirectory();
    return $mResult;
  }
  
  public function add(Redirect $oRedirect) {
    
    $bResult = false;
    
    if (!$oPost = $oRedirect->getDocument('post')) {
      
      dspm(t('Impossible de revenir sur la page d\'édition. Modifications perdues'), 'error');
      
    } else {
      
      $oValues = $this->buildValues($oPost);
      
      if (!$oValues || !$oValues->validate($this->getSchema())) {
        
        dspm(t('Un ou plusieurs champs ne semblent pas corrects, ceux-ci sont indiqués en rouge'), 'warning');
        
      } else {
        
        $this->validateElement($oValues->getRoot());
        
        $oValues = $oValues->updateNamespaces($this->getNamespace(), $this->getNamespace(), $this->getPrefix());
        
        // if ($oFile = $oValues->saveTemp()) $this->getDB()->run("add to {$this->getParent()} {$oFile->getSystemPath()}");
        $this->insert($oValues->display(true, false), $this->getParent());
        //dspm(t($this->getTitle().));
        
        $bResult = true;
      }
    }
    
    return $bResult;
  }
  
  public function validateElement(XML_Element $oElement) {
    
    $oAttributes = $oElement->query("@*[namespace-uri()='{$this->getNamespace()}']");
    
    foreach ($oElement->getAttributes() as $oAttribute) {
      
      $sValue = $oAttribute->getValue();
      
      if ($oAttribute->useNamespace($this->getNamespace('lc'))) {
        
        switch ($oAttribute->getName()) {
          
          case 'duplicate' :
            
            // load function and targeted element
            
            $aValue = explode(' ', $sValue);
            
            if (!count($aValue) == 2) dspm(xt('Arguments %s insuffisant pour la duplication dans %s',
              new HTML_Strong($sValue), view($oElement)), 'xml/warning');
            else {
              
              list($sFunction, $sTarget) = $aValue;
              
              if ($sTarget{0} == '@') $oNode = $oElement->setAttribute(substr($sTarget, 1), 'null');
              else $oNode = $oElement->getParent()->insertNode($sTarget, null, null, $this->getNamespace(), $oElement, true);
              
              switch ($sFunction) {
                
                case 'urlize' : $oNode->set(urlize($oElement->getValue())); break;
                
                default :
                  
                  dspm(xt('Fonction %s inconnu sur le champ %s', new HTML_Strong($sValue), view($oElement)), 'xml/warning');
              }
            }
            
          break;
          
          case 'gen-id' :
            
            $oElement->setAttribute($sValue, uniqid());
            
          break;
          
          case 'wiki' :
          
          break;
          
          case 'model' :
          default :
            
            // dspm(xt('Attribut %s inconnu dans l\'élément %s',
              // new HTML_Strong($oAttribute->getName()), view($oAttribute->getParent())), 'xml/warning');
        }
        
        $oAttribute->remove();
      }
    }
    
    foreach ($oElement->getChildren() as $oChild) if ($oChild->isElement()) $this->validateElement($oChild);
  }
  
  public function buildValues($oValues, XML_Element $oParent = null) {
    
    if (!$oParent) $oParent = new XML_Document($this->getEmpty());
    // dspf($this->getEmpty());
    foreach ($oValues->getChildren() as $oValue) {
      
      if ($oValue->isElement()) {
        
        if (substr($oValue->getName(), 0, 4) == 'attr') $oParent->setAttribute(substr($oValue->getName(), 5), $oValue->read());
        else {
          
          $oChild = $oParent->addNode($this->getFullPrefix().$oValue->getName(), null, null, $this->getNamespace());
          
          if ($oValue->isComplex()) $oChild->add($this->buildValues($oValue, $oChild));
          else $oChild->add($oValue->read());
          
          if (!trim($oChild->read())) $oChild->remove();
        }
      }// else dspf($oValue);
    }
    
    return $oParent;
  }
  
  public function edit(Redirect $oRedirect) {
    
    if (!$oValues = $oRedirect->getDocument('post')) {
      
      dspm(t('Impossible de revenir sur la page d\'édition. Modifications perdues'), 'error');
      $oRedirect->setPath($this->getDirectory());
      
    } else {
      
      $oValues = $this->buildValues($oValues);
      dspf($oValues);
      
      $sPath = $oValues->readByName('id');
      $oRedirect->setPath($sReturn.$sPath);
      return '';
      if (!$oValues->validate($this->getSchema())) {
        
        //$sFile = 'document';
        $oPost = $oRedirect->getDocument('post');
        
        $oValues = new XML_Element('new');
        
        foreach ($oSchema->getChildren() as $nField)
          if (!$nField->get('fs:deco', 'fs', SYLMA_NS_FORM_SCHEMA)) $oValues->add($oPost->get($nField->getId()));
        
        // get html contenu
        
        $sPath = urlize($oValues->read('titre'));
        
        if (!$sOldPath = $oPost->read('old_id')) {
          
          // pas d'ancien id
          $oRedirect->setPath($sSuccess);
          dspm(t('Désolé, erreur lors de l\'édition.'), 'error');
          
        } else {
          
          $sOldPath = xmlize($sOldPath);
          $oValues->setAttribute('id', $sPath);
          
          if ($this->load($sOldPath)->isEmpty()) {
            
            dspm(t('Impossible de retrouver les anciennes valeurs'), 'warning');
            
          } else {
            
            if ($oFile = $this->getFile($sPath)) $oValues->add($oFile);
            
            if ($sPath != $sOldPath) dspm('Titre modifié', 'db/notice');
            
            dspm(t('Actualité mise-à-jour'));
            $this->getDB()->update($sOldPath, $oValues);
          }
          
          $oRedirect = new Redirect($sSuccess.$sPath);
        }
      }
    }
    
    return $oRedirect;
  }
  
  private function getFile($sPath) {
    
    $oResult = null;
    $sFile = 'document';
    
    if (isset($_FILES[$sFile]) && $_FILES[$sFile]['name']) {
      
      if ($_FILES[$sFile]['size'] > SYLMA_UPLOAD_MAX_SIZE) dspm(t('Le fichier lié est trop grand'), 'warning');
      else {
        
        $oParentDirectory = extractDirectory(__file__, true);
        
        if (!$oDirectory = $oParentDirectory->getParent()->addDirectory('documents')) dspm(t('Impossible de créer le répertoire de destination des fichiers'), 'error');
        else {
          
          $sExtension = '';
          
          if ($iExtension = strrpos($_FILES[$sFile]['name'], '.')) $sExtension = strtolower(substr($_FILES[$sFile]['name'], $iExtension));
          
          if ($sExtension == '.php') dspm(xt('L\'extension "%s" de ce fichier est interdite !', new HTML_Strong($sExtension)), 'warning');
          else {
            
            $sName = $sPath.$sExtension;
            $sPath = $oDirectory->getRealPath().'/'.$sName;
            
            if(!move_uploaded_file($_FILES[$sFile]['tmp_name'], $sPath)) dspm(t('Problème lors du chargement du fichier'), 'warning');
            else {
              
              $oResult = new XML_Element($sFile, $sName);
              
              dspm(xt('Fichier %s ajouté dans %s', new HTML_Strong($sName), (string) $oDirectory));
            }
          }
        }
      }
    }
    
    return $oResult;
  }
  
  public function load($sId) {
    
    return $this->getDB()->load($sId);;
  }
  
  public function query($sQuery, array $aNamespaces = array()) {
    
    $aNamespaces = array_merge($this->getNS(), $aNamespaces);
    
    return $this->getDB()->query($sQuery, $aNamespaces);
  }
  
  public function insert($mElement, $sTarget, array $aNamespaces = array()) {
    
    $aNamespaces = array_merge($this->getNS(), $aNamespaces);
    
    return $this->getDB()->insert($mElement, $sTarget, $aNamespaces);
  }
  
  public function delete($sId, array $aNamespaces = array()) {
    
    $aNamespaces = array_merge($this->getNS(), $aNamespaces);
    
    return $this->getDB()->delete($sId, $aNamespaces);
  }
}



