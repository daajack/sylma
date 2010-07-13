<?php

class SimpleList {
  
  private $sName; // name of the root
  private $sPathField; // field to use as path when reading
  
  public function __construct(XML_Document $oSchema, $sName, XML_Directory $oDirectory, $sPath, $oRedirect, $sPathField) {
    
    
  }
  
  public function add() {
    
    
  }
}

class SimpleNode extends SimpleList {
  
  private $oElement = null;
  private $oParent = null;
  
  private $oSchema;
  private $oDirectory;
  private $sPath; // path where datas are
  private $bDB = false; // use DB or not
  private $oRedirect;
  
  public function __construct(XML_Document $oSchema, $sName, XML_Directory $oDirectory, $oRedirect, $sPathField) {
    
    $this->oParent = $oParent;
    $this->oSchema = $oSchema;
    $this->sName = $sName;
    
    $this->oDirectory = $oDirectory;
    
    if ($sPath{0} == '*') {
      
      $this->bDB = true;
      $this->sPath = substr($sPath, 1);
      
    } else $this->sPath = $sPath;
    
    $this->oRedirect = $oRedirect;
    $this->sPathField = $sPathField;
  }
  
  public function getDirectory() {
    
    return $this->oDirectory;
  }
  
  public function getSchema() {
    
    return $this->oSchema;
  }
  
  public function getRedirect() {
    
    return $this->oRedirect;
  }
  
  public function getPath() {
    
    return $this->sPath;
  }
  
  public function useDB() {
    
    return $this->bDB;
  }
  
  public function transformHTML($oRedirect, $sName) {
    
    $oContenu = $oRedirect->getDocument('post')->getByName($sName);
    
    $oResult = new XML_Document();
    
    $oResult->loadText('<root>'.$oContenu->read().'</root>');
    
    if (!$oResult) $oRedirect->addMessage(t('Contenu invalide, balise html mal formées'), 'form/warning');
    else $oContenu->set($oResult->getChildren());
  }
  
  public function load($sPath) {
    
    $oResult = Controler::getDatabase()->get($this->getPath().$sPath);
    
    return $oResult;
  }
  
  public function build($sReturn, $sSuccess) {
    
    // Redirect $oRedirect, XML_Document $oSchema, 
    
    $oRedirect = $this->getRedirect();
    $oSchema = $this->getSchema();
    
    $oRedirect->setMessages($this->checkRequest());
    $oRedirect->setPath($this->getDirectory().$sReturn);
    
    // load html field value
    foreach($oSchema->query('fs:field/fs:type[content() = "html"]') as $oHTML) $this->transformHTML($oRedirect, $oHTML->getId());
    
    if (!$oRedirect->getMessages('form/warning')) {
      
      $oPost = $oRedirect->getDocument('post');
      $oValues = new XML_Element($this->getName());
      
      foreach ($oSchema->getChildren() as $nField)
        if (!$nField->get('fs:deco', 'fs', SYLMA_NS_FORM_SCHEMA)) $oValues->add($oPost->get($nField->getId()));
      
      $sPath = urlize($oValues->read($this->sPathField));
      $oValues->setAttribute('path', $sPath);
      
      //if ($oFile = $this->getFile($sPath)) $oValues->add($oFile);;
      
      if (Controler::getDatabase()->query()) dspm(t('Ce titre est déjà utilisé'), 'form/warning');
      else {
        
        if ($this->useDB()) {
          
          // db
          
          //Controler::getDatabase()->insert($oValues, $this->getPath());
          
        } else {
          
          // file
          
          //$oDocument = new XML_Document($this->getPath());
          
          if ($oDocument->isEmpty()) dspm(xt('Document de données %s vide', $this->getPath()), 'error');
          else {
            
            $oDocument->add($oValues);
            $oDocument->save();
          }
        }
        
        dspm(t('Enregistrement ajouté'));
        
        $oRedirect = new Redirect($sSuccess.$sPath);
      }
    }
    
    return $oRedirect;
  }
  
  public function edit(Redirect $oRedirect, XML_Document $oSchema, $sReturn, $sSuccess) {
    
    if (!$oValues = $oRedirect->getDocument('post')) {
      
      dspm(t('Impossible de revenir sur la page d\'édition. Modifications perdues'), 'error');
      $oRedirect->setPath('/actualite');
      
    } else {
      
      $sPath = $oValues->readByName('old_id');
      $oRedirect->setPath($sReturn.$sPath);
    }
    
    $oRedirect->setMessages($this->checkRequest($oSchema));
    $this->transformHTML($oRedirect);
    
    if (!$oRedirect->getMessages('form/warning')) {
      
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
    
    return $oRedirect;
  }
  
  public function load($sId) {
    
    $oDocument = $this->getDB()->load($sId);
    if (!$oDocument->isEmpty()) $oDocument->addNode('old_id', $sId);
    
    return $oDocument;
  }
  
  public function delete($sId) {
    
    dspm(t('Actualité supprimée !'));
    $this->getDB()->delete($sId);
  }
}