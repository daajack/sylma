<?php

namespace sylma\schema\xsd\component;
use sylma\core, sylma\dom, sylma\schema\parser, sylma\parser\reflector;

class ComplexType extends parser\component\Complex implements core\arrayable {

  protected $aAttributes = array();
  protected $aAttributeGroups = array();

  protected $children;
  protected $base = null;
  protected $simpleContent = null;
  protected $prepared = false;

  public function parseRoot(dom\element $el) {

    $this->setNode($el);
    $this->setName($el->readx('@name', array(), false));
    $this->setNamespace($this->getHandler()->getTargetNamespace());

    $this->prepare();
  }

  public function prepare() {

    if (!$this->prepared) {

      $this->prepared = true;
      $this->prepareContent();
    }
  }

  protected function prepareContent() {

    if ($content = $this->getx('self:simpleContent', false)) {

      $this->simpleContent = $content;

    } else if ($content = $this->getx('self:complexContent', false)) {

      $extension = $content->getx('self:extension | self:restriction', array(), false);

      if ($extension) {

        $this->children = $this->loadChildren($extension);
        $ns = $this->getHandler()->parseName($extension->readx('@base'));
        $this->base = array(
          'namespace' => $ns[0],
          'name' => $ns[1],
        );
      }
    }
    else {

      $this->children = $this->loadChildren($this->getNode());
    }
  }

  protected function loadChildren(dom\element $el) {

    $result = array();

    foreach ($el->getChildren() as $child) {

      if (!$child instanceof dom\element) {

        continue;
      }

      $component = $this->parseComponent($child);
      $result[] = $component;

      switch ($child->getName()) {

        case 'group' :
        case 'sequence' :
        case 'choice' :
        case 'all' :

          $this->setParticle($component);
          break;

        case 'attribute' :

          $this->addAttribute($component);
          break;

        case 'attributeGroup' :

          $this->addAttributeGroup($component);
          break;

        case 'anyAttribute' :

          $this->addAttributeGroup($component);
          break;

        case 'annotation' :

          //$this->annotation = $component;
          break;

        default :

          $this->launchException('Unknown element : ' . $child->getName(), get_defined_vars());
      }
    }

    return $result;
  }

  protected function addAttribute(Attribute $attribute) {

    $this->aAttributes[] = $attribute;
  }

  protected function addAttributeGroup(AttributeGroup $group) {

    $this->aAttributeGroups[] = $group;
  }

  public function asArray() {

    if ($this->simpleContent) {

      $content = null;
    }
    else {

      $content = $this->children;
    }
//if (preg_match('/apply/', $this->getName())) dsp($this->getName(), $this->getNamespace());
    return array(
      'element' => 'complexType',
      'namespace' => $this->getNamespace(),
      'name' => $this->getName(),
      'base' => $this->base,
      'mixed' => core\functions\strtobool($this->readx('@mixed')),
      //'extends' => $this->get
      'content' => $content,
    );
  }
}


