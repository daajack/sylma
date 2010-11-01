<?php

class DBX_Module extends Module {
  
  private $oModel = null;
  
  private $bSelfDirectory = true;
  private $oSelfDirectory = null;
  private $oExtendDirectory = null;
  
  private $oOptions = null;
  private $aOptions = array(); // cache array
  
  public function __construct(XML_Directory $oDirectory, XML_Document $oSchema, XML_Document $oOptions) {
    
    $this->setDirectory(__file__);
    $this->oSelfDirectory = $this->getDirectory();
    $this->oExtendDirectory = $oDirectory;
    
    $this->switchDirectory();
    
    $this->oSchema = $oSchema;
    $this->oOptions = $oOptions;
    
    $this->setNamespace('http://www.sylma.org/modules/dbx', 'dbx', false);
    $this->setNamespace($oSchema->getAttribute('targetNamespace'), $this->readOption('prefix'));
    $this->setNamespace(SYLMA_NS_SCHEMAS, 'lc', false);
  }
  
  public function getOption($sPath, $bDebug = true) {
    
    if (!array_key_exists($sPath, $this->aOptions)) {
      
      if ((!$this->aOptions[$sPath] = $this->getOptions()->getByName($sPath, $this->getNamespace('dbx'))) && $bDebug)
        dspm(xt('Option %s introuvable dans %s', new HTML_Strong($sPath), view($this->getOptions())), 'action/warning');
    }
    
    return $this->aOptions[$sPath];
  }
  
  public function getModel() {
    
    return $this->oModel;
  }
  
  /*public function loadModel() {
    
    $sFile = 'dbx-empty.xml';
    $sDirectory = 'tmp';
    
    if (1 || (!$oFile = $this->getDirectory()->addDirectory($sDirectory)->getFile($sFile))) {
      
      $oModel = $this->getEmpty()->getModel($this->getSchema(), false);
      
      if ($oModel->isEmpty()) dspm(xt('Fichier modèle %s invalide', view($oModel)), 'action/error');
      else $oFile = $oModel->save($this->getDirectory()->getDirectory($sDirectory).'/'.$sFile);
    }
    
    $this->oModel = $oFile;
  }*/
  
  public function readOption($sPath, $bDebug = true) {
    
    if ($oOption = $this->getOption($sPath, $bDebug)) return $oOption->read();
    else return '';
  }
  
  private function getOptions() {
    
    return $this->oOptions;
  }
  
  private function getHeaders() {
    
    return $this->getOption('headers');
  }
  
  private function getParent() {
    
    //return "doc('{$this->getDB()->getCollection()}/{$this->readOption('parent-path')}')";
    $sParentPath = $this->readOption('parent-path', false);
    $sParent = $sParentPath ? $sParentPath : $this->readOption('parent');
    
    return "doc('{$this->readOption('document')}')/$sParent";
  }
  
  private function getPath() {
    
    return $this->readOption('path');
  }
  
  private function getEmpty() {
    
    $oResult = new XML_Document();
    $oResult->addNode($this->getFullPrefix().$this->readOption('name'), null, null, $this->getNamespace());
    
    return $oResult;
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
  
  public function validateElement(XML_Element $oElement, $bID = false) {
    
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
            
            if ($bID) $oElement->setAttribute('xml:id', uniqid('x')); //$sValue
            
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
        
        if (substr($oValue->getName(), 0, 4) == 'attr') {
          
          $sName = substr($oValue->getName(), 5);
          
          if ($sName == 'id') $oParent->setAttribute('xml:'.$sName, $oValue->read());
          else $oParent->setAttribute($sName, $oValue->read());
          
        } else {
          
          $oChild = $oParent->addNode($this->getFullPrefix().$oValue->getName(), null, null, $this->getNamespace());
          
          if ($oValue->isComplex()) $oChild->add($this->buildValues($oValue, $oChild));
          else $oChild->add($oValue->read());
          
          if (!trim($oChild->read())) $oChild->remove();
        }
      }// else dspf($oValue);
    }
    
    return $oParent;
  }
  
  public function getPost(Redirect $oRedirect, $bMessage = true) {
    
    $oResult = null;
    
    if (!$oPost = $oRedirect->getDocument('post')) {
      
      if ($bMessage) {
        
        dspm(t('Une erreur s\'est produite. Impossible de continuer. Modifications perdues'), 'error');
        dspm(t('Aucune données dans $_POST'), 'action/error');
      }
      
    } else {
      
      if (!$oValues = $this->buildValues($oPost)) {
        
        if ($bMessage) {
          
          dspm(t('Impossible de lire les valeurs envoyés par le formulaire'), 'error');
          dspm(xt('Erreur dans la conversion des valeurs %s dans $_POST', view($oPost)), 'action/error');
        }
        
      } else $oResult = $oValues;
    }
    
    return $oResult;
  }
  
  private function getTemplateExtension() {
    
    $oTemplate = null;
    
    if ($oFormExtension = $this->getOptions()->getByName('form-extension', $this->getNamespace('dbx'))) {
      
      $oTemplate = $oFormExtension->getFirst();
    }
    
    return $oTemplate;
  }
  
  public function run(Redirect $oRedirect, $sAction, $aOptions = array()) {
    
    $mResult = null;
    $sList = $this->getDirectory().'/admin/list';
    
    $this->switchDirectory();
    
    $sID = array_val(0, $aOptions, '');
    
    switch ($sAction) {
      
      case 'edit' :
        
        if ((!$oValues = $this->getPost($oRedirect, false)) && (!$oValues = $this->load($sID))) {
          
          dspm(xt('L\'élément identifié par %s n\'existe pas', new HTML_Strong($sID)), 'warning');
          
        } else {
          
          if (!$oModel = $oValues->getModel($this->getSchema())) {
            
            dspm(xt('Impossible de charger l\'élément'), 'error');
            dspm(xt('Aucun modèle chargé pour %s', view($oValues)), 'action/error');
            
          } else {
            
            $oPath = new XML_Path($this->getDirectory().'/form.eml', array(
              'model' => $oModel,
              'action' => $this->getPath()."/edit-do/$sID.redirect",
              'template-extension' => $this->getTemplateExtension()), true, false); //.redirect
            
            $mResult = new XML_Action($oPath);
          }
        }
        
      break;
      
      case 'edit-do' :
        
        if (!$this->edit($oRedirect, $sID)) $oRedirect->setPath($this->getPath().'/edit/'.$sID);
        else $oRedirect->setPath($sList);
        
        $mResult = $oRedirect;
        
      break;
      
      case 'add' :
        
        if (!$oValues = $this->getPost($oRedirect, false)) $oValues = $this->getEmpty();
        
        if (!$oModel = $oValues->getModel($this->getSchema(), (bool) $oRedirect->getDocument('post'))) {
          
          dspm(xt('Impossible de charger l\'élément'), 'error');
          dspm(xt('Aucun modèle chargé pour %s', view($oValues)), 'action/error');
          
        } else {
          
          $oPath = new XML_Path($this->getDirectory().'/form.eml', array(
            'model' => $oModel,
            'action' => $this->getPath().'/add-do.redirect',
            'template-extension' => $this->getTemplateExtension()), true, false); //.redirect
          
          $mResult = new XML_Action($oPath);
        }
        
      break;
      
      case 'add-do' :
        
        if (!$this->add($oRedirect)) $oRedirect->setPath($this->getPath().'/add');
        else $oRedirect->setPath($sList);
        
        $mResult = $oRedirect;
        
      break;
      
      case 'list' :
        
        $iPage = array_val('page', $aOptions, 1);
        $iPageSize = array_val('size', $aOptions, 30);
        $sOrder = array_val('order', $aOptions, 'date-parution');
        $sOrderDir = array_val('order-dir', $aOptions, 'a');
        
        $mResult = $this->getList($sList, $iPage, $iPageSize, $sOrder, $sOrderDir);
        
      break;
      
      case 'delete' :
        
        $oPath = new XML_Path($this->getDirectory().'/delete.eml', array(
          'id' => $sID,
          'action' => $this->getPath()."/delete-do/$sID.redirect"), true, false); //.redirect
        
        $mResult = new XML_Action($oPath);
        
      break;
      
      case 'delete-do' :
        
        if (!$sID) {
          
          dspm(t('ID manquant'), 'action/error');
          
        } else {
          
          $this->delete($sID);
          dspm(xt('Elément %s supprimé', $sID), 'success');
          
          $oRedirect->setPath($sList);
          $mResult = $oRedirect;
        }
        
      break;
      
      case 'import' :
        
        $this->import();
        
      break;
    }
    
    $this->switchDirectory();
    
    if (!$mResult instanceof Redirect && $sAction != 'list') {
      
      $mResult = new HTML_Div($mResult);
      $mResult->shift(new HTML_A($sList, t('< Retour à la liste'), array('class' => 'dbx-link-list')));
      
      $mResult = $mResult->getChildren();
    }
    
    return $mResult;
  }
  
  public function getList($sPath, $iPage, $iPageSize = 3, $sOrder = '', $sOrderDir = 'a', $sWhere = '') {
    
    $mResult = null;
    
    $oModel = $this->getEmpty()->getModel($this->getSchema(), false);
    
    if ($oModel->isEmpty()) dspm(xt('Fichier modèle %s invalide', view($oModel)), 'action/error');
    else {
      
      $oModel->add($this->getHeaders());
      $sOrderDir = $sOrderDir == 'a' ? 'ascending' : 'descending';
      
      $oTemplate = $this->getDocument('list-xq.xsl', true);
      
      $oTemplate->setParameters(array(
        'page' => $iPage,
        'page-size' => $iPageSize,
        'parent-name' => $this->readOption('parent'),
        'parent-path' => $this->getParent(),
        'order' => $sOrder,
        'prefix' => $this->getFullPrefix(),
        'order-dir' => $sOrderDir));
      
      $sQuery = $oModel->parseXSL($oTemplate, false);
      
      if ($oFile = $oModel->saveTemp()) {
        
        $oPath = new XML_Path($this->getDirectory().'/list', array(
          'model' => $oFile->getSystemPath(),
          'query' => $sQuery,
          'xquery-headers' => $this->getNamespaces(),
          'module' => (string) $this->getExtendDirectory()));
        
        $mResult = new XML_Action($oPath);
        
        $mResult->setVariables(array(
          'page' => $iPage,
          'page-size' => $iPageSize,
          'parent-name' => $this->readOption('parent'),
          'parent-path' => $this->getParent(),
          'order' => $sOrder,
          'order-dir' => $sOrderDir,
          'path-add' => $this->getExtendDirectory().'/admin/add',
          'path-list' => $sPath));
      }
      
      $mResult = $mResult->parse(); // parse to keep files in process before __destruct()
    }
    
    return $mResult;
  }
  
  public function add(Redirect $oRedirect) {
    
    $bResult = false;
    
    
    if ($oValues = $this->getPost($oRedirect)) {
      
      if (!$oValues->validate($this->getSchema())) {
        
        dspm(t('Un ou plusieurs champs ne sont pas corrects, ceux-ci sont indiqués en rouge'), 'warning');
        
      } else {
        
        $this->validateElement($oValues->getRoot(), true);
        
        $oValues = $oValues->updateNamespaces($this->getNamespace(), $this->getNamespace(), $this->getPrefix());
        
        // if ($oFile = $oValues->saveTemp()) $this->getDB()->run("add to {$this->getParent()} {$oFile->getSystemPath()}");
        if ($this->insert($oValues->display(true, false), $this->getParent())) {
          
          dspm(t('Elément ajouté'), 'success');
          $bResult = true;
        }
      }
    }
    
    return $bResult;
  }
  
  public function edit(Redirect $oRedirect, $sID) {
    
    $bResult = false;
    
    if ($oValues = $this->getPost($oRedirect)) {
      
      if (!$oValues->validate($this->getSchema())) {
        
        dspm(t('Un ou plusieurs champs ne sont pas corrects, ceux-ci sont indiqués en rouge'), 'warning');
        
      } else {
        
        $this->validateElement($oValues->getRoot(), false);
        
        $oValues = $oValues->updateNamespaces($this->getNamespace(), $this->getNamespace(), $this->getPrefix());
        
        if (!$this->load($sID)) {
          
          dspm(xt('L\'élément %s n\'existe pas. Modifications perdues', new HTML_Strong($sID)), 'warning');
          
        } else {
          
          if ($this->update($sID, $oValues) || 1) {
            
            dspm(t('Elément mis-à-jour'), 'success');
            $bResult = true;
          }
        }
      }
    }
    
    return $bResult;
  }
  
  public function import() {
    
    if (!$sFile = $this->getOptions()->read('dbx:file', $this->getNS())) {
      
      dspm(xt('Aucun fichier d\'importation défini dans les options'), 'warning');
      
    } else {
      
      if ($oFile = Controler::getFile($sFile)) {
        
        $oDoc = $oFile->getDocument();
        
        if (!$oDoc || $oDoc->isEmpty() || !$oDoc->getRoot()->hasChildren()) {
          
          dspm(xt('Document %s invalide', $oFile->parse()), 'warning');
          
        } else {
          
          foreach ($oDoc->getChildren() as $oItem) $this->insert($oItem, $this->getParent());
          //if ($this->getDB()->run("add to {$this->getParent()} {$oFile->getSystemPath()}"))
          
          dspm(xt('Document %s importé dans %s', view($oFile->getDocument()), new HTML_Strong($this->getParent())), 'success');
        }
      }
    }
  }
  
  public function getNamespaces($aNamespaces = array()) {
    
    return array_merge($this->getNS(), $aNamespaces);
  }
  
  public function load($sID) {
    
    return $this->getDB()->load($sID);
  }
  
  public function get($sQuery, array $aNamespaces = array(), $bDocument = false) {
    
    return $this->getDB()->get($sQuery, $this->getNamespaces($aNamespaces), $bDocument);
  }
  
  public function query($sQuery, array $aNamespaces = array()) {
    
    return $this->getDB()->query($sQuery, $this->getNamespaces($aNamespaces));
  }
  
  public function update($sID, XML_Document $oDocument, array $aNamespaces = array()) {
    
    return $this->getDB()->update($sID, $oDocument, $this->getNamespaces($aNamespaces));
  }
  
  public function insert($mElement, $sTarget, array $aNamespaces = array()) {
    
    return $this->getDB()->insert($mElement, $sTarget, $this->getNamespaces($aNamespaces));
  }
  
  public function delete($sID, array $aNamespaces = array()) {
    
    return $this->getDB()->delete($sID, $this->getNamespaces($aNamespaces));
  }
}



