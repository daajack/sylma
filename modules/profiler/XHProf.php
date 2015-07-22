<?php

namespace sylma\modules\profiler;
use sylma\core;

/**
 * Require XHProf : https://php.net/manual/fr/book.xhprof.php
 */
class XHProf extends core\module\Domed {

  const ROOT = 'xhprof/';
  const DB_CONNECTION = 'test';

  protected $aResult = array();
  protected $iStack = 0;

  public function start() {

    $this->iStack++;

    if ($this->iStack > 1) {

      return;
    }
/*
ALTER TABLE `sandbox`.`php_method` ADD UNIQUE `fullname` (`fullname`) COMMENT '';

CREATE TABLE IF NOT EXISTS `sandbox`.`profiler_calls` (`source` varchar(100) NOT NULL,CONSTRAINT FOREIGN KEY (`source`) REFERENCES `php_method` (fullname), `target` varchar(100) NOT NULL,CONSTRAINT FOREIGN KEY (`target`) REFERENCES `php_method` (fullname), `ct` INT(6) NULL DEFAULT null, `wt` INT(6) NULL DEFAULT null, `cpu` INT(6) NULL DEFAULT null, `mu` INT(6) NULL DEFAULT null, `pmu` INT(6) NULL DEFAULT null) ENGINE=MyISAM;

ALTER TABLE `sandbox`.`profiler_calls` ADD UNIQUE `unique_index`(`source`, `target`);
 */

    \xhprof_enable(\XHPROF_FLAGS_NO_BUILTINS + \XHPROF_FLAGS_CPU + \XHPROF_FLAGS_MEMORY, array(
      'ignored_functions' => array(
        'sylma\modules\tester\Basic::evaluate',
        'sylma\modules\tester\Profiler::stopProfile',
        'sylma\modules\tester\Basic::{closure}',
        'sylma\modules\profiler\Manager::stop',
      ),
    )); //\XHPROF_FLAGS_CPU + \XHPROF_FLAGS_MEMORY
  }

  public function stop($bForce = false) {

    if ($this->iStack === 1) {

      $this->aResult = array_merge($this->aResult, xhprof_disable());
    }
    
    if ($bForce) {

      $this->iStack = 0;
    }
    else {

      $this->iStack--;
    }
  }

  public function save() {

    if (!$this->aResult) {

      dsp('No profile result');
      //$this->launchException('Cannot save profile, no result found');
      return;
    }

    require_once(self::ROOT . '/utils/xhprof_lib.php');
    require_once(self::ROOT . '/utils/xhprof_runs.php');

    $xhprof_runs = new \XHProfRuns_Default();
//dsp(\Sylma::getSettings());

    $db = $this->getManager(self::DB_MANAGER)->getConnection(self::DB_CONNECTION)->getDatabase();
    $sTable = 'profiler_calls';

    $sql = "INSERT INTO `$sTable`"
           //. "(source, target, ct, wt, cpu, mu, pmu) VALUES "
           . " (source, target, ct, wt) VALUES"
           //. "(:source, :target, :ct, :wt, :cpu, :mu, :pmu)"
           . " (:source, :target, :ct, :wt)"
           . " ON DUPLICATE KEY UPDATE"
           . " ct=VALUES(ct) + ct, wt = VALUES(wt) + wt";

//dsp($sql);
    $sth = $db->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
//$test = microtime(true);
    foreach ($this->aResult as $sKey => $aValues) {

      if (!strpos($sKey, '==>')) {

        continue;
      }

      list($sSource, $sTarget) = explode('==>', $sKey);

      if ($sSource{0} === '@') {

        $sSource = $sTarget;
      }

      if (strpos($sSource, '@')) {

        $sSource = substr($sSource, 0, -2);
      }

      if (strpos($sTarget, '@')) {

        $sTarget = substr($sTarget, 0, -2);
      }

      if ($sTarget{0} === '?'
          || strpos($sSource, '{closure}') || strpos($sTarget, '{closure}')
          || !strpos($sSource, '::') || !strpos($sTarget, '::')
          || strpos($sSource, '.') || strpos($sTarget, '.')
          || strpos($sSource, '()') || strpos($sTarget, '()')) {

        //continue;
      }

try {
      $sth->execute(array(
        ':source' => $sSource,
        ':target' => $sTarget,
        ':ct' => $aValues['ct'],
        ':wt' => $aValues['wt'],
        //':cpu' => $aValues['cpu'],
        //':mu' => $aValues['mu'],
        //':pmu' => $aValues['pmu'],
      ));
}
catch (\Exception $e) {

  $this->launchException($e->getMessage(), get_defined_vars());
}

    }
//dsp(round(microtime(true) - $test, 2) . ' seconds elapsed');
//dsp(count($this->aResult) . ' calls added');
    //$run_id = $xhprof_runs->save_run($this->aResult, "xhprof_testing");
  }
}
