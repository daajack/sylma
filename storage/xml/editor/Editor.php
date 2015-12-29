<?php

namespace sylma\storage\xml\editor;
use sylma\core, sylma\dom, sylma\storage\fs;

class Editor extends core\module\Domed {

  const FACTORY_RELOAD = false;
  const FILE_MANAGER = 'fs/editable';

  const NS = 'http://2013.sylma.org/modules/stepper';

  public function __construct(core\argument $args, core\argument $post) {

    //$this->setDirectory(__DIR__);
    $this->setNamespace(self::NS);
    $this->loadDefaultSettings();

    $this->setSettings($post);
    $this->setSettings($args);

    if ($sDirectory = $this->read('dir', false)) {

      $this->setDirectory($this->getManager(self::FILE_MANAGER)->getDirectory($sDirectory));
    }
  }

  public function init(fs\file $file) {

    $this->setDocument($file->asDocument());
  }

  protected function buildElement(dom\element $el) {

    $aResult = array(
      '_alias' => 'element',
      'prefix' => $el->getPrefix(),
      'name' => $el->getName(),
      'attribute' => array(),
      'format' => $el->isComplex() ? 'complex' : (strlen($el->read()) < 100 ? 'text' : 'complex'),
    );

    foreach ($el->getAttributes() as $attr) {

      $aResult['attribute'][] = array(
        'prefix' => $attr->getPrefix(),
        'name' => $attr->getName(),
        'content' => (string) $attr,
      );
    }

    $aChildren = array();

    foreach ($el->getChildren() as $child) {

      if ($child instanceof dom\element) {

        $aChildren[] = $this->buildElement($child);
      }
      else if ($child instanceof dom\comment) {

        $aChildren[] = array(
          '_alias' => 'comment',
          'content' => (string) $child,
        );
      }
      else {

        $sContent = (string) $child;
        //$sContent = trim(preg_replace(array('/[\t ]+/', '/\n\s*/'), array(' ', "\n"), $sContent));

        $aChildren[] = array(
          '_alias' => 'text',
          'content' => $sContent,
        );
      }
    }

    if ($aChildren) {

      $aResult['children'] = array(
        array(
          '_all' => $aChildren
        ),
      );
    }

    return $aResult;
  }

  public function asJSON() {

    $doc = $this->getDocument();

    $aResult = array('element' => array($this->buildElement($doc->getRoot())));

    return $aResult;
  }
}

