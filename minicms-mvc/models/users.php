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

  public static function updatePasswordToken($user_id, $token) {
    $query = self::$db->prepare("UPDATE users SET password_token=:token, password_change_time=:time WHERE id=:id");
    return $query->execute([
      "id" => $user_id,
      "token" => $token,
      "time" => time()
    ]);
  }
}