<?php

$driver = realpath(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "Driver" . DIRECTORY_SEPARATOR . "SQL_Command.php");
require_once $driver;

/**
 *
 * @author NOUNI EL BACHIR
 */
class Pdo_Select_Command_Builder extends SQL_Select_Command_Builder
{

    private $select_fields;
    private $from;
    private $joins;
    private $wheres;
    private $order_by;
    private $limit;
    private $offset;

    function __construct()
    {
        $this->clear();
    }

    function select($table_name, $field_name, $alias = FALSE)
    {
        $table_name = $this->table_prefix . $table_name;
        if ($alias)
            $field_name = $field_name . " AS " . $alias;
        $this->select_fields[] = $table_name . "." . $field_name;
        return $this;
    }

    public function join($right_table_name, $right_field_name, $op, $left_table_name, $left_field_name, $join_type = "left", $right_table_name_alias = "")
    {
        $left_table_name = $this->table_prefix . $left_table_name;
        $right_table_name = $this->table_prefix . $right_table_name;

        if (empty($right_table_name_alias))
            $this->joins[] = $join_type . ' JOIN ' . $right_table_name . " ON " . $right_table_name . "." . $right_field_name . " " .
                $op . " " . $left_table_name . "." . $left_field_name;
        else
            $this->joins[] = $join_type . ' JOIN ' . $right_table_name . " AS " . $right_table_name_alias . " ON " . $right_table_name_alias . "." . $right_field_name . " " .
                $op . " " . $left_table_name . "." . $left_field_name;
        return $this;
    }

    public function from($table_name, $alias = FALSE)
    {
        $this->from = $this->table_prefix . $table_name;
        if ($alias)
            $this->from = $this->from . " AS " . $alias;
        return $this;
    }

    function where($table_name, $field_name, $op, $value, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;
        switch ($type) {
            case SQL_Column_Type::$DATE:
                if (is_a($value, "DateTime"))
                    $value = $value->format("Y-m-d H:i:s");
                $this->wheres[] = $table_name . "." . $field_name . " " . $op . " '" . $this->sanitizeDate($value) . "' ";
                break;
            case SQL_Column_Type::$STRING:
                $this->wheres[] = $table_name . "." . $field_name . " " . $op . " '" . $this->sanitizeString($value) . "' ";
                break;
            case SQL_Column_Type::$BOOLEAN:
                $this->wheres[] = $table_name . "." . $field_name . " " . $op . ' ' . intval($value);
                break;
            case SQL_Column_Type::$INTEGER:
                $this->wheres[] = $table_name . "." . $field_name . " " . $op . ' ' . intval($value);
                break;
            case SQL_Column_Type::$SQLEXPRESSION:
                $this->wheres[] = $table_name . "." . $field_name . " " . $op . ' ' . $value;
                break;
            default:
                $this->wheres[] = $table_name . "." . $field_name . " " . $op . ' ' . $value;
                break;
        }
        return $this;
    }

    public function where_is($table_name, $field_name, $value)
    {
        $table_name = $this->table_prefix . $table_name;
        $this->wheres[] = $table_name . "." . $field_name . " IS " . $value . ' ';
        return $this;
    }

    function order_by($table_name, $field_name, $order = "ASC")
    {
        $table_name = $this->table_prefix . $table_name;
        $this->order_by = " ORDER BY " . $table_name . "." . $field_name . " " . $order;
        return $this;
    }

    public function get()
    {
        $sql = "SELECT " . implode(", ", $this->select_fields) . " ";
        $sql .= " FROM " . $this->from . " ";
        if (!empty($this->joins))
            $sql .= "  " . implode("  ", $this->joins) . " ";
        if (!empty($this->wheres))
            $sql .= " WHERE " . implode(" AND ", $this->wheres) . " ";
        if (!empty($this->order_by))
            $sql .= $this->order_by;
        if (!empty($this->limit))
            $sql .= " LIMIT " . $this->offset . ", " . $this->limit;

        return $sql;
    }

    public function get_command_type()
    {
        return parent::$SELECT_COMMAND;
    }

    public function limit($limit, $offset = 0)
    {
        if (!is_numeric($limit))
            throw new SQL_Exception(__CLASS__ . '::' . __FUNCTION__ . " The limit should be an integer value.");
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function clear()
    {
        $this->table_prefix = "";
        $this->select_fields = array();
        $this->from = "";
        $this->joins = array();
        $this->wheres = array();
        $this->order_by = "";
        $this->limit = FALSE;
        $this->offset = FALSE;
    }

    public function min($table_name, $field_name, $alias = FALSE)
    {
        $table_name = $this->table_prefix . $table_name;

        $field_name = ' MIN(' . $table_name . '.' . $field_name . ') ';
        if ($alias)
            $field_name = $field_name . ' AS ' . $alias;
        $this->select_fields[] = $field_name;
        return $this;
    }

    public function avg($table_name, $field_name, $alias = FALSE)
    {
        $table_name = $this->table_prefix . $table_name;

        $field_name = ' AVG(' . $table_name . '.' . $field_name . ') ';
        if ($alias)
            $field_name = $field_name . ' AS ' . $alias;
        $this->select_fields[] = $field_name;
        return $this;
    }

    public function max($table_name, $field_name, $alias = FALSE)
    {
        $table_name = $this->table_prefix . $table_name;

        $field_name = ' MAX(' . $table_name . '.' . $field_name . ') ';
        if ($alias)
            $field_name = $field_name . ' AS ' . $alias;
        $this->select_fields[] = $field_name;
        return $this;
    }

    public function where_in($table_name, $field_name, array $values, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;

        if (in_array($type, array(SQL_Column_Type::$STRING, SQL_Column_Type::$DATE)))
            $values = array_map(function ($item) {
                return "'" . $item . "'";
            }, $values);
        $this->wheres[] = $table_name . "." . $field_name . " IN (" . implode(', ', $values) . ') ';
        return $this;
    }

    public function where_native($where)
    {
        $this->wheres[] = $where;
        return $this;
    }

    public function select_count($table_name, $field_name, $alias = FALSE)
    {
        $table_name = $this->table_prefix . $table_name;
        $field_name = 'COUNT(' . $table_name . "." . $field_name . ') ';
        if ($alias)
            $field_name = $field_name . " AS " . $alias;
        $this->select_fields[] = $field_name;
        return $this;
    }

    public function select_distinct($table_name, $field_name, $alias = FALSE)
    {
        $table_name = $this->table_prefix . $table_name;
        if ($alias)
            $field_name = $field_name . " AS " . $alias;
        $this->select_fields[] = ' DISTINCT ' . $table_name . "." . $field_name;
        return $this;
    }

    public function where_not_in($table_name, $field_name, array $values, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;

        if (in_array($type, array(SQL_Column_Type::$STRING, SQL_Column_Type::$DATE)))
            $values = array_map(function ($item) {
                return "'" . $item . "'";
            }, $values);
        $this->wheres[] = $table_name . "." . $field_name . " NOT IN (" . implode(', ', $values) . ') ';
        return $this;
    }

    public function clean_select()
    {
        $this->select_fields = array();
    }

}

class Pdo_Command_Executor extends SQL_Command_Executor
{

    private $params;

    /**
     *
     * @var \PDO
     */
    protected $_pdo;

    public function __construct()
    {
        $this->_pdo = NULL;
    }

    protected function save_server_param(Common_Server_Param $params)
    {
        if (is_null($params))
            throw new SQL_Exception(__CLASS__ . '::' . __FUNCTION__ . " Invalide Common Server Param");
        $this->params = $params;
        return $this;
    }

    private function die_error($error)
    {
        if (is_array($error))
            throw new SQL_Exception(__CLASS__ . '::' . __FUNCTION__ . ' ' . implode(', ', $error));
        else
            throw new SQL_Exception(__CLASS__ . '::' . __FUNCTION__ . ' ' . $error);
    }

    public function execute_query($command, $type = 2)
    {

        try {
            $pdo = $this->get_pdo();
            if ($this->is_transcation_active())
                $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
        } catch (PDOException $e) {
            throw new SQL_Exception(__CLASS__ . '::' . __FUNCTION__ . ' Connexion échouée : ' . $e->getMessage());
        }
        if ($this->is_transcation_active())
            $pdo->beginTransaction();
        try {
            $return = NULL;
            if (is_array($command)) {
                $res_arr = new Pdo_NoQuery_Result_Set();
                foreach ($command as $value) {
                    if ($value->get_command_type() == SQL_Command_Builder::$SELECT_COMMAND) {
                        $stt = $pdo->prepare($value, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
                        $result = $stt->execute();
                        if (!$result)
                            $this->die_error($pdo->errorInfo());
                        $res = new Pdo_Result_Set($result);
                    } else if ($command->get_command_type() == SQL_Command_Builder::$PROCEDURE_COMMAND) {
                        $in_param = $command->getParamsByDirection(SQL_Procedure_Command_Builder::PD_IN);
                        $call = 'CALL ' . $command->getProcedureName() . '(';
                        foreach ($in_param as $name => $param) {
                            $call .= ':' . $name . ',';
                        }
                        $call = rtrim($call, ',') . ')';

                        $stt = $pdo->prepare($call);

                        foreach ($in_param as $name => $param) {
                            switch ($param['type']) {
                                case 'string':
                                    $pdo_type = PDO::PARAM_STR;
                                case 'bool':
                                    $pdo_type = PDO::PARAM_BOOL;
                                case 'integer':
                                    $pdo_type = PDO::PARAM_INT;
                                default:
                                    $pdo_type = PDO::PARAM_STR;
                            }
                            $stt->bindParam(':' . $name, $param['value'], $pdo_type);
                        }
                        $result = $stt->execute();
                        if (!$result)
                            $this->die_error($pdo->errorInfo());
                        $res = new Pdo_Result_Set($stt);
                    } else {
                        $result = $pdo->exec($value->get());
                        if (!$result)
                            $this->die_error($pdo->errorInfo());
                        $res = new Pdo_NoQuery_Result($result);
                    }
                    $res_arr->push($res);
                }
                $return = $res_arr;
            } else if (is_string($command)) {
                if ($type == SQL_Command_Builder::$SELECT_COMMAND) {
                    $stt = $pdo->prepare($command, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
                    $result = $stt->execute();
                    if (!$result)
                        $this->die_error($pdo->errorInfo());
                    $res = new Pdo_Result_Set($stt);
                } else {
                    $result = $pdo->exec($command);
                    if (!$result)
                        $this->die_error($pdo->errorInfo());
                    $res = new Pdo_NoQuery_Result($result);
                }
                $return = $res;
            } else if ($command instanceof SQL_Command_Builder) {
                if ($command->get_command_type() == SQL_Command_Builder::$SELECT_COMMAND) {
                    $stt = $pdo->prepare($command->get(), array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
                    $result = $stt->execute();
                    if (!$result)
                        $this->die_error($pdo->errorInfo());
                    $res = new Pdo_Result_Set($stt);
                } else if ($command->get_command_type() == SQL_Command_Builder::$PROCEDURE_COMMAND) {
                    $in_param = $command->getParamsByDirection(SQL_Procedure_Command_Builder::PD_IN);
                    $call = 'CALL ' . $command->getProcedureName() . '(';
                    foreach ($in_param as $name => $param) {
                        $call .= ':' . $name . ',';
                    }
                    $call = rtrim($call, ',') . ');';

                    $stt = $pdo->prepare($call);

                    foreach ($in_param as $name => $param) {
                        switch ($param['type']) {
                            case 'string':
                                $pdo_type = PDO::PARAM_STR;
                            case 'bool':
                                $pdo_type = PDO::PARAM_BOOL;
                            case 'integer':
                                $pdo_type = PDO::PARAM_INT;
                            default:
                                $pdo_type = PDO::PARAM_STR;
                        }
                        $stt->bindParam(':' . $name, $param['value'], $pdo_type);
                    }
                    $result = $stt->execute();
                    if (!$result)
                        $this->die_error($pdo->errorInfo());
                    $res = new Pdo_Result_Set($stt);
                } else {
                    $result = $pdo->exec($command->get());
                    if (!$result)
                        $this->die_error($pdo->errorInfo());
                    if ($command->get_command_type() == SQL_Command_Builder::$INSERT_COMMAND)
                        $res = new Pdo_NoQuery_Result($pdo->lastInsertId());
                    else
                        $res = new Pdo_NoQuery_Result($result);
                }
                $return = $res;
            }
            if ($this->is_transcation_active())
                $pdo->commit();
            return $return;
        } catch (Exception $exc) {
            if ($this->is_transcation_active())
                $pdo->rollBack();
            throw $exc;
        }
    }

    public function close_connexion()
    {
        unset($this->_pdo);
    }

    public function open_connexion()
    {
        if (!isset($this->_pdo)) {
            $dsn = 'mysql:dbname=' . $this->params->getDb_name() . ';host=' . $this->params->getHost_name();
            $user = $this->params->getUser_name();
            $password = $this->params->getPassword();

            $this->_pdo = new PDO($dsn, $user, $password);

            $this->_pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    protected function get_pdo()
    {
        if (!isset($this->_pdo)) {
            $this->open_connexion();
        }
        return $this->_pdo;
    }

}

class Pdo_Result_Set extends SQL_Result_Set
{

    private $result;
    private $key;

    function __construct(PDOStatement $result)
    {
        $this->result = $result->fetchAll(PDO::FETCH_ASSOC);
        $result->closeCursor();
        $this->key = 0;
    }

    public function current()
    {
        return $this->result[$this->key()];
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        $this->key++;
    }

    public function rewind()
    {
        $this->key = 0;
    }

    public function valid()
    {
        return is_array($this->result) AND count($this->result) > $this->key();
    }

    function first_row()
    {
        if (is_array($this->result) AND count($this->result) >= 1) {
            return $this->result[0];
        }
        throw new SQL_Exception(__CLASS__ . '::' . __FUNCTION__ . " The result set is empty.");
    }

    public function count()
    {
        return is_array($this->result) ? count($this->result) : 0;
    }

    public function free_result()
    {
        //unset($this->result);
    }

    public function as_array()
    {
        return is_array($this->result) ? array_map(function ($item) {
            return $item;
        }, $this->result) : array();
    }

}

class Pdo_Update_Command_Builder extends SQL_Update_Command_Builder
{

    protected $table_name;
    protected $columns;
    protected $wheres;
    protected $wheres_is;

    function __construct($table_name = null, $columns = array(), $wheres = array())
    {
        $this->clear();
        $this->table_name = $table_name;
        foreach ($columns as $value) {
            $this->column($value['field_name'], $value['field_new_value'], $value['type']);
        }
        foreach ($wheres as $value) {
            $this->where($value['table_name'], $value['field_name'], $value['op'], $value['value'], $value['type']);
        }
    }

    public function update_table($table_name)
    {
        $this->table_name = $this->table_prefix . $table_name;
        return $this;
    }

    public function column($field_name, $field_new_value, $type = 2)
    {
        //$this->valdiate_type($field_new_value, $type);

        switch ($type) {
            case SQL_Column_Type::$DATE:
                if (is_a($field_new_value, "DateTime"))
                    $field_new_value = $field_new_value->format("Y-m-d H:i:s");
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => "'" . $this->sanitizeDate($field_new_value) . "'",
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$STRING:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => "'" . $this->sanitizeString($field_new_value) . "'",
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$BOOLEAN:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => intval($field_new_value),
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$INTEGER:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => intval($field_new_value),
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$SQLEXPRESSION:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => $field_new_value,
                    'type' => $type
                );
                break;
            default:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => $field_new_value,
                    'type' => $type
                );
                break;
        }

        return $this;
    }

    public function where($table_name, $field_name, $op, $value, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;
        switch ($type) {
            case SQL_Column_Type::$DATE:
                if (is_a($value, "DateTime"))
                    $value = $value->format("Y-m-d H:i:s");
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => "'" . $this->sanitizeDate($value) . "'",
                    'type' => $type,
                );
                break;
            case SQL_Column_Type::$STRING:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => "'" . $this->sanitizeString($value) . "'",
                    'type' => $type,
                );
                break;
            case SQL_Column_Type::$BOOLEAN:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => intval($value),
                    'type' => $type,
                );
                break;
            case SQL_Column_Type::$INTEGER:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => intval($value),
                    'type' => $type,
                );
                break;
            case SQL_Column_Type::$SQLEXPRESSION:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => $value,
                    'type' => $type,
                );
                break;
            default:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => $value,
                    'type' => $type,
                );
                break;
        }

        return $this;
    }

    public function where_is($table_name, $field_name, $value)
    {
        $table_name = $this->table_prefix . $table_name;
        $this->wheres_is[] = array(
            'tablename' => $table_name,
            'fieldname' => $field_name,
            'value' => $value
        );
    }

    public function get()
    {
        return $this->get_query($this->wheres, $this->wheres_is, $this->columns);
    }

    protected function get_query($wheres, $wheres_is, $columns)
    {
        $sql = "UPDATE " . $this->table_name . " SET ";
        $sql .= $this->builder_columns_sql($columns);
        if (!empty($wheres))
            $sql .= $this->build_where_sql($wheres, $wheres_is);

        return $sql;
    }

    protected function build_where_sql($wheres, $wheres_is)
    {
        return " WHERE " . implode(" AND ", array_map(function ($item) {

            return $item['table_name'] . "." . $item['field_name'] . " " . $item['op'] . ' ' . $item['value'];
        }, $wheres)) . " " . implode(" AND ", array_map(function ($item) {

            return $item['table_name'] . "." . $item['field_name'] . " IS " . $item['value'];
        }, $wheres_is)) . " ";
    }

    protected function builder_columns_sql($columns)
    {
        return implode(", ", array_map(function ($item) {
            return $item['field_name'] . "=" . $item['field_new_value'];
        }, $columns));
    }

    public function get_command_type()
    {
        return parent::$UPDATE_COMMAND;
    }

    public function clear()
    {
        $this->table_name = "";
        $this->columns = array();
        $this->wheres = array();
        $this->wheres_is = array();
    }

    public function where_in($table_name, $field_name, array $values, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;

        if (in_array($type, array(SQL_Column_Type::$STRING, SQL_Column_Type::$DATE)))
            $values = array_map(function ($item) {
                return "'" . $item . "'";
            }, $values);
        $this->wheres[] = $table_name . "." . $field_name . " IN (" . implode(', ', $values) . ') ';
        return $this;
    }

    public function where_native($where)
    {
        $this->wheres[] = $where;
        return $this;
    }

    public function where_not_in($table_name, $field_name, array $values, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;

        if (in_array($type, array(SQL_Column_Type::$STRING, SQL_Column_Type::$DATE)))
            $values = array_map(function ($item) {
                return "'" . $item . "'";
            }, $values);
        $this->wheres[] = $table_name . "." . $field_name . " NOT IN (" . implode(', ', $values) . ') ';
        return $this;
    }

}

class Pdo_NoQuery_Result extends SQL_NoQuery_Result
{

    private $result;

    function __construct($result)
    {
        $this->result = $result;
    }

    public function get_bool_value()
    {
        return $this->result;
    }

    public function get_int_value()
    {
        return intval($this->result);
    }

}

class Pdo_NoQuery_Result_Set extends SQL_NoQuery_Result_Set
{

    private $set;

    function __construct()
    {
        $this->set = array();
    }

    public function push(\SQL_NoQuery_Result $result)
    {
        $this->set[] = $result;
    }

    public function get_bool_and()
    {
        return array_reduce($this->set, function ($res, $item) {
            return $res && $item;
        }, TRUE);
    }

    public function get_bool_or()
    {
        return array_reduce($this->set, function ($res, $item) {
            return $res || $item;
        }, TRUE);
    }

}

class Pdo_Insert_Command_Builder extends SQL_Insert_Command_Builder
{

    protected $table_name;
    protected $columns;

    function __construct($table_name = null, $columns = array())
    {
        $this->clear();
        $this->table_name = $table_name;
        foreach ($columns as $value) {
            $this->column($value['field_name'], $value['field_new_value'], $value['type']);
        }
    }

    public function into_table($table_name)
    {
        $this->table_name = $this->table_prefix . $table_name;
        return $this;
    }

    public function column($field_name, $field_new_value, $type = 2)
    {
        //$this->valdiate_type($field_new_value, $type);

        switch ($type) {
            case SQL_Column_Type::$DATE:
                if (is_a($field_new_value, "DateTime"))
                    $field_new_value = $field_new_value->format("Y-m-d H:i:s");
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => "'" . $this->sanitizeDate($field_new_value) . "'",
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$STRING:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => "'" . $this->sanitizeString($field_new_value) . "'",
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$BOOLEAN:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => intval($field_new_value),
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$INTEGER:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => intval($field_new_value),
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$FLOAT:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => floatval($field_new_value),
                    'type' => $type
                );
                break;
            case SQL_Column_Type::$SQLEXPRESSION:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => $field_new_value,
                    'type' => $type
                );
                break;
            default:
                $this->columns[] = array(
                    'field_name' => $field_name,
                    'field_new_value' => $field_new_value,
                    'type' => $type
                );
                break;
        }

        return $this;
    }

    public function get()
    {
        $insert_sql = 'INSERT INTO ' . $this->table_name;
        $cols = array();
        $values = array();
        $i = 0;
        foreach ($this->columns as $column) {
            $cols[$i] = $column['field_name'];
            $values[$i] = $column['field_new_value'];
            $i++;
        }
        $insert_sql .= '(' . implode(', ', $cols) . ') VALUES(' . implode(', ', $values) . ') ';
        return $insert_sql;
    }

    public function get_command_type()
    {
        return parent::$INSERT_COMMAND;
    }

    public function clear()
    {
        $this->table_name = "";
        $this->columns = array();
    }

}

class Pdo_DataBaseBackup extends DataBaseBackup
{

    protected function createSql(\DataBaseBackup_Table $table, $drop_if_exists = FALSE)
    {
        $cmd_exec = new Pdo_Command_Executor();
        $cmd_exec->set_server_param($this->getDbServer());

        try {
            if ($table->isTable()) {
                //Structure de la table
                $r = $cmd_exec->execute_query('show create table ' . $table->getName(), SQL_Command_Builder::$SELECT_COMMAND);
                $arr = $r->first_row();
                $cs = $arr['Create Table'];

                //Les triggers s'ils existent
                $r = $cmd_exec->execute_query('SHOW TRIGGERS WHERE `Table` LIKE "' . $table->getName() . '"', SQL_Command_Builder::$SELECT_COMMAND);
                if (count($r) > 0) {
                    foreach ($r as $trigger) {
                        $name = $trigger['Trigger'];
                        $tr = $cmd_exec->execute_query('SHOW CREATE TRIGGER `' . $name . '`', SQL_Command_Builder::$SELECT_COMMAND);
                        if (count($tr) > 0) {
                            $t = (array)$tr->first_row();
                            $script = $this->sanitizeTriggerCreateScript($t['SQL Original Statement']);
                            $table->addTrigger($name, $script);
                        }
                    }
                }
            } elseif ($table->isView()) {
                $r = $cmd_exec->execute_query('show create view ' . $table->getName(), SQL_Command_Builder::$SELECT_COMMAND);
                $arr = $r->first_row();
                $cs = $this->sanitizeViewCreateScript($arr['Create View']);
            }
            $cs = trim($cs, ';') . ';';
            //Add DROP IF EXISTS
            if ($drop_if_exists) {
                $type = $table->isTable() ? 'TABLE' : 'VIEW';
                $table->setDropScript('DROP ' . $type . ' IF EXISTS `' . $table->getName() . '`;');
            }
            $table->setCreateSql($cs);
            $cmd_exec->close_connexion();
            return $table->getCreateSql();
        } catch (Exception $exc) {
            $cmd_exec->close_connexion();
            throw $exc;
        }
    }

    private function sanitizeViewCreateScript($script)
    {
        return $script;
    }

    private function sanitizeTriggerCreateScript($script)
    {
        return $script;
    }

    protected function foreignTablesName(\DataBaseBackup_Table $table)
    {
        $create_sql = $table->getCreateSql();
        $matches = array();
        if ($table->isTable()) {
            if (preg_match_all("/CONSTRAINT[\s\w\d\(\)`_\-]*REFERENCES\s`([\w_\d\-]*)`[\s\w\d\(\)`_\-]*,?/", $create_sql, $matches)) {
                return $matches[1];
            }
        } elseif ($table->isView()) {
            if (preg_match_all("/(?:(?:(?:from|FROM)\s+\(*`([\w_\d]+)`)|(?:\s*(?:join|JOIN)\s*`([\w_\d]+)`\s*on\(\([\w_\d\s`\.\=]+\){3}))\s*/", $create_sql, $matches, PREG_SET_ORDER)) {
                if (!class_exists('ArrayUtils'))
                    throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' the class ArrayUtils dosent existe to use. You should load core/DataStructures/Arrays/ArrayUtils library');
                return array_map(function ($item) {
                    return array_pop($item);
                }, $matches);
            }
        }
        return array();
    }

    protected function listeTableNames()
    {
        $cmd_exec = new Pdo_Command_Executor();
        $cmd_exec->set_server_param($this->getDbServer());

        try {
            $r = $cmd_exec->execute_query('show full tables', SQL_Command_Builder::$SELECT_COMMAND);
            $arr = array();
            foreach ($r as $value) {
                /*
                 * Array
                 *       (
                 *          [Tables_in_d2_db_prod_simul] => bf_accuses_commandes
                 *          [0] => bf_accuses_commandes
                 *          [Table_type] => BASE TABLE
                 *          [1] => BASE TABLE
                 *      )
                 */
                $name = $value[0];
                $type = $value[1];
                $arr[$name] = new Pdo_DataBaseBackup_Table($name, $type);
            }
            $cmd_exec->close_connexion();
            return $arr;
        } catch (Exception $exc) {
            $cmd_exec->close_connexion();
            throw $exc;
        }
    }

}

class Pdo_DataBaseBackup_Table extends DataBaseBackup_Table
{

    public function isTable()
    {
        return $this->getType() == 'BASE TABLE';
    }

    public function isView()
    {
        return $this->getType() == 'VIEW';
    }

    public function insertSql(\Common_Server_Param $db_server, $limit = -1, $offest = 0)
    {
        try {
            if ($this->isTable()) {
                $table_name = $this->getName();
                if (empty($table_name))
                    throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' the table name is empty');

                //Pour des raisons de pérformance je vais utiliser directement mysql
                $connexion = mysql_connect($db_server->getHost_name(), $db_server->getUser_name(), $db_server->getPassword());
                try {
                    mysql_select_db($db_server->getDb_name(), $connexion);
                    if (!is_numeric($offest) OR $offest < 0)
                        $offest = 0;
                    if (is_numeric($limit) AND $limit > 0)
                        $query = mysql_query("SELECT * FROM " . $table_name . " LIMIT $offest, $limit", $connexion);
                    else
                        $query = mysql_query("SELECT * FROM " . $table_name, $connexion);
                    $insertions = '';
                    while ($nuplet = mysql_fetch_array($query)) {
                        $insertions .= "INSERT INTO `" . $table_name . "` VALUES(";
                        for ($i = 0; $i < mysql_num_fields($query); $i++) {
                            if ($i != 0)
                                $insertions .= ", ";
                            if (is_null($nuplet[$i]) OR !isset($nuplet[$i]))
                                $insertions .= 'NULL';
                            else {
                                if (in_array(mysql_field_type($query, $i), array("string", "blob", 'date', 'datetime')))
                                    $insertions .= "'";
                                $insertions .= addslashes($nuplet[$i]);
                                if (in_array(mysql_field_type($query, $i), array("string", "blob", 'date', 'datetime')))
                                    $insertions .= "'";
                            }
                        }
                        $insertions .= ");\n";
                    }
                    mysql_close($connexion);
                } catch (Exception $exc) {
                    mysql_close($connexion);
                    throw $exc;
                }

                return $insertions;
            } else
                return '';
        } catch (Exception $exc) {
            throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' Table : ' . $this->getName() . $exc->getMessage());
        }
    }

}

class Pdo_Delete_Command_Builder extends SQL_Delete_Command_Builder
{

    protected $table_name;
    protected $wheres;
    protected $wheres_is;

    function __construct($table_name = null, $wheres = array())
    {
        $this->clear();
        $this->table_name = $table_name;
        foreach ($wheres as $value) {
            $this->where($value['table_name'], $value['field_name'], $value['op'], $value['value'], $value['type']);
        }
    }

    public function from($table_name)
    {
        $this->table_name = $this->table_prefix . $table_name;
        return $this;
    }

    public function get_command_type()
    {
        return parent::$DELETE_COMMAND;
    }

    public function clear()
    {
        $this->table_name = "";
        $this->wheres = array();
        $this->wheres_is = array();
    }

    public function where_in($table_name, $field_name, array $values, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;

        if (in_array($type, array(SQL_Column_Type::$STRING, SQL_Column_Type::$DATE)))
            $values = array_map(function ($item) {
                return "'" . $item . "'";
            }, $values);
        $this->wheres[] = $table_name . "." . $field_name . " IN (" . implode(', ', $values) . ') ';
        return $this;
    }

    public function where_native($where)
    {
        $this->wheres[] = $where;
        return $this;
    }

    public function where($table_name, $field_name, $op, $value, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;
        switch ($type) {
            case SQL_Column_Type::$DATE:
                if (is_a($value, "DateTime"))
                    $value = $value->format("Y-m-d H:i:s");
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => "'" . $this->sanitizeDate($value) . "'",
                    'type' => $type,
                );
                break;
            case SQL_Column_Type::$STRING:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => "'" . $this->sanitizeString($value) . "'",
                    'type' => $type,
                );
                break;
            case SQL_Column_Type::$BOOLEAN:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => intval($value),
                    'type' => $type,
                );
                break;
            case SQL_Column_Type::$INTEGER:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => intval($value),
                    'type' => $type,
                );
                break;
            case SQL_Column_Type::$SQLEXPRESSION:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => $value,
                    'type' => $type,
                );
                break;
            default:
                $this->wheres[] = array(
                    'table_name' => $table_name,
                    'field_name' => $field_name,
                    'op' => $op,
                    'value' => $value,
                    'type' => $type,
                );
                break;
        }

        return $this;
    }

    public function where_is($table_name, $field_name, $value)
    {
        $table_name = $this->table_prefix . $table_name;
        $this->wheres_is[] = array(
            'tablename' => $table_name,
            'fieldname' => $field_name,
            'value' => $value
        );
    }

    public function get()
    {
        return $this->get_query($this->wheres, $this->wheres_is);
    }

    protected function get_query($wheres, $wheres_is, $columns = array())
    {
        $sql = "DELETE FROM " . $this->table_name . ' ';
        if (!empty($wheres))
            $sql .= $this->build_where_sql($wheres, $wheres_is);

        return $sql;
    }

    protected function build_where_sql($wheres, $wheres_is)
    {
        return " WHERE " . implode(" AND ", array_map(function ($item) {

            return $item['table_name'] . "." . $item['field_name'] . " " . $item['op'] . ' ' . $item['value'];
        }, $wheres)) . " " . implode(" AND ", array_map(function ($item) {

            return $item['table_name'] . "." . $item['field_name'] . " IS " . $item['value'];
        }, $wheres_is)) . " ";
    }

    public function where_not_in($table_name, $field_name, array $values, $type = 2)
    {
        $table_name = $this->table_prefix . $table_name;

        if (in_array($type, array(SQL_Column_Type::$STRING, SQL_Column_Type::$DATE)))
            $values = array_map(function ($item) {
                return "'" . $item . "'";
            }, $values);
        $this->wheres[] = $table_name . "." . $field_name . " NOT IN (" . implode(', ', $values) . ') ';
        return $this;
    }

}

class Pdo_Procedure_Command_Builder extends SQL_Procedure_Command_Builder
{
    /**
     * @var string
     */
    protected $_procedure_name;
    /**
     * @var array
     */
    protected $_params;

    /**
     * @var integer
     */
    protected $_data_access_type;

    function get_command_type()
    {
        return SQL_Command_Builder::$PROCEDURE_COMMAND;
    }

    /**
     * @param $name stored procedure name
     * @return self
     */
    function procedure($name)
    {
        if (is_string($name)) $this->_procedure_name = $name;
        else throw new SQL_Exception('Le nom de la procedure stockée est invalide, il doit etre une chaine de caractères.');

        return $this;
    }

    /**
     * set param values
     * For the direction param you use one of the const :
     * Procedure_Command_Builder::PD_IN
     * Procedure_Command_Builder::PD_OUT
     * Procedure_Command_Builder::PD_INOUT
     *
     * For the data_type param you use one of the const :
     * SQL_Column_Type::$INTEGER
     * SQL_Column_Type::$$DATE
     * SQL_Column_Type::$FLOAT
     * SQL_Column_Type::$BOOLEAN
     * SQL_Column_Type::$SQLEXPRESSION
     *
     * @param $name string the param name without @ character
     * @param $direction integer
     * @param $data_type integer
     * @param $value mixed
     * @return self
     */
    function param($name, $direction, $data_type, $value)
    {
        if (!is_string($name) OR preg_match("/^@/", $name))
            throw new SQL_Exception('Le nom du paramettre doit etre une string et ne doit pas commencer par @.');

        self::validate_param_direction($direction);

        SQL_Column_Type::validate($data_type);

        $this->_params[$direction][$name] = array(
            'name'=>$name,
            'value' => $value,
            'type' => SQL_Column_Type::mapToPrimitive($data_type)
        );

        return $this;
    }

    function get()
    {
        $instructions = array();

        $in_param = $this->getParamsByDirection(self::PD_IN);

        foreach ($in_param as $name => $param) {
            $instructions[] = 'Set @' . $name . '=' . $param['value'];
        }

        $call = 'CALL ' . $this->_procedure_name . '(';
        foreach ($in_param as $name => $param) {
            $call .= '@' . $name . ',';
        }
        $call = rtrim($call, ",") . ')';
        $instructions = array_merge($instructions, array($call));

        $cmd = implode('; ', $instructions) . ';';
        return $cmd;
    }

    /**
     * Return an array of param that are IN
     * array(
     *   'name'=>
     *   'value'=>
     * )
     * @param int $direction
     * @return array
     */
    function getParamsByDirection($direction = self::PD_IN)
    {
        self::validate_param_direction($direction);
        if (array_key_exists($direction, $this->_params)) {
            return $this->_params[$direction];
        } else return array();
    }

    function clear()
    {
        $this->_params = array();
        $this->_data_access_type = -1;
        $this->_procedure_name = '';
    }

    /**
     * You use one of the const :
     *
     * Procedure_Command_Builder::DA_NO_SQL
     * Procedure_Command_Builder::DA_READ_SQL_DATA
     * Procedure_Command_Builder::DA_CONTAINS_SQL
     * Procedure_Command_Builder::DA_MODIFIES_SQL_DATA
     *
     * @param $type interger
     * @return self
     */
    function data_access_type($type = self::READ_SQL_DATA)
    {
        self::validate_data_access_type($type);

        $this->_data_access_type = $type;

        return $this;
    }

    /**
     * @return string
     */
    function getProcedureName()
    {
        return $this->_procedure_name;
    }


}

?>
