<?php

namespace sylma\storage\sql\runtime;
use sylma\core;

class Query extends core\module\Domed
{
  public $content = '';
  protected $columns = array();
  
  public function __construct($table)
  {
    $this->table = $table;
  }
  
  public function addElements()
  {
    $this->columns = func_get_args();
  }
  
  public function render()
  {
    $query = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . $this->table;
    $connection = $this->getManager('mysql')->getConnection('test');
    
    $rows = $connection->query($query);
    
    return $rows;
  }
}
