<?php

namespace sylma\modules\users;
use sylma\core, sylma\schema;

class Form extends schema\cached\form\Form {

  public function validate() {

    $bResult = false;
    
    if ($this->getMode() === 'delete')
    {
      return true;
    }
    
    $sConfirmAlias = 'password_confirm';

    $sConfirm = $this->read($sConfirmAlias, false);
    $password = $this->getElement('password');

    $user = $this->getManager('user');

    if (!$sConfirm) {

      if ($this->getMode() == 'insert') {

        $this->addMessage($this->translate('You must confirm the password'), array('error' => true));
      }
      else
      {
        if (!$iID = intval($this->read('id'))) {

          $this->launchException('ID not valid');
        }

        $this->removeElement('password');

        $bResult = parent::validate();
      }
    }
    else {

      if (!$password->validate() || $sConfirm !== $password->getValue()) {

        $this->addMessage($this->translate('Passwords do not match'), array('error' => true));
      }
      else {

        $password->setValue($user->getManager()->crypt($password->getValue()));

        $bResult = parent::validate();
      }
    }

    if (!$this->getElements()) {

      $this->addMessage($this->translate('No data updated'));
    }

    return $bResult;
  }
}

