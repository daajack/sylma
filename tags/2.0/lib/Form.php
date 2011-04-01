<?php

class Form_Controler {
  
  //private $aSchemas = array();
  private $aNS = array('fs' => SYLMA_NS_FORM_SCHEMA);
  /*
  public function getSchema($sSchema = '') {
    
    if (array_key_exists($sSchema, $this->aSchemas)) return $this->aSchemas[$sSchema];
    else return array();
  }
  
  public function getSchemas() {
    
    $aSchemas = array();
    foreach (func_get_args() as $sArg) $aSchemas += $this->getSchema($sArg);
    
    return $aSchemas;
  }
  
  public function setSchemas($aSchemas) {
    
    $this->aSchemas = $aSchemas;
  }
  
  public function addSchemas($aSchemas) {
    
    $this->aSchemas += $aSchemas;
  }
  */
  protected function checkRequest(XML_Document $oSchema) {
    
    $aMsg = array();
    
    if (!$oSchema) $aMsg[] = new Message(t('Aucun schéma défini, le contrôle des champs ne peut pas s\'effectuer !', 'form/error'));
    
    $sError = 'form/warning';
    
    foreach ($oSchema->getChildren() as $nField) {
      
      // Si le paramètre 'deco' est à true, la valeur n'est pas contrôlée
      
      if ($nField->read('fs:deco', $this->aNS)) continue;
      
      $oTitle = new HTML_Tag('strong', $nField->read('fs:title', $this->aNS));
      $sKey = $nField->getId();
      $aField = array('field' => $sKey);
      
      if (!array_key_exists($sKey, $_POST) || !$_POST[$sKey]) {
        
        // Si le champs est requis
        
        if (strtobool($nField->read('fs:required', $this->aNS))) {
          
          $aMsg[] = new Message(xt('Le champ "%s" est obligatoire.', $oTitle), $sError, $aField);
        }
        
      } else {
        
        // Test des types
        
        $mValue = $_POST[$sKey];
        
        switch ($nField->read('fs:type', $this->aNS)) {
          
          // Integer
          
          case 'key' :
            
            if ($oOptions = $nField->get('fs:options', $this->aNS)) {
              
              if ($oOptions->isTextElement()) { // text options
                
                $iCount = count(explode(',', $oOptions->read()));
                
                if (!is_numeric($mValue) || !$mValue || $mValue > $iCount - 1) {
                  
                  $aMsg[] = new Message(xt('L\'option choisie du champ "%s" n\'est pas valide', $oTitle), $sError, $aField);
                }
                
              } else if ($oOptions->hasElementChildren()) { // elements options
                
                $mValue = addQuote($mValue);
                
                if (!$oOptions->get("*[@value=$mValue]")) {
                  
                  $aMsg[] = new Message(xt('L\'option choisie du champ "%s" n\'est pas valide', $oTitle), $sError, $aField);
                }
              }
            }
            
          break;
          
          case 'integer' :
            
            $fValue = floatval($mValue); $iValue = intval($mValue);
            
            if (!is_numeric($mValue) || $fValue != $iValue) {
              
              $aMsg[] = new Message(xt('Le champ "%s" doit être un nombre entier.', $oTitle), $sError, $aField);
            }
            
          break;
          
          // Float
          
          case 'float' :
            
            if (!is_numeric($mValue)) {
              
              $aMsg[] = new Message(xt('Le champ "%s" doit être un nombre.', $oTitle), $sError, $aField);
            }
            
          break;
          
          // Date
          
          case 'date' :
            
            
            
          break;
          
          // E-mail
          
          case 'email' :
            
            $sAtom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';   // caractères autorisés avant l'arobase
            $sDomain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)'; // caractères autorisés après l'arobase (nom de domaine)
            
            $sRegex = '/^'.$sAtom.'+(\.'.$sAtom.'+)*@('.$sDomain.'{1,63}\.)+'.$sDomain.'{2,63}$/i';
            
            if (!preg_match($sRegex, $mValue)) {
              
              $aMsg[] = new Message(xt('Le champ "%s" n\'est pas une adresse mail valide.', $oTitle), $sError, $aField);
            }
            
          break;
        }
        
        // Si une taille minimum est requise
        
        if (($iMinSize = $nField->read('fs:min-size', $this->aNS)) && strlen($mValue) < $iMinSize) {
          
          $oMessage = xt('Le champ "%s" doit faire au moins %s caractères', $oTitle, new HTML_Strong($iMinSize));
          $aMsg[] = new Message($oMessage, $sError, $aField);
        }
      }
    }
    
    return $aMsg;
  }
  /*
  public function importPost($aSchema, $bXML = false) {
    
    $aFields = array();
    
    foreach ($aSchema as $sField => $aField) {
      
      // Si le filtre 'deco' est activé, le champs n'est pas inséré dans la base
      
      if (isset($aField['deco']) && $aField['deco']) continue;
      
      $sType = array_val('type', $aField);
      $mValue = false;
      
      if (array_key_exists($sField, $_POST)) {
        
        $sValue = $_POST[$sField];
        
        if ($sType == 'date') {
          
          // Date
          
          $mValue = db::buildDate($sValue);
          
          // if (!$sValue) $sValue = 'NULL';
          // else $sValue = db::formatString($sValue);
          
        } else {
          
          // Autres
          
          if ($bXML) $mValue = addQuote(xmlize($sValue));
          else $mValue = db::formatString($sValue); 
        }
        
      } else {
        
        // Booléen
        
        if ($sType == 'bool') $mValue = 0;
      }
      
      if ($mValue !== false) $aFields[$sField] = $mValue;
    }
    
    return $aFields;
  }*/
}

