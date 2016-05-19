<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\schema;

class Element extends schema\parser\component\Element implements core\arrayable {

  public function parseRoot(dom\element $el) {

    $this->setNode($el, false);

    $this->loadName();
    $this->loadNamespace();
    $this->setNamespaces($this->getHandler()->getNS());

    $this->parseType($el);
  }

  protected function loadName() {

    $this->setName($this->readx('@name'));
  }

  public function loadNamespace($ns = '') {

    $this->setNamespace($this->getHandler()->getTargetNamespace(), 'element');
    return;

    if (!$this->getNamespace('element')) {

      if (!$ns) {

        $ns = $this->getParser()->getTargetNamespace();
      }

      $this->setNamespace($ns, 'element');
      //$this->log("load > {$this->asToken()} [" . get_class($this) . ']');
    }
  }

  protected function parseType(dom\element $el) {

    $type = null;
    $ref = $this->readx('@ref', false);

    if (!$ref) {

      $ref = $this->readx('@substitutionGroup', false);
    }

    if ($ref) {

      $ns = $this->getHandler()->parseName($ref, null, $el);

      $this->setNamespace($ns[0], 'element');
      $name = $ns[1];
    }
    else {

      $this->setNamespace($this->getHandler()->getTargetNamespace());
      $name = $this->readx('@name', false);
    }

    if ($stype = $this->readx('@type', false)) {

      $type = $this->getHandler()->parseName($stype);
      $this->typeName = $type;
    }
    else if (!$ref) {

      if (!$el = $this->getx('xs:complexType | xs:simpleType')) {

        $this->launchException('No type found in : ' . $this->getNode()->asToken(), get_defined_vars());
      }

      $type = $this->parseComponent($el);
      $this->type = $type;
    }

    $this->qualified = $this->getHandler()->useElementForm;
    $this->name = $name;
    $this->ref = $ref;
  }

  public function asArray() {

    return array(
      'element' => 'element',
      'name' => $this->name,
      'namespace' => $this->getNamespace(),
      'qualified' => $this->qualified,
      'ref' => $this->ref,
      'type' => $this->type,
      'typeName' => $this->typeName,
      'source' => $this->getNode()->asToken(),
    );
   }
}

