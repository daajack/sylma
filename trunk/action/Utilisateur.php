<?php

class Utilisateur extends Action {
  
  public function __construct() {
    
    $this->setSchemas(Controler::getYAML('action/utilisateur.yml'));
  }
  
  public function display($iId) {
    
    if (!$iId) return t('- aucun -');
    
    $rUtilisateur = db::query("SELECT * FROM utilisateur WHERE id = $iId");
    
    if (!mysql_num_rows($rUtilisateur)) return t('- utilisateur inexistent -');
    else $oUtilisateur = mysql_fetch_object($rUtilisateur);
    
    return "{$oUtilisateur->v_prenom} {$oUtilisateur->v_nom}";
  }
  
  public function actionList() {
    
    Controler::getWindow()->addJS('/web/form.js');
    
    Controler::getWindow()->addCSS('/web/ajax.css');
    Controler::getWindow()->addJS('/web/ajax.js');
    
    Controler::getWindow()->addJS('/web/lib/prototype.js');
    Controler::getWindow()->addJS('/web/lib/scriptaculous.js');
    Controler::getWindow()->addJS('/web/lib/effects.js');
    
    Controler::getWindow()->getBloc('content-title')->addChild(t('Liste des utilisateurs'));
    
    $oContainer = new HTML_Div();
    
    $oContainer->addChild(new HTML_Button(t('Ajouter un utilisateur'), "window.location ='/utilisateur/add'"));
    $oContainer->addChild(new HTML_AJAX_Form('utilisateur-edit'));
    
    $oScript = new HTML_Script();
    
    // $oScript->addChild("function editPiece(iId) { window.addAJAX('/form/piece/edit/' + iId, 'piece-edit', {$this->iFormWidth}, {$this->iFormHeight}, 'piece-edit-caller-' + iId); return false; }");
    $oScript->addChild("function deleteUtilisateur(iId) { window.addAJAX('/form/utilisateur/delete/' + iId, 'utilisateur-edit', 350, 110, 'utilisateur-delete-caller-' + iId); return false; }");
    
    $oContainer->addChild($oScript);
    
    $oContainer->addChild(new HTML_Div($this->actionTable(), array('id' => 'utilisateur-list', 'class' => 'ajax-container')));
    
    return $oContainer;
  }
  
  public function actionTable() {
    
    $oTemplate = new HTML_Template('template/utilisateur_list');
    
    // SELECT des utilisateurs
    
    $rUtilisateur = db::query('SELECT * FROM utilisateur');
    
    $aUtilisateurs = array();
    
    while ($oUtilisateur = mysql_fetch_object($rUtilisateur)) {
      
      $rRole = db::query("SELECT role.v_nom FROM utilisateur_role LEFT JOIN role ON role.id = id_role WHERE id_utilisateur = {$oUtilisateur->id}");
      
      if (mysql_num_rows($rRole)) {
        
        $aRoles = array();
        $sRoles = '';
        
        while ($oRole = mysql_fetch_object($rRole)) $aRoles[] = $oRole->v_nom;
        
        $sRoles = implode(', ', $aRoles);
        
      } else $sRoles = '';
      
      $oUtilisateur->roles = $sRoles;
      
      $oUtilisateur->link_m = new HTML_Icone("/utilisateur/edit/{$oUtilisateur->id}", '/web/icones/write.png', t('Editer l\' utilisateur'));
      $oUtilisateur->link_s = new HTML_Icone('#', '/web/icones/delete.png', t('Supprimer un utilisateur'), array(
        
        'onclick' => "return deleteUtilisateur({$oUtilisateur->id})",
        'id'      => 'utilisateur-delete-caller-'.$oUtilisateur->id,
        'class'   => 'ajax-caller',
      ));
      
      $aUtilisateurs[] = $oUtilisateur;
    }
    
    $oTemplate->setArgument('aUtilisateurs', $aUtilisateurs);
    
    return $oTemplate;
  }
  
  private function loadRoles() {
    
    $rRole = db::query('SELECT * FROM role');
    $aRoles = array();
    
    if (mysql_num_rows($rRole)) {
      
      while ($oRole = mysql_fetch_object($rRole)) {
        
        $sField = 'role_'.$oRole->id;
        
        $aRole = array(
          
          'type' => 'bool',
          'title' => ucfirst($oRole->v_display),
          'name' => $sField,
          'arguments' => array('value' => $oRole->id),
          'deco' => true,
          'id_sql' => $oRole->id,
        );
        
        $aRoles[$sField] = $aRole;
      }
    }
    
    return $aRoles;
  }
  
  private function updateRoles($iIdUser) {
    
    $aRoles = $this->loadRoles();
    
    $aKeys = array('id_utilisateur', 'id_role');
    $aValues = array();
    
    foreach($aRoles as $sRole => $aRole)
      if (isset($_POST[$sRole])) $aValues[] = array($iIdUser, $aRole['id_sql']);
    
    db::query('DELETE FROM utilisateur_role WHERE id_utilisateur = '.$iIdUser);
    if ($aValues) db::query('INSERT INTO utilisateur_role '.db::buildMultiInsert($aKeys, $aValues));
  }
  
  public function actionAdd($oRedirect) {
    
    if ($oRedirect->isSourceAction('/utilisateur/add_do')) $this->setMode('post');
    
    $aSchema = $this->getSchemas('add');
    
    Controler::getWindow()->addCSS('/web/form.css');
    
    // Form
    
    if ($this->isMode('edit')) {
      
      $sAction = '/utilisateur/edit_do/'.$this->getId();
      $sTitle = 'Edition d\'un utilisateur';
      
    } else {
      
      $iId = 0;
      $sAction = '/utilisateur/add_do';
      $sTitle = 'Saisie d\'un utilisateur';
      
      $aSchema['v_password']['required'] = true;
      $aSchema['v_password_confirm']['required'] = true;
    }
    
    Controler::getWindow()->getBloc('content-title')->addChild(t($sTitle));
    
    $oForm = new HTML_Form($sAction);
    $oForm->addClasses('id', 'main_form');
    $oForm->addClass('float-block center-block col1-5');
    
    // Actions
    
    $oForm->getBloc('action')->addChild(new HTML_Button(t('Annuler'), "history.go(-1);"));
    $oForm->addAction(t('Terminer'));
    
    // Ajout des rôles
    
    if (Controler::getUser()->isRole('administrateur')) $aSchema += $this->loadRoles();
    
    // Chargement des valeurs
    
    if ($this->isMode('post') || $this->isMode('edit')) $aValues = $oRedirect->getArgument('post');
    else $aValues = array();
    
    // Chargement des données
    
    $oForm->build($aSchema, $aValues, $oRedirect->getMessages());
    
    return $oForm;
  }
  
  public function actionAdd_do() {
    
    $aSchema = $this->getSchemas('add');
    $oRedirect = new Redirect('/utilisateur/add', $this->checkRequest($aSchema));
    
    // Contrôle du mot de passe
    
    if (!array_val('v_password', $_POST))
      $oRedirect->addMessage(t('Vous devez créer un mot de passe lors de la saisie d\'un utilisateur !'), 'warning', array('field' => array('v_password', 'v_password_confirm')));
    else if (!array_val('v_password_confirm', $_POST))
      $oRedirect->addMessage(t('Vous devez confirmer le mot de passe pour pouvoir le changer !'), 'warning', array('field' => 'v_password_confirm'));
    else if (array_val('v_password_confirm', $_POST) != array_val('v_password', $_POST))
      $oRedirect->addMessage(t('Les mots de passe ne correspondent pas !'), 'warning');
    
    // Contrôle de l'existence du nom d'utilisateur
    
    if (isset($_POST['v_user']) && $_POST['v_user']) {
      $rUtilisateur = db::query('SELECT * FROM utilisateur WHERE v_user = '.db::buildString($_POST['v_user']));
      if (mysql_num_rows($rUtilisateur)) $oRedirect->addMessage(t('Cet utilisateur existe déjà !'), 'warning', array('field' => 'v_user'));
    }
    
    if (!$oRedirect->getMessages('warning')) {
      
      $aFields = $this->importPost($aSchema);
      
      if (isset($aFields['v_password'])) $aFields['v_password'] = 'SHA('.$aFields['v_password'].')';
      
      db::query('INSERT INTO utilisateur '.db::buildInsert($aFields));
      
      // Ajout des rôles
      
      if (Controler::getUser()->isRole('administrateur')) $this->updateRoles(mysql_insert_id());
      
      // Redirection
      
      $oRedirect->addMessage(t('Utilisateur ajouté'), 'success');
      $oRedirect->setPath('/utilisateur/list');
    }
    
    return $oRedirect;
  }
  
  public function actionEdit($oRedirect) {
    
    if (!$iId = $this->getId()) return $this->errorRedirect(t('Numéro d\'utilisateur incorrect'));
    
    // Si pas admin, l'id doit correspondre à l'utilisateur en cours
    
    if (Controler::getUser()->getArgument('id') != $iId && !Controler::getUser()->isRole('administrateur'))
      Controler::accessRedirect();
    
    if ($oRedirect->isSourceAction('/utilisateur/edit_do')) $this->setMode('post');
    
    $oRedirect->setArgument('id', $iId);
    
    if ($this->isMode('normal')) {
      
      // SELECT de l'intervention
      
      $rObjet = db::query('SELECT * FROM utilisateur WHERE id = '.$iId);
      if (!mysql_num_rows($rObjet)) return $this->errorRedirect(t('Cet utilisateur n\'existe pas'));
      
      $aArguments = mysql_fetch_assoc($rObjet);
      unset($aArguments['v_password']);
      
      // Si admin, récupération des rôles
      
      if (Controler::getUser()->isRole('administrateur')) {
        
        $rURoles = db::query("SELECT * FROM utilisateur_role WHERE id_utilisateur = $iId");
        while ($oURole = mysql_fetch_object($rURoles)) $aArguments['role_'.$oURole->id_role] = $oURole->id_role;
      }
      
      $oRedirect->setArgument('post', $aArguments);
    }
    
    $this->setMode('edit');
    
    return $this->actionAdd($oRedirect);
  }
  
  public function actionEdit_do() {
    
    if (!$iId = $this->getId()) return errorRedirect('Numéro d\'utilisateur non spécifié');
    
    // Si pas admin, l'id doit correspondre à l'utilisateur en cours
    
    if (Controler::getUser()->getArgument('id') != $iId && !Controler::getUser()->isRole('administrateur'))
      Controler::accessRedirect();
    
    $aSchema = $this->getSchemas('add');
    $oRedirect = new Redirect("/utilisateur/edit/$iId", $this->checkRequest($aSchema));
    
    if (array_val('v_password', $_POST) && !array_val('v_password_confirm', $_POST))
      $oRedirect->addMessage(t('Vous devez confirmer le mot de passe pour pouvoir le changer !'), 'warning', array('field' => 'v_password_confirm'));
    else if (array_val('v_password_confirm', $_POST) != array_val('v_password', $_POST))
      $oRedirect->addMessage(t('Les mots de passe ne correspondent pas !'), 'warning');
    else if (!array_val('v_password', $_POST) && !array_val('v_password_confirm', $_POST)) {
      
      // Si aucun mot de passe, on les enlève de la requête
      unset($aSchema['v_password'], $aSchema['v_password_confirm']);
    }
    
    if (!$oRedirect->getMessages('warning')) {
      
      $aFields = $this->importPost($aSchema);
      
      if (isset($aFields['v_password'])) $aFields['v_password'] = 'SHA('.$aFields['v_password'].')';
      
      db::query('UPDATE utilisateur '.db::buildUpdate($aFields).' WHERE id = '.$iId);
      
      // Ajout des rôles
      
      if (Controler::getUser()->isRole('administrateur')) $this->updateRoles($iId);
      
      // Redirection
      
      $oRedirect->addMessage(t('Utilisateur mis-à-jour'), 'success');
      $oRedirect->setPath('/utilisateur/list');
      
    } else $oRedirect->setArgument('id', $iId);
    
    return $oRedirect;
  }
  
  public function actionDelete() {
    
    if (!$iId = $this->getId()) return $this->errorRedirect(t('Numéro de client non spécifié'));
    
    Controler::getWindow()->addCSS('/web/form.css');
    
    // Titre
    
    Controler::getWindow()->getBloc('content-title')->addChild(t('Suppression d\'un utilisateur'));
    
    // Chargement de l'enregistrement
    
    $rUtilisateur = db::query("SELECT * FROM utilisateur WHERE id = $iId");
    
    if (!mysql_num_rows($rUtilisateur)) return 'Cet utilisateur n\'existe pas';
    else $oUtilisateur = mysql_fetch_object($rUtilisateur);
    
    // Création du form
    
    $oForm = new HTML_Form("/form/utilisateur/delete_do/$iId");
    $oForm->displayMark(false);
    
    $oForm->getBloc('action')->addChild(new HTML_Button(t('Annuler'), "getAJAX('utilisateur-edit').unload()"));
    $oForm->addAction(t('Confirmer'));
    
    $oForm->addChild(sprintf(t('Voulez vous supprimer l\'utilisateur "%s" ?'), new HTML_Strong($oUtilisateur->v_user)));
    
    return $oForm;
  }
  
  public function actionDelete_do() {
    
    if (!$iId = $this->getId()) return $this->errorRedirect(t('Numéro de client non spécifié'));
    
    db::query('DELETE FROM utilisateur_role WHERE id_utilisateur = '.$iId);
    db::query('DELETE FROM utilisateur WHERE id = '.$iId);
    
    $oRedirect = new Redirect();
    
    $oRedirect->addMessage(t('Utilisateur supprimé'), 'success');
    
    $oRedirect->setArgument('action', 'script');
    $oRedirect->setArgument('script', "updateAJAXList('/simple/utilisateur/table', 'utilisateur-list')");
    
    return $oRedirect;
  }
  
  public function actionLogin($oRedirect) {
    
    Controler::getWindow()->addCSS('/web/form.css');
    Controler::getWindow()->getBloc('content-title')->addChild('Connexion');
    Controler::getWindow()->getBloc('content')->addClass('access');
    
    $aSchema = $this->getSchema('login');
    
    $aSchema['v_password']['required'] = true;
    $aSchema['v_user']['suffixe'] = $aSchema['v_password']['suffixe'] = '';
    
    $oForm = new HTML_Form('/utilisateur/login_do');
    $oForm->displayMark(false);
    $oForm->addClass('float-block center-block col1-5 customize-block');
    $oForm->addChild(new HTML_Style('', '.form-content { margin-top: 5em; margin-bottom: 5em; }'));
    $oForm->addChild(new HTML_Input('hidden', $oRedirect->getSource(), array('name' => 'redirect')));
    
    $oForm->build($aSchema, array(), $oRedirect->getMessages());
    
    $oForm->addAction(t('Connexion'));
    
    return $oForm;
  }
  
  public function actionLogin_do() {
    
    $aSchema = $this->getSchema('login');
    $oRedirect = new Redirect("/utilisateur/login", $this->checkRequest($aSchema));
    
    if (!$oRedirect->getMessages('warning')) {
      
      $aFields = $this->importPost($aSchema);
      $aFields['v_password'] = 'SHA('.$aFields['v_password'].')';
      
      $rUtilisateur = db::query('SELECT * FROM utilisateur WHERE '.db::buildWhere($aFields, 'LIKE', 'AND'));
      
      if (!mysql_num_rows($rUtilisateur)) $oRedirect->addMessage('Nom d\'utilisateur ou mot de passe incorrect !', 'warning');
      else {
        
        // Authentification réussie !
        
        $oUtilisateur = mysql_fetch_object($rUtilisateur);
        $rRole = db::query("SELECT * FROM utilisateur_role LEFT JOIN role ON id_role = id WHERE id_utilisateur = {$oUtilisateur->id}");
        
        // Ajout des rôles
        
        $aRoles = array();
        
        if (mysql_num_rows($rRole)) while ($oRole = mysql_fetch_object($rRole)) $aRoles[] = $oRole->v_nom;
        
        // Création de l'utilisateur
        
        $aArguments = array(
          'id' => $oUtilisateur->id,
          'nom' => $oUtilisateur->v_nom,
          'prenom' => $oUtilisateur->v_prenom,
        );
        
        $oUser = new User($oUtilisateur->v_user, $aRoles, $aArguments);
        $oUser->login();
        
        Controler::setUser($oUser);
        
        // Si il y'a redirection
        
        if (isset($_POST['redirect']) && $_POST['redirect'] && !in_array($_POST['redirect'], array(PATH_LOGIN, PATH_LOGOUT))) {
          
          $sPath = $_POST['redirect'];
          Controler::addMessage(sprintf(t('Redirection vers "%s"'), new HTML_Strong($sPath)));
          
        } else $sPath = '/';
        
        $oRedirect->setPath($sPath);
        $oRedirect->addMessage(t('Bienvenue '.$oUtilisateur->v_prenom.'. Vous n\'avez pas de nouveau message.'), 'success');
      }
      
    } else $oRedirect->setArgument('id', $iId);
    
    return $oRedirect;
  }
  
  public function actionLogout($oRedirect) {
    
    Controler::getWindow()->getBloc('content-title')->addChild('Déconnexion');
    
    Controler::getUser()->logout();
    
    $oRedirect->addMessage('Déconnexion effectuée.');
    $oRedirect->setPath('/utilisateur/login');
    return $oRedirect;
  }
}