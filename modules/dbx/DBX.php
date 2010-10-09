<?php

class DBX_Module extends Module {
  
  private $oEmpty = null;
  private $sPath = '';
  private $sExtension = '';
  
  private $bSelfDirectory = true;
  private $oSelfDirectory = null;
  private $oExtendDirectory = null;
  
  public function __construct(XML_Directory $oDirectory, XML_Document $oSchema, $sNamespace, XML_Document $oOptions) {
    
    $this->setDirectory(__file__);
    $this->oSelfDirectory = $this->getDirectory();
    $this->oExtendDirectory = $oDirectory;
    
    $this->switchDirectory();
    
    $this->oSchema = $oSchema;
    $this->setNamespace($sNamespace);
    $this->setNamespace('http://www.sylma.org/modules/dbx', 'dbx', false);
    
    // options
    
    $this->oEmpty = new XML_Document($oOptions->getByName('empty', $this->getNamespace('dbx'))->getFirst());
    
    $this->sPath = $oOptions->readByName('path', $this->getNamespace('dbx'));
    $this->sExtension = $oOptions->readByName('extension', $this->getNamespace('dbx'));
  }
  
  private function getExtension() {
    
    return $this->sExtension;
  }
  
  private function getPath() {
    
    return $this->sPath;
  }
  
  private function getExtendDirectory() {
    
    return $this->oExtendDirectory;
  }
  
  private function switchDirectory() {
    
    if ($this->bSelfDirectory) $this->oDirectory = $this->oExtendDirectory;
    else $this->oDirectory = $this->oSelfDirectory;
    
    $this->bSelfDirectory = !$this->bSelfDirectory;
  }
  
  private function getEmpty() {
    
    return $this->oEmpty;
  }
  
  private function getDB() {
    
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
          dspf($oValues);
        } else $oValues = null;
        
        if (!$oValues) $oValues = $this->getEmpty();
        
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
    }
    
    $this->switchDirectory();
    return $mResult;
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
  
  public function add(Redirect $oRedirect) {
    
    $bResult = false;
    
    if (!$oPost = $oRedirect->getDocument('post')) {
      
      dspm(t('Impossible de revenir sur la page d\'édition. Modifications perdues'), 'error');
      
    } else {
      
      $oValues = $this->buildValues($oPost);
      //dspf($oValues);
      
      if (!$oValues || !$oValues->validate($this->getSchema())) {
        
        dspm(t('Un ou plusieurs champs ne semblent pas corrects, ceux-ci sont indiqués en rouge'), 'warning');
        
      } else {
        
        $sPath = urlize($oValues->getByName('intitule'));
        $oValues->setAttribute('id', $sPath);
        
        $this->getDB()->insert($oValues, '/*');
        //dspm(t($this->getTitle().));
        
        $bResult = true;
      }
    }
    
    return $bResult;
  }
  
  public function buildValues($oValues, XML_Element $oParent = null) {
    
    if (!$oParent) $oParent = new XML_Document($this->getEmpty());
    
    foreach ($oValues->getChildren() as $oValue) {
      
      if ($oValue->isElement()) {
        
        if (substr($oValue->getName(), 0, 4) == 'attr') $oParent->setAttribute(substr($oValue->getName(), 4), $oValue);
        else {
          
          $oChild = $oParent->addNode($oValue->getName(), null, null, $this->getNamespace());
          
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
  
  public function load($sId) {
    
    return $this->getDB()->load($sId);;
  }
  
  public function delete($sId) {
    
    dspm(t('Actualité supprimée !'));
    $this->getDB()->delete($sId);
  }
}