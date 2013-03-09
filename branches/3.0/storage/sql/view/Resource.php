<?php

namespace sylma\storage\sql\view;
use sylma\core, sylma\dom, sylma\parser\reflector, sylma\template, sylma\storage\fs;

class Resource extends reflector\handler\Elemented implements reflector\elemented {

  protected $ID;
  protected $tree;
  protected $sSource;

  const NS = 'http://2013.sylma.org/storage/sql/view';

  public function parseRoot(dom\element $el) {

    $el = $this->setNode($el);
    $this->allowForeign(true);

    $this->parseSource();


    return $this;
  }

  protected function addID() {

    $row = $this->getTree();

    if ($id = $this->getNode()->getx('self:id')) {

      $select = $row->getQuery();

      $this->parseID($id);
      $select->setWhere($row->getElement('id', $row->getNamespace()), '=', $this->getID());
    }
    else {

      $this->throwException('Not implemented');
    }
  }

  /**
   *
   * @param \sylma\storage\fs\file $file
   * @return \sylma\schema\parser\schema
   */
  public function setSchema(fs\file $file) {

    $builder = $this->getManager('parser')->loadBuilder($file, null, $this->getArguments());

    $schema = $builder->getSchema($file, $this->getWindow());
    $schema->setView($this->getParent());

    $field = $schema->getElement();

    $this->setTree($field);
    $this->addID();

    return $schema;
  }

  protected function setTree(template\parser\tree $tree) {

    $this->tree = $tree;
  }

  public function getTree() {

    return $this->tree;
  }

  protected function parseSource() {

    //$this->dsp($this->getNS());
    //$this->dsp($this->elementDocument);
    //$this->dsp($this->elementDocument->getNS());
    //$this->dsp($this->getElement());
    //$this->dsp($this->elementDocument->getRoot());

    $this->setSource($this->getNode()->readx('self:source'));
  }

  protected function setSource($sSource) {

    $this->sSource = $sSource;
  }

  protected function getSource() {

    return $this->sSource;
  }

  protected function parseID(dom\element $el) {

    if ($el->isComplex()) {

      if ($el->countChildren() > 1) {

        $this->throwException(sprintf('Only one child expected in %s', $el->asToken()));
      }

      $mResult = $this->parseElement($el->getFirst());
    }
    else {

      $mResult = $el->read();
    }

    $this->setID($mResult);
  }

  protected function setID($id) {

    $this->ID = $id;
  }

  protected function getID() {

    return $this->ID;
  }
}
