<?php

namespace sylma\template\binder\_class;
use sylma\core, sylma\parser\languages\common;

class Template extends Builder {

  const TEMPLATE_MODE = 'script';

  /**
   * @var common\js\_function
   */
  protected $template = null;

  /**
   * Sub-objects only added once in asArray()
   */
  protected $bAdded = false;

  /**
   * This class is templated with @ alias
   */
  protected $bTemplate = false;

  //protected $aAliases = array();

  /**
   * @see useAll()
   */
  protected $bAll = false;

  /**
   * @see needAll()
   */
  protected $bNeedAll = true;

  /**
   * Script var
   */
  protected $source;

  /**
   * Sub classes
   */
  protected $aChildren = array();

  protected $aAutoloads = array();

  protected function init() {

    parent::init();
    $this->useAll((bool) $this->readx('@js:all'));
  }

  protected function getAlias() {

    return $this->readx('@js:alias');
  }

  public function addChild($sAlias, $sClass, $bAutoload) {

    $aResult = array();

    if (!isset($this->aChildren[$sAlias])) {

      $window = $this->getWindow();
      $source = $this->getSource();

      $self = $window->createVariable('this');
      $sID = $this->getSpacerID();

      $this->prepareFactory($sAlias, $sClass, $sID, $bAutoload);

      if ($this->useTemplate()) {

        if ($this->useAll()) {

          if ($this->needAll()) {

            $aResult[] = $self->call('buildObjectsAll', array($source->getProperty('_all')));
            $aResult[] = $this->createSpacer($sID);

            $this->needAll(false);
          }
        }
        else  {

          $aResult[] = $self->call('buildObjects', array($window->toString($sAlias), $source->getProperty($sAlias)));
          $aResult[] = $this->createSpacer($sID);
        }
      }
      else {

        $aResult[] = $this->createSpacer($sID);
      }

      $this->aChildren[$sAlias] = true;
    }

    return $aResult;
  }

  protected function prepareFactory($sAlias, $sClass, $sID, $bAutoload) {

    $sPath = "sylma.template.classes.$sAlias";
    $aValues = array(
      'name' => $sClass,
      'node' => $sID,
    );

    if ($bAutoload) {

      $this->aAutoloads[] = $sAlias;
    }

    $obj = $this->getObject();
    $result = $obj->setProperty($sPath, $this->getWindow()->createObject($aValues));

    return $result;
  }

  public function addTo(common\_object $container) {

    if ($this->aAutoloads) {

      $this->getObject()->setProperty('sylma.template.autoloaded', $this->getWindow()->createObject($this->aAutoloads));
    }

    if ($this->useAll()) {

      $this->getObject()->setProperty('sylma.template.mixed', true);
    }

    return parent::addTo($container);
  }

  public function useTemplate($bValue = null) {

    if (is_bool($bValue)) $this->bTemplate = $bValue;

    return $this->bTemplate;
  }

  protected function setSource(common\_var $source) {

    $this->source = $source;
  }

  protected function getSource() {

    return $this->source;
  }

  /**
   * All children added as array
   */
  protected function useAll($bValue = null) {

    if (is_bool($bValue)) $this->bAll = $bValue;

    return $this->bAll;
  }

  /**
   * Only one call required with first child
   */
  protected function needAll($bValue = null) {

    if (is_bool($bValue)) $this->bNeedAll = $bValue;

    return $this->bNeedAll && $this->useAll();
  }

  protected function getSpacerID() {

    if ($this->useAll()) {

      if ($this->needAll()) {

        $sResult = $this->sSpacer = uniqid('sylma');
      }
      else {

        $sResult = $this->sSpacer;
      }
    }
    else {

      $sResult = uniqid('sylma');
    }

    return $sResult;
  }

  /**
   * @uses \sylma\view\parser\Elemented::parseFromChild()
   */
  protected function createSpacer($sID) {

    $parser = $this->getParser()->getParent();
    return $parser->parseFromChild($this->createElement('span', '.', array('class' => "$sID spacer"), \Sylma::read('namespaces/html'), false));
  }

  protected function getAutoload() {

    return $this->readx('@js:autoload');
  }

  /**
   * Type check
   */
  protected function prepareParent(self $class) {

    return $class->addChild($this->getAlias(), $this->getExtend(), $this->getAutoload());
  }

  /**
   * @uses self::addChild of parent class
   */
  public function asArray() {

    $aResult = array();
    $bTemplate = $this->useTemplate();
    $bTemplateChild = $this->getRoot()->getMode() === self::TEMPLATE_MODE;

    if ($bTemplate || $bTemplateChild) {

      $this->isUsed(true);

      $this->loadExtend();

      $obj = $this->getParser()->getObject();

      $class = $bTemplateChild ? $obj : $obj->getClass();
      $aResult = $this->prepareParent($class);

      if (!$this->bAdded) {

        if (!$bTemplateChild) {

          $root = $this->getRoot();
          $sMode = $root->getMode();

          $root->setMode(self::TEMPLATE_MODE);
        }

        $this->template = $this->buildTemplate();

        if (!$bTemplateChild) {

          $root->setMode($sMode);
        }

        $this->bAdded = true;
      }
    }
    else {

      $aResult = parent::asArray();
    }

    return $aResult;
  }

  /**
   * @uses Handler::startSource() and stopSource()
   * @uses Handler::startObject() stopObject() and getObject()
   * @uses Handler::getParent()
   */
  protected function buildTemplate() {

    $window = $this->getWindow();
    $self = $window->createVariable('this');
    $source = $window->createVariable('item');

    $this->getElement()->setAttribute('id', $self->getProperty('id'));

    $this->setSource($source);
    $this->getParser()->startSource($source);
    $this->getParser()->startObject($this);

    $content = $window->toString($this->getElement());

    $this->getParser()->stopSource();
    $this->getParser()->stopObject();

    $result = $window->createFunction(array($source->getName()));
    $result->addContent($window->createReturn($content));

    return $result;
  }
}

