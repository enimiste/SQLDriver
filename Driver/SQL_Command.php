<?php

require_once 'SQL_Exceptions.php';
require_once 'SQL_Infra_Common.php';

/**
 * 
 *
 * @author NOUNI EL BACHIR
 */
abstract class SQL_Command_Builder {

    static $SELECT_COMMAND = 1;
    static $Update_COMMAND = 2;

    protected $table_prefix;

    function prefix_table($prefix) {
        $this->table_prefix = $prefix;
        return $this;
    }

    abstract function get();

    abstract function get_command_type();
    
    abstract function clear();
}

final class SQL_Column_Type{
    static $INTEGER = 1;
    static $STRING = 2;
    static $DATE = 3;
    static $FLOAT = 4;
    static $BOOLEAN = 5;
}
abstract class SQL_Select_Command_Builder extends SQL_Command_Builder {

    abstract function select($table_name, $field_name, $alias = FALSE);

    abstract function from($table_name, $alias = FALSE);

    abstract function join($right_table_name, $right_field_name, $op, $left_table_name, $left_field_name, $join_type = "left");

    abstract function where($table_name, $field_name, $op, $value);
    
    abstract function limit($limit);

    abstract function order_by($table_name, $field_name, $order = "ASC");
}

abstract class SQL_Update_Command_builder extends SQL_Command_Builder {
    abstract function update_table($table_name);
    
    abstract function column($field_name, $field_new_value, $type = SQL_Column_Type::STRING);
    
    abstract function where($table_name, $field_name, $op, $value);
}

abstract class SQL_Command_Executor {

    public function set_server_param(Common_Server_Param $params) {
        $this->save_server_param($params);
        return $this;
    }

    abstract function execute_query(SQL_Command_Builder $command);

    abstract protected function save_server_param(Common_Server_Param $params);
}

abstract class SQL_Result_Set implements Iterator {
    abstract function first_row();
}

abstract class SQL_NoQuery_Result {
    abstract function get_bool_value();
    abstract function get_int_value();
}



?>
