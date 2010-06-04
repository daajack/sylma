<?php

class Users extends Form_Controler {
  
  private $sPathModule = '/sylma/modules/users';
  
  public function loadUser($sName) {
    
    if (Controler::getUser()->getName() != $sName && !Controler::getUser()->isMember('0'))
      Controler::errorRedirect('Nom d\'utilisateur incorrect !');
    
    $sName = db::formatString($sName);
    $oResult = db::getXML("SELECT us.v_name, ut.* FROM user AS us LEFT JOIN profil AS ut ON ut.v_user = us.v_name WHERE v_name = $sName");
    
    if ($oResult->isEmpty()) {
      
      Controler::errorRedirect('Cet utilisateur n\'existe pas !');
    }
    
    return $oResult;
  }
  
  public function getList() {
    
    // Controler::getWindow()->getBloc('content-title')->add('Liste des utilisateurs');
    
    $oResult = db::queryXML('SELECT us.v_name, ut.v_prenom, ut.v_nom FROM user AS us LEFT JOIN profil AS ut ON ut.v_user = us.v_name');
    
    $oView = db::buildTable($oResult, array('Utilisateur', 'Prénom', 'Nom'), '/utilisateur/edit/');
    
    return $oView;
  }
  
  public function login_do() {
    
    $oSchema = new XML_Document($this->sPathModule.'/user.bml', MODE_EXECUTION);
    $aSchema = $oSchema->getChildren()->toArray('id');
    
    $oRedirect = new Redirect("/login", $this->checkRequest($aSchema));
    
    if (!$oRedirect->getMessages('warning')) {
      
      $aFields = $this->importPost($aSchema, true);
      $aFields['v_password'] = addQuote(sha1($_POST['v_password']));
      
      $oUsers = new XML_Document($this->sPathModule.'/users.xml', MODE_EXECUTION);
      
      if (!$oUser = $oUsers->get("//user[@name = {$aFields['v_name']} and @password = {$aFields['v_password']}]")) {
        
        $oRedirect->addMessage('Nom d\'utilisateur ou mot de passe incorrect !', 'warning');
        
      } else {
        
        // Authentification réussie !
        
        $sName = $oUser->getAttribute('name');
        
        // Ajout des rôles
        
        $aGroups = array(SYLMA_AUTHENTICATED);
        
        $oAllGroups = new XML_Document($this->sPathModule.'/groups.xml', MODE_EXECUTION);
        
        $oGroups = $oAllGroups->query("group[@owner = {$aFields['v_name']}]/@name | group[member = {$aFields['v_name']}]/@name");
        foreach ($oGroups as $oAttribute) $aGroups[] = $oAttribute->getValue();
        
        // Création de l'utilisateur
        
        $oRealUser = new User($sName, $aGroups);
        
        Controler::setUser($oRealUser);
        
        // Ajout du profil
        
        $oProfil = new XML_Document($oUser->read('@path'), MODE_EXECUTION);
        
        if (!$oProfil->isEmpty()) {
          
          $sFirstName = $oProfil->read('first-name');
          $oRealUser->setArguments(array('full-name' => $sFirstName.' '.$oProfil->read('last-name')));
          
        } else $sFirstName = '... visiteur';
        
        $oRealUser->login();
        
        // Si il y'a redirection
        
        if (isset($_POST['redirect']) && $_POST['redirect'] && !in_array($_POST['redirect'], array(PATH_LOGIN, PATH_LOGOUT))) {
          
          $sPath = $_POST['redirect'];
          Controler::addMessage(xt('Redirection vers "%s"', new HTML_Strong($sPath)));
          
        } else $sPath = '/user';
        
        $oRedirect->setPath($sPath);
        $oRedirect->addMessage(t('Bienvenue '.$sFirstName.'. Vous n\'avez pas de nouveau message.'), 'success');
      }
      
    }
    
    return $oRedirect;
  }
  
  public function logout() {
    
    // Controler::getWindow()->getBloc('content-title')->add('Déconnexion');
    
    Controler::getUser()->logout();
    
    $oRedirect = new Redirect('/login');
    $oRedirect->addMessage('Déconnexion effectuée.');
    
    return $oRedirect;
  }
}
