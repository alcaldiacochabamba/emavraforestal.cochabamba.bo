<?php

class DB{
    
  public $pdo = null;
  
    function __construct(){

        $this->pdo = new PDO(
            "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET, 
            DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            

    }
    
    


    
    
    
}
define("DB_HOST", "localhost");
define("DB_NAME", "reforest");
define("DB_CHARSET", "utf8mb4");
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("DB_PORT", 3306);
$_DB = new DB();



// Database settings



/*
$results = $_DB->select(
    "SELECT * FROM `users`",
  );
  
  echo json_encode(count($results)==0 ? null : $results);
  */
?>