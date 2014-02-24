<?php

$driver = realpath(__DIR__ . "/../" . "Driver/SQL_Command.php");
require_once $driver;

/**
*
* @author NOUNI EL BACHIR
*/
class Mysql_Select_Command_Builder extends SQL_Select_Command_Builder {

    private $select_fields;
    private $from;
    private $joins;
    private $wheres;
    private $order_by;
    private $command_type;

    function __construct() {
        $this->table_prefix = "";
        $this->select_fields = array();
        $this->from = "";
        $this->joins = array();
        $this->wheres = array();
        $this->order_by = "";
        $this->command_type = FALSE;
    }

    function select($table_name, $field_name, $alias = FALSE) {
        $this->command_type = SQL_Command_Builder::$SELECT_COMMAND;
        $table_name = $this->table_prefix . $table_name;
        if ($alias)
            $field_name = $field_name . " AS " . $alias;
        $this->select_fields[] = $table_name . "." . $field_name;
        return $this;
    }

    public function join($right_table_name, $right_field_name, $op, $left_table_name, $left_field_name, $join_type = "left") {
        $left_table_name = $this->table_prefix . $left_table_name;
        $right_table_name = $this->table_prefix . $right_table_name;

        $this->joins[] = $right_table_name . " ON " . $right_table_name . "." . $right_field_name . " " .
                $op . " " . $left_table_name . "." . $left_field_name;
        return $this;
    }

    public function from($table_name, $alias = FALSE) {
        $this->from = $this->table_prefix . $table_name;
        if ($alias)
            $this->from = $this->from . " AS " . $alias;
        return $this;
    }

    function where($table_name, $field_name, $op, $value) {
        $table_name = $this->table_prefix . $table_name;
        $this->wheres[] = $table_name . "." . $field_name . " " . $op . " " . $value;
        return $this;
    }

    function order_by($table_name, $field_name, $order = "ASC") {
        $table_name = $this->table_prefix . $table_name;
        $this->order_by = " order by " . $table_name . "." . $field_name . " " . $order;
        return $this;
    }

    public function limit($limit) {
        if(!is_numeric($limit))
            throw new SQL_Exception("The limit should be an integer value.");
        $this->limit = $limit;
    }

    public function get() {
        $sql = "SELECT " . implode(", ", $this->select_fields) . " ";
        $sql .= " FROM " . $this->from . " ";
        if (!empty($this->joins))
            $sql .= " LEFT JOIN " . implode(" LEFT JOIN ", $this->joins) . " ";
        if (!empty($this->wheres))
            $sql .= " WHERE " . implode(" AND ", $this->wheres) . " ";
        if (!empty($this->order_by))
            $sql .= $this->order_by;
        if (!empty($this->limit))
            $sql .= " LIMIT " . $this->limit;
        return $sql;
    }

    public function get_command_type() {
        return $this->command_type;
    }

}

class Mysql_Command_Executor extends SQL_Command_Executor {

    private $params;

    protected function save_server_param(Common_Server_Param $params) {
        if (is_null($params))
            throw new SQL_Exception("Invalide Common Server Param");
        $this->params = $params;
    }

    public function execute_query(Mysql_Command_Builder $command) {
        mysql_connect($this->params->getHost_name(), $this->params->getUser_name(), $this->params->getPassword()) or die(mysql_error());
        mysql_select_db($this->params->getDb_name()) or die(mysql_error());
        $result = mysql_query($command->get()) or die(mysql_error());
        if ($command->get_command_type() == SQL_Command_Builder::$SELECT_COMMAND)
            return new Mysql_Result_Set($result);
        else
            NULL;
    }

}

class Mysql_Result_Set extends SQL_Result_Set {

    private $result;
    private $current;
    private $key;

    function __construct($result) {
        $this->result = $result;
        $this->current = NULL;
        $this->key = -1;
    }

    public function current() {
        return $this->current;
    }

    public function key() {
        return $this->key;
    }

    public function next() {
        $this->current = mysql_fetch_row($this->result);
        $this->key++;
    }

    public function rewind() {
        $this->key = 0;
        mysql_data_seek($this->result, 0);
        $this->current = mysql_fetch_row($this->result);
        $this->key++;
    }

    public function valid() {
        return $this->current;
    }

    function first_row(){
        $this->rewind();
        if($this->valid()) return $this->current;
        throw new SQL_Exception("The result set is empty.");
    }

}

class Mysql_Update_Command_Builder extends SQL_Update_Command_builder {

    public function get() {
        
    }

    public function get_command_type() {
        
    }

}

class MySql_NoQuery_Result extends SQL_NoQuery_Result {
    
}

?>
