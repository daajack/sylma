<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\schema;

class Attribute extends schema\parser\component\Basic implements core\arrayable {

  protected $type = null;
  protected $typeName = null;

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

      if ($el = $this->getx('xs:simpleType')) {

        $type = $this->parseComponent($el);
      }
      else {

        $type = $this->getHandler()->getType('string', $this->getNamespace('xs'));
      }

      $this->type = $type;
    }

    if ($qualified = $this->readx('@form', false)) {

      $qualified = $qualified === 'qualified';
    }
    else {

      $qualified = $this->getHandler()->useAttributeForm;
    }

    $this->name = $name;
    $this->qualified = $qualified;
    $this->ref = $ref;
  }

  public function asArray() {

    return array(
      'element' => 'attribute',
      'namespace' => $this->getNamespace(),
      'name' => $this->name,
      'qualified' => $this->qualified,
      'type' => $this->type,
      'ref' => $this->ref,
      'type' => $this->type,
      'typeName' => $this->typeName,
      'source' => $this->getNode()->asToken(),
    );
  }
}

