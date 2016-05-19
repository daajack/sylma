<?php

namespace sylma\schema\parser;
use sylma\core, sylma\dom, sylma\schema\parser, sylma\storage\fs;

interface schema extends container {

  function getTargetNamespace();
  function parseName($sName, parser\namespaced $source = null, dom\element $context = null);
  function addSchema(fs\file $file, $force = false);
}

