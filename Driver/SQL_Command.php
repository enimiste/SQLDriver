<?php

require_once 'SQL_Exceptions.php';
require_once 'SQL_Infra_Common.php';

/**
 *
 *
 * @author NOUNI EL BACHIR
 */
abstract class SQL_Command_Builder
{

    static $SELECT_COMMAND = 1;
    static $UPDATE_COMMAND = 2;
    static $INSERT_COMMAND = 3;
    static $DELETE_COMMAND = 4;
    static $PROCEDURE_COMMAND = 5;

    /**
     *
     * @var string
     */
    protected $table_prefix;

    function prefix_table($prefix)
    {
        $this->table_prefix = $prefix;
        return $this;
    }

    abstract function get();

    abstract function get_command_type();

    abstract function clear();

    function sanitizeString($value)
    {
        return preg_replace("/'/", "\'", $value);
    }

    function sanitizeDate($value)
    {
        return preg_replace("/'/", "\'", $value);
    }

    public function __toString()
    {
        return $this->get();
    }

}

final class SQL_Column_Type
{

    static $INTEGER = 1;
    static $STRING = 2;
    static $DATE = 3;
    static $FLOAT = 4;
    static $BOOLEAN = 5;
    static $SQLEXPRESSION = 6;

    static function validate($type)
    {
        if (!in_array($type, array(self::$INTEGER, self::$BOOLEAN, self::$DATE, self::$FLOAT, self::$SQLEXPRESSION, self::$STRING)))
            throw new SQL_Exception('La valeur du type de donnée est invalide');
    }

    static function mapToPrimitive($data_type)
    {
        switch ($data_type) {
            case self::$BOOLEAN:
                return 'bool';
            case self::$DATE:
                return 'date';
            case self::$FLOAT:
                return 'float';
            case self::$INTEGER:
                return 'integer';
            case self::$STRING:
                return 'string';
            default:
                throw new SQL_Exception('No primitive data associted to this SQL_Column_Type');
        }
    }
}

interface SQL_Where
{

    function where_native($where);

    function where($table_name, $field_name, $op, $value, $type = 2);

    function where_is($table_name, $field_name, $value);

    function where_in($table_name, $field_name, Array $values, $type = 2);

    function where_not_in($table_name, $field_name, Array $values, $type = 2);

    function where_like($table_name, $field_name, $value);
}

abstract class SQL_Select_Command_Builder extends SQL_Command_Builder implements SQL_Where
{

    abstract function select($table_name, $field_name, $alias = FALSE);

    abstract function select_distinct($table_name, $field_name, $alias = FALSE);

    abstract function select_count($table_name, $field_name, $alias = FALSE);

    abstract function from($table_name, $alias = FALSE);

    abstract function join($right_table_name, $right_field_name, $op, $left_table_name, $left_field_name, $join_type = "left", $right_table_name_alias = "");

    abstract function limit($limit, $offset = 0);

    abstract function min($table_name, $field_name, $alias = FALSE);

    abstract function max($table_name, $field_name, $alias = FALSE);

    abstract function avg($table_name, $field_name, $alias = FALSE);

    abstract function order_by($table_name, $field_name, $order = "ASC");

    abstract function clean_select();
}

abstract class SQL_Update_Command_Builder extends SQL_Command_Builder implements SQL_Where
{

    abstract function update_table($table_name);

    abstract function column($field_name, $field_new_value, $type = 2);
}

abstract class SQL_Insert_Command_Builder extends SQL_Command_Builder
{

    abstract function into_table($table_name);

    abstract function column($field_name, $field_new_value, $type = 2);
}

abstract class SQL_Delete_Command_Builder extends SQL_Command_Builder implements SQL_Where
{

    abstract function from($table_name);
}

abstract class SQL_Command_Executor
{

    /**
     *
     * @var boolean
     */
    protected $_transaction_active;

    public function __construct()
    {
        $this->_transaction_active = FALSE;
    }

    /**
     * @param Common_Server_Param $params
     * @return $this
     */
    public function set_server_param(Common_Server_Param $params)
    {
        $this->save_server_param($params);
        return $this;
    }

    abstract protected function save_server_param(Common_Server_Param $params);

    /**
     *
     * @return bool
     */
    public function is_transcation_active()
    {
        return $this->_transaction_active;
    }

    /**
     *
     * @param bool $enabled
     */
    public function enable_transaction($enabled = true)
    {
        $this->_transaction_active = $enabled;
        return $this;
    }

    abstract function open_connexion();

    abstract function close_connexion();

    /**
     *
     */
    abstract function execute_query($command, $type = 2);
}

abstract class SQL_Result_Set implements Iterator, Countable
{

    abstract function first_row();

    abstract function free_result();

    abstract function as_array();
}

abstract class SQL_NoQuery_Result
{

    abstract function get_bool_value();

    abstract function get_int_value();
}

abstract class SQL_NoQuery_Result_Set
{

    abstract function push(SQL_NoQuery_Result $result);

    abstract function get_bool_and();

    abstract function get_bool_or();
}

abstract class DataBaseBackup
{

    /**
     *
     * @var \Common_Server_Param
     */
    protected $_db_server;

    /**
     *
     * @var string
     */
    protected $_path;

    /**
     *
     * @var array array of \DataBaseBackup_Table
     */
    protected $_tables;

    /**
     *
     * @var integer 1 structure, 2  données et 3 structure et données
     */
    protected $_mode;

    public function __construct(\Common_Server_Param $db_server = NULL)
    {
        ini_set('memory_limit', -1);
        //ini_set('max_execution_time', 300);
        $this->_db_server = $db_server;
        $this->_path = '';
    }

    /**
     *
     * @param \Common_Server_Param $db_server
     * @param integer $mode : 1 structure, 2  données et 3 structure et données
     * @param string $path
     * @param array $exclude_tables tables to exclude from result dump
     * @return boolean
     * @throws Exception
     */
    function dumpToFile($mode, $path, $exclude_tables = array(), $drop_if_exists = FALSE)
    {
        if (!isset($this->_db_server))
            throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' you should set the property db_server.');
        if (!is_string($path))
            throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' the param $path should be a valide path to an existing dir');
        if (is_dir($path)) {
            $path = $path . DIRECTORY_SEPARATOR . $this->getDbServer()->getDb_name() . '_' . date('dmYHis') . '.sql';
        } else {
            $dir = dirname($path);
            if (!is_dir($dir))
                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' the param $path should be a valide path to an existing dir or file');
        }

        $this->_path = $path;
        $this->_mode = $mode;
        //Dump
        $this->_tables = $this->listeTableNames();

        foreach ($this->_tables as $table) {
            $this->loadTable($table, $drop_if_exists);
        }

        //Exclude tables : Problème ppur les dépendances des vues ==> à fixer
        if (!empty($exclude_tables)) {
            /* $this->_tables = array_filter($this->_tables, function(\DataBaseBackup_Table $table) use($exclude_tables) {
              $tables_name = array_map(function(\DataBaseBackup_Table $item) {
              return $item->getName();
              }, $table->getForeignTables());
              $tables_name[] = $table->getName();

              $excluded = array_filter($tables_name, function($item) use($exclude_tables) {
              return in_array($item, $exclude_tables);
              });

              return empty($excluded);
              });
              $obj = $this;
              $this->_tables = array_filter($this->_tables, function(\DataBaseBackup_Table $table) use($obj) {
              try {
              foreach ($table->getForeignTables() as $value) {
              $obj->getTable($value->getName());
              }
              return TRUE;
              } catch (Exception $ex) {
              return FALSE;
              }
              }); */
        }
        foreach (range(1, 100) as $v) {
            $this->_tables = $this->ordonne_tables();
            $this->setIds($v);
            try {
                $this->check_invariant();
                break;
            } catch (Exception $exc) {
                continue;
            }
        }
        $this->check_invariant();
        //Output :
        if ($drop_if_exists) {
            $drop = $this->output_drop();
            file_put_contents($path . '_C.sql', $drop, FILE_APPEND);
        }
        $create = $this->output_create();
        file_put_contents($path . '_C.sql', $create, FILE_APPEND);
        //Generate insertion sql:
        // si l'utilisateur a demandé les données ou la totale
        if ($mode > 1) {
            $this->output_insert($path . '_I.sql');
        }
        return TRUE;
    }

    /**
     *
     * @return string
     */
    protected function output_drop()
    {
        $r = '-- -------------------------------------------------------' . PHP_EOL;
        $r .= '--       Drop SQL' . PHP_EOL;
        $r .= '-- -------------------------------------------------------' . PHP_EOL;
        $func = function ($acc, Array $tables) use (&$func) {
            if (empty($tables))
                return $acc;
            $table = ArrayUtils::array_value_at($tables, 1);
            return $func($acc, array_slice($tables, 1)) . PHP_EOL . $table->getDropScript();
        };
        $r .= $func($r, $this->_tables);
        $r .= PHP_EOL . PHP_EOL . PHP_EOL;
        return $r;
    }

    /**
     *
     * @return string
     */
    protected function output_create()
    {
        $r = $this->getOutputHeader() . PHP_EOL;
        //Create SQL
        foreach ($this->getTables() as $table) {
            $r .= '-- -------------------------------------------------------' . PHP_EOL;
            $r .= '--     Table ' . $table->getName() . PHP_EOL;
            $r .= '--       Create SQL' . PHP_EOL;
            $r .= '-- -------------------------------------------------------' . PHP_EOL;
            $r .= $table->getCreateSql() . PHP_EOL;
            $triggers = $table->getTriggers();
            if (!empty($triggers)) {
                $r .= '-- -------------------------------------------------------' . PHP_EOL;
                $r .= '--       Create SQL Triggers For Table : ' . $table->getName() . PHP_EOL;
                $r .= '-- -------------------------------------------------------' . PHP_EOL;
                $r .= 'DELIMITER //' . PHP_EOL;
                foreach ($triggers as $name => $script) {
                    $r .= '-- Trigger : ' . $name . PHP_EOL;
                    $r .= rtrim($script, ";") . '//' . PHP_EOL;
                }
                $r .= 'DELIMITER ;' . PHP_EOL;
            }
        }
        $r .= PHP_EOL . PHP_EOL . PHP_EOL;
        return $r;
    }

    /**
     *
     * @return string
     */
    protected function output_insert($path)
    {
        //Select SQL
        foreach ($this->getTables() as $table) {
            if ($table->isTable()) {
                $r = '';
                $r .= '-- -------------------------------------------------------' . PHP_EOL;
                $r .= '--     Table ' . $table->getName() . PHP_EOL;
                $r .= '--       Insertion SQL' . PHP_EOL;
                $r .= '-- -------------------------------------------------------' . PHP_EOL;
                $r .= PHP_EOL;
                file_put_contents($path, $r, FILE_APPEND);
                $offset = 0;
                $limit = 1000;
                $insert_sql = $table->insertSql($this->getDbServer(), $limit, $offset);

                while (!empty($insert_sql) > 0) {
                    file_put_contents($path, $insert_sql, FILE_APPEND);
                    $offset += $limit;
                    $insert_sql = $table->insertSql($this->getDbServer(), $limit, $offset);
                }
            }
        }
        return TRUE;
    }

    /**
     *
     * @param integer $init id to start from
     */
    protected function setIds($init = 1)
    {
        $id = $init;
        foreach ($this->getTables() as $table) {
            $table->setId($id);
            $id++;
        }
    }

    /**
     * Validate Invariant : id table should be heigher than id of his foreign tables
     * @throws Exception
     */
    protected function check_invariant()
    {
        foreach ($this->getTables() as $table) {
            if ($table->hasForeignTables()) {
                $ids = array_map(function ($item) {
                    return $item->getId();
                }, $table->getForeignTables());
                $max = max($ids);
                if ($table->getId() < $max)
                    throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' Table : ' . $table->getName() . ' ID : ' . $table->getId() . ' should be heigher than the max of foreign tables MAX : ' . $max);
            }
        }
    }

    /**
     *
     * @return array array of \DataBaseBackup_Table
     */
    protected function ordonne_tables()
    {
        $tables = $this->getTables();
        $ord_tables[] = array_shift($tables);
        foreach ($tables as $table) {
            $b_index = -1;
            $found = FALSE;
            foreach ($ord_tables as $tbl) {
                $b_index++;
                if (ArrayUtils::array_fexists($tbl->getForeignTables(), function ($item) use ($table) {
                    return $item->getName() == $table->getName();
                })
                ) {
                    $found = TRUE;
                    break;
                }
            }
            if ($found AND $b_index < count($ord_tables)) {
                //Select at
                $ord_tables = ArrayUtils::array_insert_at($ord_tables, $table, $b_index + 1);
            } else
                $ord_tables[] = $table;
        }
        return $ord_tables;
    }

    /**
     *
     * @param array $tables array of \DataBaseBackup_Table
     */
    protected function printTablesToConsole(Array $tables)
    {
        array_walk($tables, function ($item) {
            print $item . "\n";
        });
    }

    /**
     *
     * @param \DataBaseBackup_Table $table
     */
    protected function loadTable(\DataBaseBackup_Table $table, $drop_if_exists = FALSE)
    {
        $mode = $this->_mode;
        if ($mode == 1 || $mode == 3) {
            $this->createSql($table, $drop_if_exists);
            $this->foreignTables($table);
        }
    }

    /**
     *
     * @param \DataBaseBackup_Table $table
     * @throws Exception
     */
    protected function foreignTables(\DataBaseBackup_Table $table)
    {
        try {
            $foreign_tables_name = $this->foreignTablesName($table);
            if (!empty($foreign_tables_name)) {
                $arr = array();
                foreach ($foreign_tables_name as $name) {
                    $tbl = $this->getTable($name);
                    $arr[] = $tbl;
                }
                $table->setForeignTables($arr);
            }
        } catch (Exception $exc) {
            throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' erreur : ' . $exc->getMessage());
        }
    }

    /**
     *
     * @return \Common_Server_Param
     */
    public function getDbServer()
    {
        return $this->_db_server;
    }

    /**
     *
     * @param \Common_Server_Param $db_server
     */
    public function setDbServer($db_server)
    {
        $this->_db_server = $db_server;
    }

    /**
     *
     * @return string
     */
    public function getDumpFileName()
    {
        return basename($this->_path);
    }

    /**
     *
     * @return array of \DataBaseBackup_Table
     */
    public function getTables()
    {
        return $this->_tables;
    }

    /**
     * @return string Description
     */
    protected function getOutputHeader()
    {
        $modes = array(1 => 'structure', 2 => 'données', 3 => 'structure et données');
        $h = '-- ------------------------------------------------------------------------------------------------------------------' . PHP_EOL;
        $h .= '--                       Backup de la base de donnees ' . $this->getDbServer()->getDb_name() . '                     ' . PHP_EOL;
        $h .= '--                                     Date : ' . date('Y-m-d H:i:s') . '                                            ' . PHP_EOL;
        $h .= '--                               Type Backup : ' . $modes[$this->_mode] . '                                          ' . PHP_EOL;
        $h .= '-- ------------------------------------------------------------------------------------------------------------------' . PHP_EOL;

        return $h;
    }

    /**
     *
     * @param string $name
     */
    function getTable($name)
    {
        $f = array_filter($this->getTables(), function (\DataBaseBackup_Table $item) use ($name) {
            return $item->getName() == $name;
        });
        if (empty($f))
            throw new Exception(__CLASS__ . '::' . __FUNCTION__ . $name . ' table introuvable.');
        return array_shift($f);
    }

    /**
     *
     * @param \DataBaseBackup_Table $table
     */
    function addTable(\DataBaseBackup_Table $table)
    {
        $f = array_filter($this->_tables, function (\DataBaseBackup_Table $item) use ($table) {
            return $table->getName() == $item->getName();
        });
        if (empty($f))
            $this->_tables[] = $table;
    }

    /**
     * @return array array of \DataBaseBackup_Table object
     */
    abstract protected function listeTableNames();

    /**
     * @return array of string
     */
    abstract protected function foreignTablesName(\DataBaseBackup_Table $table);

    /**
     * @return \DataBaseBackup_Table Description
     */
    abstract protected function createSql(\DataBaseBackup_Table $table, $drop_if_exists = FALSE);
}

abstract class DataBaseBackup_Table
{

    /**
     *
     * @var string
     */
    protected $_name;

    /**
     *
     * @var string
     */
    protected $_type;

    /**
     *
     * @var string
     */
    protected $_create_sql;

    /**
     *
     * @var string
     */
    protected $_select_all_result;

    /**
     *
     * @var integer
     */
    protected $_id;

    /**
     *
     * @var array array of \DataBaseBackup_Table
     */
    protected $_foreign_tables;

    /**
     *
     * @var string
     */
    protected $_drop_script;

    /**
     *
     * @var array array('trigger_name'=>'create_script')
     */
    protected $_triggers;

    function __construct($name, $type)
    {
        $this->_name = $name;
        $this->_type = $type;
        $this->_id = -1;
        $this->_foreign_tables = array();
        $this->_drop_script = NULL;
        $this->_create_sql = NULL;
        $this->_triggers = array();
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setType($type)
    {
        $this->_type = $type;
    }

    public function getCreateSql()
    {
        return $this->_create_sql;
    }

    public function setCreateSql($create_sql)
    {
        $this->_create_sql = $create_sql;
    }

    /**
     *
     * @return integer
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     *
     * @param type $id
     * @throws Exception
     */
    public function setId($id)
    {
        if (!is_numeric($id))
            throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' the param id should be positif integer');
        $this->_id = intval($id);
    }

    /**
     *
     * @return \DataBaseBackup_Table
     */
    public function getForeignTables()
    {
        return $this->_foreign_tables;
    }

    /**
     *
     * @param array $foreing_tables array of \DataBaseBackup_Table
     */
    public function setForeignTables(Array $foreing_tables)
    {
        foreach ($foreing_tables as $value) {
            $this->addForeingTable($value);
        }
    }

    public function getDropScript()
    {
        return $this->_drop_script;
    }

    public function setDropScript($drop_script)
    {
        $this->_drop_script = $drop_script;
    }

    /**
     *
     * @param \DataBaseBackup_Table $table
     */
    public function addForeingTable(\DataBaseBackup_Table $table)
    {
        $f = array_filter($table->getForeignTables(), function (\DataBaseBackup_Table $item) use ($table) {
            return $table->getName() == $item->getName();
        });
        if (empty($f))
            $this->_foreign_tables[] = $table;
    }

    /**
     * @return boolean
     */
    public function hasForeignTables()
    {
        return !empty($this->_foreign_tables);
    }

    /**
     *
     * @param array $triggers
     * @throws Exception
     */
    public function setTriggers(Array $triggers)
    {
        foreach ($triggers as $name => $script) {
            if (is_string($name) AND is_string($script))
                $this->addTrigger($name, $script);
            else
                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ' Invalide triggers array for table ' . $this->getName());
        }
    }

    /**
     *
     * @return array
     */
    public function getTriggers()
    {
        return $this->_triggers;
    }

    /**
     *
     * @param string $name
     * @param string $script
     */
    public function addTrigger($name, $script)
    {
        if (!isset($this->_triggers[$name]))
            $this->_triggers[$name] = $script;
    }

    /**
     * @return boolean
     */
    abstract function isTable();

    /**
     * @return boolean
     */
    abstract function isView();

    /**
     * @return array
     */
    abstract function insertSql(\Common_Server_Param $db_server, $limit = -1, $offest = 0);

    function __toString()
    {
        $r = '(' . $this->getId() . ') ' . $this->getType() . ' : ' . $this->getName() . PHP_EOL;
        if ($this->hasForeignTables()) {
            $r .= '-----Foreign Tables/Views : ' . PHP_EOL;
            foreach ($this->getForeignTables() as $tbl) {
                $r .= '----------' . '(' . $tbl->getId() . ') ' . $tbl->getType() . ' : ' . $tbl->getName() . PHP_EOL;
            }
        }
        return $r;
    }

}

abstract class SQL_Procedure_Command_Builder extends SQL_Command_Builder
{

    //Data access type
    const DA_NO_SQL = 1;
    const DA_READ_SQL_DATA = 2;
    const DA_CONTAINS_SQL = 3;
    const DA_MODIFIES_SQL_DATA = 4;

    //Parameters direction
    const PD_IN = 1;
    const PD_OUT = 2;
    const PD_INOUT = 3;

    /**
     * @param $name stored procedure name
     * @return self
     */
    abstract function procedure($name);

    /**
     * @return string
     */
    abstract function getProcedureName();

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
    abstract function data_access_type($type = self::READ_SQL_DATA);

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
    abstract function param($name, $direction, $data_type, $value);

    /**
     * Return an array of param that are IN
     * array(
     *   'name'=>
     *   'value'=>
     * )
     * @param int $direction
     * @return array
     */
    abstract function getParamsByDirection($direction = self::PD_IN);

    /**
     * Permet de valider le type de direction passer en paramettre par rapport au contantes définées dans la classe
     *
     * @param $direction integer
     * @throws SQL_Exception
     */
    static function validate_param_direction($direction)
    {
        if (!in_array($direction, array(self::PD_IN, self::PD_OUT, self::PD_INOUT)))
            throw new SQL_Exception('La valeur de la direction est invalide');
    }

    /**
     * Permet de valider le type de d'accès passer en paramettre par rapport au contantes définées dans la classe
     *
     * @param $type integer
     * @throws SQL_Exception
     */
    static function validate_data_access_type($type)
    {
        if (!in_array($type, array(self::DA_NO_SQL, self::DA_CONTAINS_SQL, self::DA_MODIFIES_SQL_DATA, self::DA_READ_SQL_DATA)))
            throw new SQL_Exception('La valeur du type d acces est invalide');
    }


}

?>
