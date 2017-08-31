<?php

namespace sylma\storage\sql\locale;
use sylma\core, sylma\parser\languages\common;

class Manager extends core\module\Domed
{
  protected $translations = array();
  protected $language;

  protected $modePrefix = false;
  protected $usePrefix = false;
  protected $redirect = array();
  protected $redirectKeys = array();
  protected $current = '';
  
  public function __construct(core\argument $args) {
    
    $this->setDirectory(__FILE__);
    $this->setSettings($args);

    $this->modePrefix = !$this->get('domains')->asArray();
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
    
    $result = null;
    $redirect = null;
    
    if ($this->modePrefix)
    {
      $lang = $this->loadPrefixLanguage($request);
      $redirect = !$lang;
      
      if (!$lang)
      {
        $lang = $this->loadSessionLanguage();
      }

      if (!$lang)
      {
        $lang = $this->loadBrowserLanguage();
      }
    }
    else
    {
      $lang = $this->loadDomainLanguage();
    }
    
    if ($lang)
    {
      $this->setLanguage($lang);
    }

    if ($redirect)
    {
      $path = (string) $request;
      if (!$lang) $lang = $this->getDefault();

      if ($path === '/') $path = '';

      $result = new core\Redirect('/' . $lang . $path);

    }
    else
    {
      $this->checkAliases($request);
    }

    return $result;
  }
  
  protected function checkAliases(core\request $request)
  {
    $result = (string) $request;

    $this->current = $request->getPath();

    foreach ($this->aliasKeys as $key => $alias) 
    {
      $exp = "`$key`";

      if (preg_match($exp, $result))
      {
        $result = preg_replace($exp, $alias, $result);
        break;
      }
    }

    $request->setPath($result);
  }

  public function saveSession() {

    $_SESSION['locale'] = $this->language;
  }

  protected function loadSessionLanguage() {

    $result = false;

    if (isset($_SESSION['locale']))
    {
      $result = $_SESSION['locale'];
    }

    return $result;
  }

  protected function loadBrowserLanguage() {

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

    return $suffix;
  }

  protected function loadPrefixLanguage($request) {

    $path = $request->getPath();
    $prefix = substr($path, 1, 2);
    $translations = array_keys($this->translations);

    if (in_array($prefix, $translations))
    {
      $sub = substr($path, 3);
      
      if (!$sub)
      {
        $sub = '/';
      }
      
      $request->setPath($sub);
      $this->usePrefix = true;
    }

    return $this->usePrefix ? $prefix : false;
  }

  protected function loadDomainLanguage() {

    $domain = $_SERVER['SERVER_NAME'];
    $result = null;

    foreach ($this->get('domains') as $key => $match) {

      if (preg_match("`^$match`", $domain)) {

        $result = $key;
        break;
      }
    }
    
    return $result;
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
    
    $path = $this->current;
    $result = $this->lookupPage($path, $language);

    if (!$result)
    {
      $result = $path;
    }
    
    if ($result === '/')
    {
      $result = '';
    }

    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on";

    if ($this->modePrefix)
    {
      $root = $_SERVER['SERVER_NAME'] . '/' . $language;
    }
    else
    {
      $root = $this->read('domains/' . $language);
    }

    return 'http' . ($https ? 's' : '') . '://' . $root . $result;
  }
  
  public function getPage($path) {

    $language = $this->getLanguage();
    $query = $this->lookupPage($path, $language);

    if (!$query)
    {
      $query = $path;
    }

    if ($this->usePrefix)
    {
      $prefix = '/' . $this->language;
      
      if ($path === '/')
      {
        $query = '';
      }
    }
    else
    {
      $prefix = '';
    }
    
    return $prefix . $query;
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
    
//    return "[[$result]]";
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
