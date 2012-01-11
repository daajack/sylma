<?php

namespace sylma\core;

interface factory {
  
  /**
   * Used in @class argument for keeping file path for relative path import
   */
  const DIRECTORY_TOKEN = '@sylma-directory';
  
  /**
   * Used in @class argument for keeping trace of last defined class namespace
   */
  const CLASSBASE_TOKEN = '@sylma-classbase';
  
  function create($sName, array $aArguments = array(), $sDirectory = '');
}