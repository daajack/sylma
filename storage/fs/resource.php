<?php

namespace sylma\storage\fs;

interface resource {
  
  function getControler();
  
  /**
   * @param boolean $bRecursive Try to get manager from parents
   * @return fs\security\manager|null The security manager
   */
  function getSettings($bRecursive = false);
  
  /**
   * Indicate if the resource exist or not, allow the manipulation of un-existent resource
   */
  function doExist();
  
  /**
   * Check if the file is accessible against the given access mode
   * 
   * @param integer $iMode One of the three access modes : @constant \Sylma::EXECUTE, \Sylma::READ, \Sylma::WRITE ;
   * @return boolean TRUE if access is granted
   */
  function checkRights($iMode);
  
  /**
   * @return fs\directory|null The parent directory or NULL if root directory
   */
  function getParent();
  
  /**
   * @return string The name of the resource without parent's path (ex: index.eml)
   */
  function getName();
  
  /**
   * @return string The full path to the resource (ex : /storage/fs/resource.php)
   */
  function __toString();
}