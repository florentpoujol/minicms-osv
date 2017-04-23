<?php

class Model {

  // holds the PDO instance
  protected static $db; 

  function __construct() {
    // if (isset(self::$db) === false)
    //   $this->connect();
  }

  // creates the connection to the database
  public static function connect() {
    $host = "localhost";
    $name = "minicms_mvc";
    $user = "root";
    $password = "root";
  
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
      self::$db = new PDO("mysql:host=$host;dbname=$name;charset=utf8", $user, $password, $options);
    }
    catch (Exception $e) {
      echo "error connecting to the database <br>";
      echo $e->getMessage();
      exit();
    }
  }
}