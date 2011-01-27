<?php

class DBX_Module extends Module {
  
  private $oModel = null;
  
  private $bSelfDirectory = true;
  private $oSelfDirectory = null;
  private $oExtendDirectory = null;
  
  private $oHeaders = null;
  private $oOptions = null;
  private $aOptions = array(); // cache array
  
  public function __construct(XML_Directory $oDirectory, XML_Document $oSchema, XML_Document $oOptions) {
    
    $this->setDirectory(__file__);
    $this->oSelfDirectory = $this->getDirectory();
    $this->oExtendDirectory = $oDirectory;
    
    $this->switchDirectory();
    
    $this->oSchema = $oSchema;
    $this->oOptions = $oOptions;
    $this->oHeaders = new XML_Document($this->getOption('headers'));
    
    $this->setNamespace('http://www.sylma.org/modules/dbx', 'dbx', false);
    $this->setNamespace(SYLMA_NS_XHTML, 'html', false);
    
    if (!$oSchema) $this->dspm(xt('Aucun schéma défini'), 'action/warning');
    else $this->setNamespace($oSchema->getAttribute('targetNamespace'), $this->readOption('database/prefix'));
    
    $this->setNamespace(SYLMA_NS_SCHEMAS, 'lc', false);
  }
  
  /*** Module Extension ***/
  
  protected function getDB() {
    
    return Controler::getDatabase();
  }
  
  private function getExtendDirectory() {
    
    return $this->oExtendDirectory;
  }
  
  private function switchDirectory() {
    
    if ($this->bSelfDirectory) $this->oDirectory = $this->oExtendDirectory;
    else $this->oDirectory = $this->oSelfDirectory;
    
    $this->bSelfDirectory = !$this->bSelfDirectory;
  }
  
  /*public function getModel() {
    
    return $this->oModel;
  }*/
  
  private function getCollection($sPath = '') {
    
    //return "doc('{$this->getDB()->getCollection()}/{$this->readOption('parent-path')}')";
    // $sParentPath = $this->readOption('parent-path', false);
    // $sParent = $sParentPath ? $sParentPath : $this->readOption('parent').'/*';
    
    return "doc('{$this->getDB()->getCollection()}{$this->readOption('database/document')}')".($sPath ? '/'.$sPath : '');
  }
  
  private function getPath() {
    
    return $this->readOption('path');
  }
  
  protected function getEmpty() {
    
    $oResult = new XML_Document();
    $oResult->addNode($this->getFullPrefix().$this->readOption('database/name'), null, null, $this->getNamespace());
    
    return $oResult;
  }
  
  /*** Options ***/
  
  private function getOptions() {
    
    return $this->oOptions;
  }
  
  public function getOption($sPath, $bDebug = true) {
    
    if (!$this->getOptions()) $this->dspm(xt('Aucune option définie'), 'action/warning');
    else {
      
      if (!array_key_exists($sPath, $this->aOptions) || !$this->aOptions[$sPath]) {
        
        $sRealPath = preg_replace('/([-\w]+)/', 'dbx:\1', $sPath);
        
        if ((!$this->aOptions[$sPath] = $this->getOptions()->get($sRealPath, $this->getNS())) && $bDebug)
          dspm(xt('Option %s introuvable dans %s', new HTML_Strong($sPath), view($this->getOptions())), 'action/warning');
      }
      
      return $this->aOptions[$sPath];
    }
    
    return null;
  }
  
  public function readOption($sPath, $bDebug = true) {
    
    if ($oOption = $this->getOption($sPath, $bDebug)) return $oOption->read();
    else return '';
  }
  
  /*** Various ***/
  
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
    
    foreach ($oElement->getChildren() as $oChild) if ($oChild->isElement()) $this->validateElement($oChild);
    
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
                case 'wikize' : 
                  
                  $sValue = preg_replace('/\\n/', '<html:br/>', $oElement->read());
                  //dspf($sValue);
                  if ($sValue) $oNode->set(strtoxml($sValue, $this->getNS()));
                  
                break;
                
                default :
                  
                  dspm(xt('Fonction %s inconnu sur le champ %s', new HTML_Strong($sValue), view($oElement)), 'xml/warning');
              }
            }
            
          break;
          
          case 'gen-id' :
            
            if ($bID) $oElement->setAttribute('xml:id', uniqid('x')); //$sValue
            
          break;
          
          case 'export' : 
            
            // $sPath = $this->readOption("file[@name='$sValue']");
            
            
          break;
            
          /*case 'use-statut' :
            
            $sPublish = $oElement->readByName('date-publish', $this->getNamespace());
            $sEnd = $oElement->readByName('date-end', $this->getNamespace());
            
            if (!$sPublish || !$sEnd) {
              
              dspm(xt('Impossible d\'indiquer l\'état de l\'élément %s', view($oElement)), 'action/warning');
              
            } else {
              
              $oToday = new DateTime();
              $oPublish = new DateTime($sPublish);
              $oEnd = new DateTime($sEnd);
              
              if ($oPublish > $oToday) $iStatut = 5;
              else if ($oEnd < $oToday) $iStatut = 20;
              else $iStatut = 10;
              
              $oElement->setAttribute('statut', $iStatut);
            }
            
          break;*/
          
          case 'model' :
          default :
            
            // dspm(xt('Attribut %s inconnu dans l\'élément %s',
              // new HTML_Strong($oAttribute->getName()), view($oAttribute->getParent())), 'xml/warning');
        }
        
        $oAttribute->remove();
      }
    }
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
    
    if ($oFormExtension = $this->getOptions()->getByName('template-form', $this->getNamespace('dbx'))) {
      
      $oTemplate = $oFormExtension->getFirst();
    }
    
    return $oTemplate;
  }
  
  /*** Headers ***/
  
  private function getAdmin() {
    
    if (!$sPath = $this->readOption('path', false)) $sPath = $this->getExtendDirectory().'/admin';
    return $sPath;
  }
  
  private function getHeaders() {
    
    return $this->oHeaders;
  }
  
  private function setHeader($sName, $sValue, $bReplace = false) {
    
    if ($sValue) {
      
      if ($oElement = $this->getHeaders()->getByName($sName, $this->getNamespace('dbx'))) {
        
        if ($bReplace) {
          
          $oElement->set($sValue);
          return $oElement;
        }
        
      } else {
        
        return $this->getHeaders()->addNode($sName, $sValue, null, $this->getNamespace('dbx'));
      }
    }
  }
  
  /*** Actions ***/
  
  public function run(Redirect $oRedirect, $sAction, $aOptions = array(), XML_Element $oOptions = null) {
    
    $mResult = null;
    $sList = $this->getAdmin().'/list';
    
    if ($oOptions) $this->getHeaders()->shift($oOptions->getChildren());
    
    // $this->switchDirectory();
    
    $sID = array_val(0, $aOptions, '');
    
    $this->switchDirectory(); // to dbx directory
    
    switch ($sAction) {
      
      case 'view' :
        
        if (!$oModel = $this->getEmpty()->getModel($this->getSchema(), false, false)) {
          
          $this->dspm(xt('Impossible de charger l\'élément'), 'error');
          $this->dspm(xt('Modèle de base invalide pour %s', view($oValues)), 'action/error');
          
        } else {
          
          // load extended datas
          
          
          $oTemplate = $this->getDocument('view-xq.xsl', 'xsl');
          
          $oTemplate->setParameters(array(
            'path' => $this->getCollection()."//id('$sID')",
            'prefix' => $this->getFullPrefix()));
          
          $oModel->add($oModel->parseXSL($this->getDocument('build-headers.xsl')));
          
          $oQuery = new XML_XQuery($oModel->parseXSL($oTemplate, false), $this->getNS());
          $oItem = new XML_Document($oQuery);
          
          if ($oItem->isEmpty()) {
            
            $this->dspm(xt('Impossible de charger l\'élément'), 'error');
            $this->dspm(xt('Document vide'), 'action/error');
            
          } else {
            
            if (!$oModel = $oItem->getModel($this->getSchema(), false, false, false)) {
              
              $this->dspm(xt('Impossible de charger l\'élément'), 'error');
              $this->dspm(xt('Aucun modèle chargé pour %s', view($oItem)), 'action/error');
              
            } else {
              
              // run action
              
              $aArguments = array('model' => $oModel);
              
              if ($oFormExtension = $this->getOptions()->getByName('template-view', $this->getNamespace('dbx'))) {
                
                $aArguments['template-extension'] = $oFormExtension->getFirst();
              }
              $mResult = $this->runAction('view', $aArguments);
            }
          }
        }
        
      break;
      
      case 'edit' :
        
        if ((!$oValues = $this->getPost($oRedirect, false)) && (!$oValues = $this->load($sID))) {
          
          dspm(xt('L\'élément identifié par %s n\'existe pas', new HTML_Strong($sID)), 'warning');
          
        } else {
          
          if (!$oModel = $oValues->getModel($this->getSchema())) {
            
            dspm(xt('Impossible de charger l\'élément'), 'error');
            dspm(xt('Aucun modèle chargé pour %s', view($oValues)), 'action/error');
            
          } else {
            // dspf($oModel);
            // $this->buildRefs($oModel, true);
            
            $mResult = $this->runAction('form', array(
              'model' => $oModel,
              'action' => $this->getPath()."/edit-do/$sID".SYLMA_FORM_REDIRECT_EXTENSION,
              'template-extension' => $this->getTemplateExtension()));
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
          
          // $this->buildRefs($oModel, true);
          $sPath = $this->readOption('add-do-path', false);
          $sPath = $sPath ? $sPath : $this->getPath().'/add-do';
          
          $this->switchDirectory();
          
          if (!$oForm = Controler::getFile('form.eml', $this->getDirectory())) $this->switchDirectory();
          
          $oPath = new XML_Path($this->getDirectory().'/form.eml', array(
            'model' => $oModel,
            'action' => $sPath.SYLMA_FORM_REDIRECT_EXTENSION,
            'template-extension' => $this->getTemplateExtension()), true, false); //.redirect
          
          $mResult = new XML_Action($oPath);
        }
        
      break;
      
      case 'add-do' :
        
        if (!$this->add($oRedirect)) {
          
          $sPath = $this->readOption('add-path', false);
          $sPath = $sPath ? $sPath : $this->getPath().'/add';
          
          $oRedirect->setPath($sPath);
          
        } else {
          
          if (!$sPath = $this->readOption('redirect', false)) $sPath = $sList;
          $oRedirect->setPath($sPath);
        }
        
        $mResult = $oRedirect;
        
      break;
      
      case 'simple-list' :
        
        $this->loadView($oRedirect, $aOptions);
        $mResult = $this->getList($this->getAdmin().'/simple-list', 'simple-list');
        
      break;
      
      case 'list' :
        
        $this->loadView($oRedirect, $aOptions);
        $mResult = $this->getList($this->getAdmin().'/simple-list');
        
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
    
    /*if (!$mResult instanceof Redirect && $sAction != 'list' && !$this->getOption('no-action', false)) {
      
      $mResult = new HTML_Div($mResult);
      $mResult->shift(new HTML_A($sList, t('< Retour à la liste'), array('class' => 'dbx-link-list')));
      
      $mResult = $mResult->getChildren();
    }*/
    
    return $mResult;
  }
  
  private function loadView(Redirect $oRedirect, array $aOptions) {
    
    $sDocument = 'dbx-list-headers'.$this->getOption('database/document');
    
    if ($sHeaders = array_val($sDocument, $_SESSION)) $this->oHeaders = new XML_Document($sHeaders);
    
    $iPage = array_val('page', $aOptions);
    $iPageSize = array_val('size', $aOptions, 15);
    $sOrder = array_val('order', $aOptions); //, $this->getFullPrefix().'date-publish'
    $sOrderDir = array_val('order-dir', $aOptions, 'a');
    
    $this->setHeader('page', ($iPage ? $iPage : 1), (bool) $iPage);
    $this->setHeader('page-size', $iPageSize);
    if ($oOrder = $this->setHeader('order', $sOrder, true)) $oOrder->setAttribute('dir', $sOrderDir);
    // $this->setHeader('where', $sWhere);
    
    $_SESSION[$sDocument] = (string) $this->getHeaders();
  }
  
  public function getList($sPath, $sAction = 'list') {
    
    $mResult = null;
    
    $oModel = $this->getEmpty()->getModel($this->getSchema(), false, false);
    
    if (!$oModel || $oModel->isEmpty()) dspm(xt('Fichier modèle %s invalide', view($oModel)), 'action/error');
    else {
      
      // $this->buildRefs($oModel);
      
      $oModel->add($this->getHeaders());
      //$sOrderDir = $sOrderDir == 'a' ? 'ascending' : 'descending';
      // dspf($oModel);
      $oTemplate = $this->getDocument('list-xq.xsl', true);
      
      $sChildren = $this->readOption('database/list-path', false);
      $sChildren = $sChildren ? $sChildren : $this->readOption('database/parent').'/*';
      
      // $sParentPath = $this->readOption('parent-path', false);
      // $sParent = $sParentPath ? $sParentPath : $this->readOption('parent').'/*';
      $oTemplate->setParameters(array(
        'parent-name' => $this->readOption('database/parent'),
        'parent-path' => $this->getCollection($sChildren),
        'build-empty' => 'true',
        'prefix' => $this->getFullPrefix()));
      
      $sQuery = $oModel->parseXSL($oTemplate, false);
      
      $oTemplateExt = $this->getOption('template-list', false);
      if ($oTemplateExt) $oTemplateExt = $oTemplateExt->getFirst();
      
      $oPath = new XML_Path($this->getDirectory().'/'.$sAction, array(
        'o-model' => $oModel,
        'datas' => $this->get($sQuery, true),
        'path-add' => $this->getAdmin().'/add',
        'path-list' => $sPath,
        'module' => (string) $this->getAdmin(),
        'template-extension' => $oTemplateExt));
      
      $mResult = new XML_Action($oPath);
      
      if ($mResult) $mResult = $mResult->parse(); // parse to keep files in process before __destruct()
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
        
        $sParent = nonull_val($this->readOption('database/insert-path', false), $this->readOption('database/parent'));
        
        // if ($oFile = $oValues->saveTemp()) $this->getDB()->run("add to {$this->getParent()} {$oFile->getSystemPath()}");
        
        if ($this->insert($oValues->display(true, false), $this->getCollection($sParent))) {
          
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
          
          //foreach ($oDoc->getChildren() as $oItem) $this->insert($oItem, $this->getParent());
          dspm('Refaire la fonction avec les doc de type database/datas');
          //if ($this->getDB()->run("add to {$this->getParent()} {$oFile->getSystemPath()}"))
          
          dspm(xt('Document %s importé dans %s', view($oFile->getDocument()), new HTML_Strong($this->getParent())), 'success');
        }
      }
    }
  }
  
  /*** Update ***/
  
  public function getNamespaces($aNamespaces = array()) {
    
    if ($aNamespaces) return array_merge($this->getNS(), $aNamespaces);
    else return $this->getNS();
  }
  
  public function load($sID) { // TOUSE ?
    
    return $this->getDB()->load($sID);
  }
  
  public function get($sQuery, $bDocument = false, array $aNamespaces = array()) {
    
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



