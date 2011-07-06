<?php

require_once('modules/test/Test.php');

class ArgumentsTest extends Test {
  
  const NS = 'http://www.sylma.org/core/settings/test';
  
  public function __construct() {
    
    $this->setDirectory(__file__);
    $this->setNamespaces(array('self' => self::NS));
    
    parent::__construct();
  }
  
  public function load() {
    
    $aResult = array();
    
    foreach ($this->getDirectory()->getFiles(array('xml'), null, null) as $file) {
      
      $doc = $file->getDocument();
      $aTests = array();
      
      if (!$doc || $doc->isEmpty()) $this->throwException(txt('@file %s cannot be load'));
      
      foreach ($doc->query('self:test', $this->getNS()) as $test) {
        
        if (!$prepare = $test->get('self:prepare', $this->getNS())) {
          
          $this->throwException('No document defined', '@file ' . $doc->getFile());
        }
        
        if (!$expected = $test->get('self:expected', $this->getNS())) {
          
          $this->throwException('No expectation defined', '@file ' . $doc->getFile());
        }
        
        $sPrepare = $prepare->read();
        $sExpected = $expected->read();
        $bResult = false;
        
        try {
          
          eval('$closure = function() { ' . $sPrepare . '; };');
          $args = $closure();
          eval('$closure = function($args) { ' . $sExpected . '; };');
          $bResult = $closure($args);
        }
        catch (SylmaExceptionInterface $e) {
          
        }
        
        $aTest = array(
          '@name' => $test->getAttribute('name'),
          'result' => booltostr($bResult),
        );
        
        if (!$bResult) $aTest['message'] = '';
        $aTests[] = $aTest;
      }
      
      $aResult[] = array(
        'description' => $doc->read('self:description', $this->getNS()),
        '#test' => $aTests,
      );
    }
    
    return $aResult;
  }
  
  public function parse() {
    
    $result = Arguments::buildDocument(array(
      'group' => array(
        'description' => 'Arguments',
        '#group' => $this->load(),
      ),
    ), $this->getNamespace());
    
    return $result;
  }
}
