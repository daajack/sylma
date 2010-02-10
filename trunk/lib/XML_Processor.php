<?php

interface XML_ProcessorInterface {
  
  public function loadElement($oElement);
  public function startAction($oAction);
  public function stopAction();
}

class XML_Processor implements XML_ProcessorInterface {
  
  private $aActions = array();
  //private $bInterfaced = false;
  private $aElements = array();
  
  public function __construct() {
    
    $this->onLoad();
  }
  /*
  public function useInterface($bInterfaced = null) {
    
    if ($bInterfaced !== null) $this->bInterfaced = (bool) $bInterfaced;
    return $this->bInterfaced;
  }
  */
  public function startAction($oAction) {
    
    $this->aActions[] = $oAction;
  }
  
  public function stopAction() {
    
    array_pop($this->aActions);
  }
  
  public function isFirst() {
    
    return (bool) (count($this->aElements) == 1);  
  }
  
  public function runChildren($mBase) {
    
    return $this->getAction()->runInterfaceList($mBase, $this->getElement());
  }
  
  public function buildChildren($oElement) {
    
    if ($oElement && $oElement->hasChildren()) {
      
      $aResults = array();
      foreach ($oElement->getChildren() as $oChild) $aResults[] = $this->getAction()->buildArgument($oChild);
      
      // return new XML_NodeList($aResults);
      return $aResults;
      
    } else return null;
  }
  
  public function getAction() {
    
    if ($this->aActions) return $this->aActions[count($this->aActions) - 1];
    else return null;
  }
  
  public function getElement() {
    
    return array_last($this->aElements);
  }
  
  public function loadElement($oElement) {
    
    $this->aElements[] = $oElement;
    
    return $this->onElement($oElement);
  }
  
  public function onLoad() { }
  public function onElement() { }
  public function parse() { }
}