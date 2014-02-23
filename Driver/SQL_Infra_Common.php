<?php

class Common_Server_Param{
    private $host_name;
    private $port_number;
    private $user_name;
    private $password;
    private $db_name;
    
    function __construct($host_name, $port_number, $user_name, $password, $db_name) {
        $this->host_name = $host_name;
        $this->port_number = $port_number;
        $this->user_name = $user_name;
        $this->password = $password;
        $this->db_name = $db_name;
    }
    
    public function getHost_name() {
        return $this->host_name;
    }

    public function setHost_name($host_name) {
        $this->host_name = $host_name;
    }

    public function getPort_number() {
        return $this->port_number;
    }

    public function setPort_number($port_number) {
        $this->port_number = $port_number;
    }

    public function getUser_name() {
        return $this->user_name;
    }

    public function setUser_name($user_name) {
        $this->user_name = $user_name;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getDb_name() {
        return $this->db_name;
    }

    public function setDb_name($db_name) {
        $this->db_name = $db_name;
    }


}
?>
