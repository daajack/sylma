<?php

class Error extends Action {
  
  public function actionView() {
    
    Controler::getWindow()->addCSS('/web/error.css');
    Controler::getWindow()->getBloc('content-title')->addChild(t('Erreur'));
    Controler::getWindow()->getBloc('content')->addClass('error');
    
    $this->addChild(new HTML_Strong(t('Une erreur s\'est produite !')));
    $this->addChild('<br/>'.t('Si le problème persiste, merci de contacter l\'administrateur.'));
    
    return $this;
  }
  
  public function actionAccess() {
    
    Controler::getWindow()->addCSS('/web/error.css');
    Controler::getWindow()->getBloc('content-title')->addChild(t('Erreur'));
    Controler::getWindow()->getBloc('content')->addClass('access');
    
    $this->addChild(new HTML_Strong(t('Accès refusé !')));
    $this->addChild('<br/>'.t('Désolé, vous n\'avez pas les droits nécessaires pour accéder à cette page.'));
    
    return $this;
  }
}