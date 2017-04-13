<?php

namespace sylma\storage\sql\crud;
use sylma\core, sylma\storage\sql;

class Where extends sql\cached\Where
{
  protected $search = null;
  protected $searches = array();
  
  public function add($val1, $op, $val2, $sDefault = null, $global = false) {

    if ($val1 === '`[collection]`' && $val2)
    {
      $this->search = $val2;
    }
    else if ($val2) {

      $sql = $this->db;
      $vals = array();

      foreach ($val2 as $group) {

        $logic = 'or';

        if (isset($group['logic'])) {

          $logic = $group['logic'];
        }

        if ($logic !== 'or' && $logic !== 'and') {

          $this->launchException('Illegal logic');
        }

        if ($op === 'array') {

          $op = $group['operator'] === '=' ? 'in' : 'not in';
          
          if (isset($group['children'])) {
            
            $vals = array_filter($group['children']);
          }
          else {
            
            $vals = array();
          }
          
          if ($vals) {

            $this->addStatic("$val1 $op ( " . implode(", ", $vals) . ')');
          }
        }
        else if ($op === 'search') {

          $searches = array();

          foreach ($group['children'] as $val) {

            if ($val['value']) {

              if ($val['operator'] !== '=') {

                $logic = 'and';
              }

              $vals[] = $this->buildSearch($val1, $val['value'], $val['operator']);
            }
          }

          if ($vals) {
            
            if ($global)
            {
              $this->addSearch('(' . implode(" $logic ", $vals) . ')');
            }
            else
            {
              $this->addStatic('(' . implode(" $logic ", $vals) . ')');
            }
            
          }
        }
        else {

          $vals = array();
          $ops = array(
            '=' => '=',
            '!' => '!=',
            '<' => '<=',
            '>' => '>=',
          );

          $first = true;
          
          foreach ($group['children'] as $val) {

            if ($val['value']) {

              if (!isset($ops[$val['operator']])) {

                $this->launchException('Illegal operator');
              }

              $op = $ops[$val['operator']];

              if ($op !== '=') {

                $logic = 'and';
              }

              $start = $first ? '' : " $logic ";
              $first = false;
              $val = $sql->escape($val['value']);
              //$content = $isDate ? "DATE($val])" : $val;
              $vals[] = $start . $val1 . ' ' . $op . ' ' . $val;
            }
          }
          
          if ($vals) {

            $this->addStatic('(' . implode('', $vals) . ')');
          }
        }
      }
    }
    
    if (!$global && $this->search && $op === 'search')
    {
      $this->add($val1, $op, array(
        array(
          'logic' => 'or',
          'children' => array(
            array(
              'value' => $this->search,
              'operator' => '='
            )
          )
        )
      ), null, true);
    }
  }
  
  protected function addSearch($val) {
    
    $this->searches[] = $val;
  }

  protected function buildSearch($val1, $val2, $operator) {

    $sql = $this->db;

    $val1 .= ' COLLATE ' . $this->getCollation();

    $op = $operator === '=' ? 'like' : 'not like';
    $val2 = "%$val2%";

    $val2 = $sql->escape($val2);

    return "$val1 $op $val2";
  }
  
  public function prepare() {
    
    if ($this->search)
    {
      $this->addStatic('(' . implode(' OR ', $this->searches) . ')');
    }
  }
}
