<?php

namespace sylma\storage\sql\locale;
use sylma\core, sylma\parser\languages\common;

class Manager extends core\module\Domed
{
  protected $translations = array();
  protected $usePrefix = false;
  protected $language;
  
  public function __construct(core\argument $args) {
    
    $this->setDirectory(__FILE__);
    $this->setSettings($args);
    
    $this->loadTranslations();
    $this->loadUserLanguage();
    $this->loadAliases($this->get('alias'));
  }
  
  protected function loadTranslations() {
    
    $translations = $this->get('translate');
    $all = $this->get('all');
    
    foreach ($translations as $key) {
      
      $this->translations[$key] = $all->get($key);
    }
  }
  
  public function loadRequest(core\request $path) {

    $subs = $path->getPath(true);
    
    if ($subs) {
      
      $languages = array();
      
      foreach ($this->getTranslations() as $key => $translation) {
        
        $languages[] = $key;
      }

      if (in_array($subs[0], $languages)) {
        
        $this->usePrefix = true;
        $this->setLanguage($subs[0]);
        array_shift($subs);
        $path->setPath('/' . implode('/', $subs));
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
  
  public function getDefault() {

    return $this->read('default');
  }
  
  public function getTranslations() {
    
    return $this->translations;
  }
  
  public function getLanguage() {
    
    return $this->language;
  }
  
  public function setLanguage($suffix) {
    
    $this->language = $suffix;
  }
  
  protected function loadUserLanguage() {

    $locales = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $languages = array_keys($this->getTranslations());
    $suffix = '';

    foreach ($locales as $locale) {
      
      $language = substr($locale, 0, 2);
      
      if (in_array($language, $languages)) {
        
        $suffix = $language;
        break;
      }
    }

    if (!$suffix) {
      
      $suffix = $this->getDefault();
    }
    
    $this->setLanguage($suffix);
  }
  
  public function getTranslation($value, $page) {
    
    $db = $this->getManager('mysql')->getConnection();
    $suffix = $this->getSuffix();
    $value = trim($value);
    
    $result = $db->read("SELECT content$suffix FROM locale WHERE content = {$db->escape($value)};", false);
    
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
  
  public function getURLPrefix() {
    
    return $this->usePrefix ? '/' . $this->language : '';
  }
  
  public function getPage($language) {
    
    $path = (string) $this->getManager('path')->getSourcePath();
    $result = $path;

    foreach ($this->aliasKeys as $key => $alias) {
      
      if ($key === $path) {
        
        if ($language === $this->getDefault()) {
          
          $result = $alias;
        }
        else {
          
          $result = $this->alias[$alias][$language];
        }
        
        break;
      }
    }

    return $result === '/' ? '' : $result;
  }
  
  public function reflectApplyPath(common\_window $window, array $paths) {
    
    $path = array_shift($paths);
    $locale = $window->addManager('locale');
    
    switch ($path) {
      
      case 'language' : 
        
        $result = $locale->call('getLanguage');
        break;
      
      case 'prefix' :
        
        $result = $locale->call('getURLPrefix');
        break;
      
      case 'page' :
        
        $result = $locale->call('getPage', $paths);
        break;
      
      default :
        
        $this->launchException('Unknown locale path');
    }
    
    return $result;
  }
}
