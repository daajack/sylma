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
  private $oHeaders = null;
  
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
    $this->oHeaders = new XML_Document($oOptions->getByName('headers', $this->getNamespace('dbx')));
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
  
  private function getHeaders() {
    
    return $this->oHeaders;
  }
  
  private function getParent() {
    
    return $this->sParentPath;//.$this->getParentName();
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
            
            if ($bID) $oElement->setAttribute($sValue, uniqid());
            
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
              'action' => $this->getPath()."/edit-do/$sID.redirect"), true, false); //.redirect
            
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
            'action' => $this->getPath().'/add-do.redirect'), true, false); //.redirect
          
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
        $iPageSize = array_val('size', $aOptions, 3);
        $sOrder = array_val('order', $aOptions, 'date-parution');
        $sOrderDir = array_val('order-dir', $aOptions, 'a');
        
        $mResult = $this->getList($sList, $iPage, $iPageSize, $sOrder, $sOrderDir);
        
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
    $oHeaders = $this->getHeaders();
    $sOrderDir = $sOrderDir == 'a' ? 'ascending' : 'descending';
    
    if ($oFile = $oHeaders->saveTemp()) {
      
      $oPath = new XML_Path($this->getDirectory().'/list', array(
        'model' => $this->getEmpty()->getModel($this->getSchema(), false),
        'xquery-headers' => $this->getNamespaces(),
        'headers' => (string) $oFile->getSystemPath(),
        'module' => (string) $this->getExtendDirectory()));
      
      $mResult = new XML_Action($oPath);
      
      $mResult->setVariables(array(
        'page' => $iPage,
        'page-size' => $iPageSize,
        'parent-name' => $this->getParentName(),
        'parent-path' => $this->getParent(),
        'order' => $this->getFullPrefix().$sOrder,
        'order-dir' => $sOrderDir,
        'path-add' => $this->getExtendDirectory().'/admin/add',
        'path-list' => $sPath));
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
        if ($this->insert($oValues->display(true, false), $this->getParent()) || 1) {
          
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
  
  public function load($sId) {
    
    return $this->getDB()->load($sId);
  }
  
  public function getNamespaces($aNamespaces = array()) {
    
    return array_merge($this->getNS(), $aNamespaces);
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
  
  public function delete($sId, array $aNamespaces = array()) {
    
    return $this->getDB()->delete($sId, $this->getNamespaces($aNamespaces));
  }
}



