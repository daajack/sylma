<?php

define('PATH_ACTIONBUILDER', '/system/action-builder');
define('NS_ACTIONBUILDER', PATH_ACTIONBUILDER.'/schema');

class ActionBuilder extends XML_Processor  {
  
  private $oMethods;
  private $aNS = array('la' => NS_ACTIONBUILDER);
  
  public function __construct() {
    
    $this->oMethods = new XML_Document(new XML_Element('la:root', null, null, NS_ACTIONBUILDER));
  }
  
  public function onElement($oElement) {
    
    $mResult = null;
    
    if (in_array($oElement->getName(true), array('layout', 'layer'))) $oElement->setAttribute('path', $this->getAction()->getPath()->getOriginalPath());
    
    if ($this->isFirst()) {
      
      $oElement->set($this->buildChildren($oElement));
      
      $mResult = new XML_Document(new HTML_Div($oElement));
      
      $this->buildResult($this->parseJS($mResult));
      $mResult = $mResult->getFirst();
      
    } else {
      
      switch ($oElement->getName(true)) {
        
        case 'replace-events' :
          
          $oDocument = new XML_Document(new XML_Element('root', $this->buildChildren($oElement)));
          $this->replaceMethodsDefault($oDocument);
          
          $mResult = $oDocument->getChildren();
          
        break;
        
        default : 
          
          $oElement->set($this->buildChildren($oElement));
          $mResult = $oElement;
          
        break;
      }
    }
    
    return $mResult;
  }
  /*
  public function replaceMethodsArray($oDocument) {
    
    $aMethods = array('event', 'click', 'dblclick', 'mousedown', 'mouseup', 'mouseover', 'mouseout', 'mousemove', 'key-down');
    foreach ($aMethods as &$sMethod) $sMethod = '//la:'.$sMethod;
    
    $this->replaceMethods($oDocument->query(implode(' | ', $aMethods), $this->aNS));
  }
  */
  public function replaceMethodsDefault($oDocument) {
    
    $this->replaceMethods($oDocument->query('//la:event', $this->aNS));
  }
  
  /*
   * Replace methods with unique id
   **/
  
  public function replaceMethods($oMethods) {
    
    foreach ($oMethods as $oMethod) {
      
      if ($oMethod->getName(true) == 'event') $sEvent = $oMethod->getAttribute('name');
      else $sEvent = $oMethod->getName(true);
      
      $sChildId = uniqid('method-');
      $oResult = new XML_Element($sEvent, null, array('id' => $sChildId));
      
      /*$aDatas = array();
      $sContent = '';
      
      if ($oDatas = $oMethod->query('la:data', $this->aNS)) {
        
        foreach ($oDatas as $oData) {
          
          $aDatas[$oData->getAttribute('name')] = ($oData->getAttribute('format') == 'object') ? $oData->getValue() : addQuote($oData->getValue());
          $oData->remove();
        }
      }
      
      foreach ($aDatas as $sKey => $sValue) $sContent .= "%$sKey, $sValue%;\n";*/
      
      $sContent = $oMethod->getValue();
      $aReplaces = array(
        '/%([\w-_]+)%/'         => '\$(this).retrieve(\'$1\')',
        '/%([\w-_]+)\s*,\s*([^%]+)%/'  => '\$(this).store(\'$1\', $2)');
      
      $oResult->add(preg_replace(array_keys($aReplaces), $aReplaces, $sContent));
      $this->oMethods->add($oResult);
      
      $oMethod->replace(new XML_Element('la:method', null, array(
        'id' => $sChildId,
        'event' => $sEvent,
        'extract-ref' => 'parent'), NS_ACTIONBUILDER));
    }
  }
  
  /*
   * Build cross references with specifics attributes 
   **/
  
  public function parseJS($oDocument) {
    
    foreach ($oDocument->query("//la:layout | //la:layer | //la:object | //la:method", $this->aNS) as $oElement) {
      
      $oRefNode = null;
      $sName = uniqid('object-');
      
      // Attributes replacements
      
      $aReplacements = array('class' => 'extend-class');
      
      foreach ($aReplacements as $sAttribute => $sNewAttribute) {
        
        if ($sValue = $oElement->getAttribute($sAttribute)) {
          
          $oElement->setAttribute($sNewAttribute, $sValue);
          $oElement->setAttribute($sAttribute);
        }
      }
      
      // Parsing base and class extend's name
      
      $aReplacements = array('extend-class', 'extend-base');
      
      foreach ($aReplacements as $sAttribute) {
        
        if ($sClassBase = $oElement->getAttribute($sAttribute)) {
          
          $sClassResult = '';
          
          foreach (explode('.', $sClassBase) as $sClass) $sClassResult .= '['.addQuote($sClass).']';
          $oElement->setAttribute($sAttribute, $sClassResult);
        }
      }
      
      // Define Reference Axis
      
      if ($sRefAxis = $oElement->getAttribute('extract-ref')) $oElement->removeAttribute('extract-ref');
      
      switch ($sRefAxis) {
        
        case 'parent' :
          
          if (!$oElement->isRoot()) $oRefNode = $oElement->getParent();
          else dspm(xt('ActionBuilder : Référence impossible, l\'objet %s n\'a pas de parent dans %s', view($oElement), $this->getAction()->getPath()->parse()), 'action/error');
          
        break;
        
        case 'child' :
        default : 
          
          if ($oElement->hasChildren() && $oElement->getFirst()->isElement()) {
            
            if ($oElement->getFirst()->getNamespace() == $oElement->getNamespace()) $oRefNode = $oElement->get("*[namespace-uri() != '".NS_ACTIONBUILDER."']", $this->aNS);
            else $oRefNode = $oElement->getFirst();
            
          } else dspm(xt('ActionBuilder : Référence impossible, l\'objet %s n\'a pas d\'enfant valide dans %s', view($oElement), $this->getAction()->getPath()->parse()), 'action/error');
          
        break;
      }
      
      // Build references
      
      if ($oRefNode) {
        
        if ($oElement->getName(true) == 'method') {
          
          // method
          
          if ($sRefId = $oRefNode->getId()) $oElement->setAttribute('id-node', $sRefId);
          else {
            
            $oParent = $oElement->get("ancestor::*[namespace-uri() = '".NS_ACTIONBUILDER."'][position() = 1]");
            $oParentNode = $oElement->getDocument()->get("//*[@id='{$oParent->getAttribute('id-node')}']");
            
            $sPath = '#'.$oParentNode->getId().' > '.$oRefNode->getCSSPath($oParentNode);
            
            $oElement->setAttribute('path-node', $sPath);
          }
          
        } else {
          
          // layout, layer, object
          
          if (!$sId = $oRefNode->getId()) $oRefNode->setAttribute('id', $sName);
          else $sName = $sId;
          
          $oElement->setAttribute('id-node', $sName);
          
        }
        
      } else Controler::addMessage(xt('ActionBuilder : Pas de référence pour l\'élément %s dans %s', view($oElement), $this->getAction()->getPath()->parse()), 'action/error');
      
      if ($oElement->getParent() && $oElement->getParent()->getName(true) != 'group' && (!$sElementName = $oElement->getAttribute('name'))) {
        
        $oElement->setAttribute('name', $sName);
      }
    }
    
    return $oDocument->extractNS(NS_ACTIONBUILDER, false); // Extract action tree
  }
  
  public function buildResult($oScript) {
    
    // Parse as JSON xml => array() then add the result in Controler
    
    $oTemplate = new XSL_Document(PATH_ACTIONBUILDER.'/index.xsl');
    //dspf($oScript);
    if ($oResult = $oTemplate->parseDocument($oScript)) {
      // dspf($oResult);
      list(, $aResult) = $oResult->toArray();
      Controler::addResult(json_encode($aResult), 'txt');
      
      // Add methods in JS element
      
      $oTemplate = new XSL_Document(PATH_ACTIONBUILDER.'/methods.xsl');
      // $oTemplate->setParameter('node-id', $sRoot);
      
      Controler::getWindow()->addJS('', $oTemplate->parseDocument($this->oMethods, false));
      
    } else dspm('Action-Builder : Aucune balises récupérées !', 'action/warning');
  }
}