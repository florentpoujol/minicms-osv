<?php
// this file receive the POST request sent from the login form
// the form has two fields : login_name and login_password

session_start();

require_once "functions.php";

$errorMsg = "";

if (isset($_POST["login_name"]) && isset($_POST["login_password"])) {
  // check that the fields are not empty
  $name = $_POST["login_name"];
  $password = $_POST["login_password"];

  if (strlen($name) === 0 || strlen($password) === 0)
    $errorMsg = "The name or password is empty !";

  else {
    // get the username and password from database for check
    require_once "database.php";

    $query = $db->prepare('SELECT * FROM users WHERE name = :name');
    $query->execute(['name' => $name]);
    $user = $query->fetch(); // same as  fetch(PDO::FETCH_ASSOC);  because of the PDO::ATTR_DEFAULT_FETCH_MODE option set when creating the connection

    if ($user === false)
      $errorMsg = "No user by that name !";

    else {
      // user has been found by name
      // let's check the password

      if (password_verify($password, $user["password_hash"])) {
        // OK correct password
        $_SESSION["minicms_handmade_auth"] = $user["id"];
        redirect();
      }
      else
        $errorMsg = "Wrong password !";
    }
  }
}

if (isset($_POST["logout"])) // the form is in the menu
  logout();

?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <meta charset="utf-8">
</head>
<body>

  <?php require_once "messages-template.php" ?>

  <form action="login.php" method="POST">
    <label>Name : <input type="text" name="login_name" required></label> <br>
    <label>Password : <input type="password" name="login_password" required></label> <br>
    <input type="submit" value="Login">
  </form>
</body>
</html>