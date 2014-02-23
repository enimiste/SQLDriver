<?php
require_once 'Mysql/MYSQL_Command.php';

/**
* 
* @author NOUNI EL BACHIR
*/
final class SQL_Factory{
    public static function get_select_command_builder(){
        return new Mysql_Select_Command_Builder();
    }
    public static function get_update_command_builder(){
        return new Mysql_Update_Command_Builder();
    }
    public static function get_command_excutor(){
        return new Mysql_Command_Executor();
    }
}
?>
