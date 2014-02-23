Introduction :

This is a new sql driver to build and Eeecute SQL commands easly using php.

Its useful for developpers who uses the native php to interacte with database.

Exemple of command building :

$cmd = SQL_Factory::get_select_command_builder();
$cmd = $cmd->prefix_table("bf_")
            ->from("contrats")
            ->select("contrats", "id", "id_contrat")
            ->select("contrats", "identifiant", "identifiant_contrat")
            ->select("commandes", "id", "id_commande")
            ->select("commandes", "contenu", "contenu_commande")
            ->select("commandes", "created_on", "created_on_commande")
            ->join("commandes", "id_contrat", "=", "contrats", "id")
            ->where("commandes", "etat_envoi", "=", "0");

echo $cmd->get();

this code will ouput :

SELECT bf_contrats.id AS id_contrat, bf_contrats.identifiant AS identifiant_contrat, bf_commandes.id AS id_commande, bf_commandes.contenu AS contenu_commande, bf_commandes.created_on AS created_on_commande  FROM bf_contrats  LEFT JOIN bf_commandes ON bf_commandes.id_contrat = bf_contrats.id  WHERE bf_commandes.etat_envoi = 0

To execute the builded command $cmd we use :

$result_set = SQL_Factory::get_command_excutor()
            ->set_server_param(new Common_Server_Param("localhost", "3306", "root", "root", "d2_db"))
            ->execute_query($cmd);

$result_set is not the same as the result of mysql. This one can be used inside a foreach loop :

foreach ($result_set as $value) {
   //Do somthing ....
}


There is an abstraction that not specify the type of DB server that will be used :


					 SQL_Command_Builder
			         	/		      \
				       /		       \
		SQL_Update_Command_Builder		    SQL_Select_Command_builder
								|- select
								|- from
								|- join
								|- where
								|- order_by



   Common_Server_Param	<============	SQL_Command_Executor   ========= >  SQL_Result_Set
	|- host_name
	|- port_number							          |  
	|- user_name				   		                  |
	|- password							          |
	|- db_name								  |
										  v
									SQL_NoQuery_Result


An implementation of Mysql :

			SQL_Select_Command_Builder

				^
				|
				|
				|

			Mysql_Select_Command_Builder  ========> Mysql_Result_Set

									|
									|
									|
									|
									v

								SQL_Result_Set


For chosing a specifique driver, the user of this library will use a factory class :



				SQL_Factory
					|- get_select_command_builder()
					|- get_update_command_builder()
					|- get_command_excutor()



