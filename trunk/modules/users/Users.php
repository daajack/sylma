<?php

class Users extends Form_Controler {
  
  public function login_do() {
    
    $oSchema = new XML_Document(extractDirectory(__file__).'/user.bml', MODE_EXECUTION);
    
    $oRedirect = new Redirect(Controler::getSettings('actions/login'), $this->checkRequest($oSchema));
    
    if (!$oRedirect->getMessages('warning')) {
      
      $sUser = addQuote($_POST['v_name']);
      $sPassword = addQuote(sha1($_POST['v_password']));
      
      $oUsers = new XML_Document(Controler::getSettings('module[@name="users"]/users/@path'), MODE_EXECUTION);
      
      if (!$oUser = $oUsers->get("//user[@name = $sUser and @password = $sPassword]")) {
        
        $oRedirect->addMessage('Nom d\'utilisateur ou mot de passe incorrect !', 'warning');
        
      } else {
        
        // Authentification réussie !
        
        $sName = $oUser->getAttribute('name');
        $sRedirect = Controler::getSettings('module[@name="users"]/@redirect');
        
        // Ajout des rôles
        
        $aGroups = array(SYLMA_AUTHENTICATED);
        
        $oAllGroups = new XML_Document(Controler::getSettings('module[@name="users"]/groups/@path'), MODE_EXECUTION);
        
        $oGroups = $oAllGroups->query("group[@owner = $sUser]/@name | group[member = $sUser]/@name");
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
        
        if (isset($_POST['redirect']) && $_POST['redirect'] && !in_array($_POST['redirect'], array(SYLMA_PATH_LOGIN, SYLMA_PATH_LOGOUT))) {
          
          $sRedirect = $_POST['redirect'];
          Controler::addMessage(xt('Redirection vers "%s"', new HTML_Strong($sRedirect)));
        }
        
        $oRedirect->setPath($sRedirect);
        $oRedirect->addMessage(t('Bienvenue '.$sFirstName.'. Vous n\'avez pas de nouveau message.'), 'success');
      }
      
    }
    
    return $oRedirect;
  }
  
  public function logout() {
    
    // Controler::getWindow()->getBloc('content-title')->add('Déconnexion');
    
    Controler::getUser()->logout();
    
    $oRedirect = new Redirect('/');
    $oRedirect->addMessage('Déconnexion effectuée.');
    
    return $oRedirect;
  }
}
