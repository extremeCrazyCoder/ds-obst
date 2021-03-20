<?php
// a class for simple use of MySQL
// copyright by Robert Nitsch, 2006
// www.robertnitsch.de

// version 1.1.0.0

if(!defined('SSQL_INC_CHECK')) die('access denied!');

class simpleMySQL {

    private $connection;
    private $querycount;
    private $affectedrows;
    public $lasterror;
    public $lastquery;
    
    // constructor
    public function __construct($db_user, $db_pass, $db_name, $db_host='localhost')
    {
        $this->querycount=0;
        $this->affectedrows=0;
        $this->lasterror='';
        $this->lastquery='';
        
        $this->connection = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // connect to mysql database
        if($this->connection->connect_errno)
        {
            $this->saveError('Verbindung zur MySQL-Datenbank fehlgeschlagen: '.mysqli_error());
            return false;
        }
        else
        {
            return true;
        }
    }
    
    public function connected()
    {
        if($this->connection != false)
            return true;
        else
            return false;
    }
    
    
    public function sql_query($query)
    {
        $this->lastquery = $query;
        $result = $this->connection->query($query); 
       
        if($result)
        {
            $this->querycount++;
            $this->affectedrows += $this->connection->affected_rows;
            
            if($result === true) {
                return true;
            }
            $res = [];
            for($i = 0; $i < $this->connection->affected_rows; $i++) {
                $res[] = (array) $result->fetch_object();
            }
            return $res;
        }
        else
        {
            // save the error message
            $this->saveError();
            return false;
        }
    }
    
    private function saveError($msg='')
    {
        if(empty($msg))
        {
            $this->lasterror=$this->connection->error;
        }
        else
        {
            $this->lasterror=$msg;
        }
    }

};
?>