<?php

$driver_pdo = realpath(__DIR__ . DIRECTORY_SEPARATOR . "Pdo" . DIRECTORY_SEPARATOR . "Pdo_Command.php");

require_once $driver_pdo;

/**
 *
 * @author NOUNI EL BACHIR
 */
final class SQL_Factory
{

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

    public static function get_command_excutor($driver = 'pdo')
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
