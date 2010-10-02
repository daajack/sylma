<?php

class DBX_Module extends Module {
  
  private $aPaths = array();
  
  private function getDB() {
    
    return Controler::getDatabase();
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
  
  public function add(Redirect $oRedirect, XML_Document $oSchema, $sReturn, $sSuccess) {
    
    $oRedirect->setMessages($this->checkRequest($oSchema));
    $oRedirect->setPath($sReturn);
    
    $this->transformHTML($oRedirect);
    
    if (!$oRedirect->getMessages('form/warning')) {
      
      $oPost = $oRedirect->getDocument('post');
      $oValues = new XML_Element('new');
      
      foreach ($oSchema->getChildren() as $nField)
        if (!$nField->get('fs:deco', 'fs', SYLMA_NS_FORM_SCHEMA)) $oValues->add($oPost->get($nField->getId()));
      
      // get html contenu
      
      $sPath = urlize($oValues->read('titre'));
      
      if (!$this->load($sPath)->isEmpty()) {
        
        dspm(t('Ce titre est déjà utilisé'), 'form/warning');
        
      } else {
        
        $oValues->setAttribute('id', $sPath);
        
        if ($oFile = $this->getFile($sPath)) $oValues->add($oFile);;
        
        $this->getDB()->insert($oValues, '/*');
        dspm(t('Actualité créée'));
        
        $oRedirect = new Redirect($sSuccess.$sPath);
      }
    }
    
    return $oRedirect;
  }
  
  public function buildValues($oValues, $oParent = null) {
    
    if (!$oParent) { // root
      
      $oParent = new XML_Element($this->getRootName(), null, null, $this->getNamespace());
    }
    
    foreach ($oValues->getChildren() as $oValue) {
      
      if (substr($oValue->getName(), 0, 4) != 'element') $oParent->setAttribute($oValue->getName(), $oValue);
      else {
        
        $oChild = $oParent->addNode($oValue->getName(), null, null, $this->getNamespace());
        if ($oValue->hasChildren()) $this->buildValues($oValue, $oChild);
      }
    }
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