<?php

namespace sylma\storage\sql\locale;
use sylma\core, sylma\parser\languages\common;

class Manager extends core\module\Domed
{
  protected $translations = array();
  protected $language;
  
  protected $redirect = array();
  protected $redirectKeys = array();
  
  public function __construct(core\argument $args) {
    
    $this->setDirectory(__FILE__);
    $this->setSettings($args);
    $this->setLanguage($this->getDefault());
    
    $this->loadTranslations();
    $this->loadRedirects($this->get('redirect'));
    $this->loadAliases($this->get('alias'));
  }
  
  public function getDefault() {

    return $this->read('default');
  }
  
  public function setLanguage($suffix) {
    
    $this->language = $suffix;
  }
  
  public function getLanguage() {
    
    return $this->language;
  }
  
  /** Url **/
  
  public function loadRequest(core\request $request) {
    
    $this->loadDomainLanguage();

    $result = (string) $request;
    
    foreach ($this->aliasKeys as $key => $alias) {
      
      $exp = "`$key`";

      if (preg_match($exp, $result)) {

        $result = preg_replace($exp, $alias, $result);
        break;
      }
    }
    
    $request->setPath($result);
  }
  
  public function loadDomainLanguage() {
    
    $domain = $_SERVER['SERVER_NAME'];
    
    foreach ($this->get('domains') as $key => $match) {
      
      if (preg_match("`$match`", $domain)) {
        
        $this->setLanguage($key);
      }
    }
  }
  
  protected function loadAliases(core\argument $aliases) {

    $aliases = $aliases->asArray();
    $all = array();

    foreach ($aliases as $alias => $translations) {
      
      $all[$alias] = $alias;
      
      foreach ($translations as $translation) {
        
        $all[$translation] = $alias;
      }
    }
    
    $this->aliasKeys = $all;
    $this->alias = $aliases;
  }
  
  protected function loadRedirects(core\argument $redirects) {

    $redirects = $redirects->asArray();
    $all = array();

    foreach ($redirects as $alias => $translations) {
      
      $all[$alias] = $alias;
      
      foreach ($translations as $translation) {
        
        $all[$translation] = $alias;
      }
    }
    
    $this->redirectKeys = $all;
    $this->redirect = $redirects;
  }
  
  protected function lookupPage($page, $language) {
    
    $result = null;

    foreach ($this->redirectKeys as $key => $alias) {
      
      if ($key === $page) {
        
        if ($language === $this->getDefault()) {
          
          $result = $alias;
        }
        else {
          
          $result = $this->redirect[$alias][$language];
        }
        
        break;
      }
    }
    
    if (!$result) {

      foreach ($this->aliasKeys as $key => $alias) {

        $exp = "`^$key`";

        if (preg_match($exp, $page)) {

          if ($language === $this->getDefault()) {

            $content = $alias;
          }
          else {

            $content = $this->alias[$alias][$language];
          }

          $result = preg_replace($exp, $content, $page);
          break;
        }
      }
    }
    
    return $result;
  }

  public function getCurrentPage($language) {
    
    $path = $_SERVER['REQUEST_URI'];
    $result = $this->lookupPage($path, $language);

    if (!$result) {
      
      $result = $path;
    }
    
    return '//' . $this->read('domains/' . $language) . $result;
  }
  
  public function getPage($path) {

    $language = $this->getLanguage();
    $result = $this->lookupPage($path, $language);
    
    if (!$result) {
      
      $this->launchException('Cannot find page : ' . $path);
    }
    
    return $result;
  }
  
  /** DB **/
  
  protected function loadTranslations() {
    
    $translations = $this->get('translate');
    $all = $this->get('languages');
    
    foreach ($translations as $key) {
      
      $this->translations[$key] = $all->get($key);
    }
  }
  
  public function getTranslations() {
    
    return $this->translations;
  }
  
  public function getTranslation($value, $page) {
    
    $db = $this->getManager('mysql')->getConnection();
    $suffix = $this->getSuffix();
    $value = trim($value);
    
    $result = $db->read("SELECT content$suffix FROM locale WHERE content = {$db->escape($value)};", true, false);
    
    if ($result === false) {
      
      $page = $db->escape($page);
      $db->insert("INSERT INTO locale (content, page) VALUES ({$db->escape($value)}, $page);");
    }
    
    if (!$result) {
      
      $result = $value;
    }
    
    return $result;
  }
  
  public function getSuffix() {
    
    $result = '';
    
    if ($this->language !== $this->getDefault()) {
      
      $result = '_' . $this->language;
    }
    
    return $result;
  }
}
