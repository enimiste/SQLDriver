<?php

class SQL_Exception extends Exception{
    function __construct($msg, $code = 1, $previous = NULL) {
        parent::__construct("SQL_Exception : " . $msg, $code, $previous);
    }

}
?>
