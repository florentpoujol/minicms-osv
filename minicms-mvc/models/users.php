<?php

class Users extends Model {

  public static function get($params) {
    $strQuery = "SELECT * FROM users WHERE ";
    foreach ($params as $name => $value)
      $strQuery .= "$name=:$name AND ";
    $strQuery = rtrim($strQuery, " AND ");
    
    $query = self::$db->prepare($strQuery);
    $success = $query->execute($params);

    if ($success === true)
      return $query->fetch();
    else
      return false;
  }
}