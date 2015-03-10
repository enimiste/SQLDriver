<?php

$driver_pdo = realpath(__DIR__ . DIRECTORY_SEPARATOR . "Pdo" . DIRECTORY_SEPARATOR . "Pdo_Command.php");

require_once $driver_pdo;

/**
 *
 * @author NOUNI EL BACHIR
 */
final class SQL_Factory
{

    /**
     * @param string $driver
     * @return SQL_Select_Command_Builder
     * @throws SQL_Exception
     */
    public static function get_select_command_builder($driver = 'pdo')
    {
        switch ($driver) {
            case 'pdo':
                return new Pdo_Select_Command_Builder();
                break;
            default:
                throw new SQL_Exception(__FUNCTION__ . 'Driver not defined');
                break;
        }
    }

    /**
     * @param string $driver
     * @return SQL_Update_Command_Builder
     * @throws SQL_Exception
     */
    public static function get_update_command_builder($driver = 'pdo')
    {
        switch ($driver) {
            case 'pdo':
                return new Pdo_Update_Command_Builder();
                break;
            default:
                throw new SQL_Exception(__FUNCTION__ . 'Driver not defined');
                break;
        }
    }

    /**
     * @param string $driver
     * @return SQL_Insert_Command_Builder
     * @throws SQL_Exception
     */
    public static function get_insert_command_builder($driver = 'pdo')
    {
        switch ($driver) {
            case 'pdo':
                return new Pdo_Insert_Command_Builder();
                break;
            default:
                throw new SQL_Exception(__FUNCTION__ . 'Driver not defined');
                break;
        }
    }

    /**
     * @param string $driver
     * @return SQL_Delete_Command_Builder
     * @throws SQL_Exception
     */
    public static function get_delete_command_builder($driver = 'pdo')
    {
        switch ($driver) {
            case 'pdo':
                return new Pdo_Delete_Command_Builder();
                break;
            default:
                throw new SQL_Exception(__FUNCTION__ . 'Driver not defined');
                break;
        }
    }

    /**
     * @param string $driver
     * @return SQL_Procedure_Command_Builder
     * @throws SQL_Exception
     */
    public static function get_procedure_command_builder($driver = 'pdo')
    {
        switch ($driver) {
            case 'pdo':
                return new Pdo_Procedure_Command_Builder();
                break;
            default:
                throw new SQL_Exception(__FUNCTION__ . 'Driver not defined');
                break;
        }
    }

    /**
     * @param string $driver
     * @return SQL_Command_Executor
     * @throws SQL_Exception
     */
    public static function get_command_executor($driver = 'pdo')
    {
        switch ($driver) {
            case 'pdo':
                return new Pdo_Command_Executor();
                break;
            default:
                throw new SQL_Exception(__FUNCTION__ . 'Driver not defined');
                break;
        }
    }

    /**
     * @param string $driver
     * @return DataBaseBackup
     * @throws SQL_Exception
     */
    public static function get_database_backup($driver = 'pdo')
    {
        switch ($driver) {
            case 'pdo':
                return new Pdo_DataBaseBackup();
                break;
            default:
                throw new SQL_Exception(__FUNCTION__ . 'Driver not defined');
                break;
        }
    }

}

?>
