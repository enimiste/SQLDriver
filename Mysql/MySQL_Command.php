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
    private $limit;

    function __construct() {
        $this->clear();
    }
    
    function select($table_name, $field_name, $alias = FALSE) {
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
        return parent::$SELECT_COMMAND;
    }

    public function limit($limit) {
        if (!is_numeric($limit))
            throw new SQL_Exception("The limit should be an integer value.");
        $this->limit = $limit;
        
        return $this;
    }

    public function clear() {
        $this->table_prefix = "";
        $this->select_fields = array();
        $this->from = "";
        $this->joins = array();
        $this->wheres = array();
        $this->order_by = "";
        $this->limit = FALSE;
    }

}

class Mysql_Command_Executor extends SQL_Command_Executor {

    private $params;

    protected function save_server_param(Common_Server_Param $params) {
        if (is_null($params))
            throw new SQL_Exception("Invalide Common Server Param");
        $this->params = $params;
    }

    public function execute_query(SQL_Command_Builder $command) {
        $link_mysql = mysql_connect($this->params->getHost_name(), $this->params->getUser_name(), $this->params->getPassword()) or die(mysql_error());
        mysql_select_db($this->params->getDb_name()) or die(mysql_error());
        
        if ($command->get_command_type() == SQL_Command_Builder::$SELECT_COMMAND){
            $result = mysql_query($command->get()) or die(mysql_error());
            $res = new Mysql_Result_Set($result);
        } else {
            $result = mysql_query($command->get()) or die(mysql_error());
            $res = new MySql_NoQuery_Result(mysql_affected_rows());
        }
        mysql_close($link_mysql);
        return $res;
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

    function first_row() {
        $this->rewind();
        if ($this->valid())
            return $this->current;
        throw new SQL_Exception("The result set is empty.");
    }

}

class Mysql_Update_Command_Builder extends SQL_Update_Command_builder {

    private $table_name;
    private $columns;
    private $wheres;

    function __construct() {
        $this->clear();
    }

    public function update_table($table_name) {
        $this->table_name = $this->table_prefix . $table_name;

        return $this;
    }

    public function column($field_name, $field_new_value, $type = SQL_Column_Type::STRING) {
        if (array_key_exists($field_name, $this->columns))
            throw new SQL_Exception("Column specified in the update command already exist.");
        $this->valdiate_type($field_new_value, $type);
        $this->columns[$field_name] = $field_new_value;

        return $this;
    }

    public function where($table_name, $field_name, $op, $value) {
        $table_name = $this->table_prefix . $table_name;
        $this->wheres[] = $table_name . "." . $field_name . " " . $op . " " . $value;

        return $this;
    }

    public function get() {
        $sql = "UPDATE " . $this->table_name . " SET ";
        $sql .= implode(", ", array_map(function($key, $item) {
                            return $key . "=" . $item;
                        }, array_keys($this->columns), array_values($this->columns)));
        if (!empty($this->wheres))
            $sql .= " WHERE " . implode(" AND ", $this->wheres) . " ";
        return $sql;
    }

    public function get_command_type() {
        return parent::$Update_COMMAND;
    }

    //***************** Private functions
    private function valdiate_type($value, $type) {
        switch ($type) {
            case SQL_Column_Type::$INTEGER:
                if (!is_numeric($value))
                    throw new Exception("Invalid value in a column of type INTEGER.");
                try {
                    $value = intval($value);
                } catch (Exception $ex) {
                    throw new Exception("Invalid value in a column of type INTEGER.");
                }
                break;
            case SQL_Column_Type::$FLOAT:
                if (!is_numeric($value))
                    throw new Exception("Invalid value in a column of type FLOAT.");

                break;
            case SQL_Column_Type::$DATE:
                if (!is_a($value, "DateTime"))
                    try {
                        $value = date_create_from_format($value);
                    } catch (Exception $ex) {
                        throw new Exception("Invalid value in a column of type INTEGER.");
                    }

                break;
            case SQL_Column_Type::$STRING:
                if (!is_string($value))
                    throw new Exception("Invalid value in a column of type STRING.");

                break;
            case SQL_Column_Type::$BOOLEAN:
                if (!is_bool($value))
                    throw new Exception("Invalid value in a column of type BOOLEAN.");

                break;
            default:
                break;
        }
    }

    public function clear() {
        $this->table_name = "";
        $this->columns = array();
        $this->wheres = array();
    }

}

class MySql_NoQuery_Result extends SQL_NoQuery_Result {
    private $result;
    
    function __construct($result) {
        $this->result = $result;
    }

    public function get_bool_value() {
        return $this->result;
    }

    public function get_int_value() {
        if(is_numeric($this->result)) return intval($this->result);
    }    
}

?>
