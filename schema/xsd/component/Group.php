<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom;

class Group extends Particle implements core\arrayable {

  protected $ref = null;

  public function parseRoot(dom\element $el) {

    $this->setNode($el);

    $ref = $this->readx('@ref', false);

    if ($ref) {

      $ns = $this->getHandler()->parseName($ref, null, $el);

      $this->setNamespace($ns[0]);
      $name = $ns[1];
    }
    else {

      $this->buildChildren();

      $this->setNamespace($this->getHandler()->getTargetNamespace());
      $name = $this->readx('@name', false);
    }

    $this->name = $name;
    $this->ref = $ref;
  }

  public function asArray() {

//dsp($this->getNamespace(), $this->name);
    return array(
      'element' => 'group',
      'namespace' => $this->getNamespace(),
      'name' => $this->name,
      'ref' => $this->ref,
      'content' => $this->children,
      'source' => $this->getNode()->asToken(),
      //'content' => $this->readx(),
    );
   }
}

