<?php
if (isset($db) === false) exit();

$title = "Login or Register";
require_once "header.php";


// process confirmation of email
if (isset($_GET["email"]) && isset($_GET["confirmtoken"])) {
  $email = $_GET["email"];
  $errorMsg = checkEmailFormat($email);
  $user = queryDB("SELECT email_token FROM users WHERE email=?", $email)->fetch();

  if ($user === false)
    $errorMsg .= "No user with that email.";

  if ($_GET["confirmtoken"] !== $user["email_token"])
    $errorMsg .= "Can not confirm the user.";

  if ($errorMsg === "") {
    $success = queryDB("UPDATE users SET email_token='' WHERE email=?", $email);

    if ($success)
      $infoMsg = "Your email has been confirmed, you can now log in.";
    else
      $errorMsg .= "There has been an error confirming the email.";
  }
}



// --------------------------------------------------
// login

$loginName = "";
if (isset($_POST["login"])) {
  // check that the fields are not empty
  $loginName = $_POST["login_name"];
  $password = $_POST["login_password"];
  $recaptcha_response = $_POST["g-recaptcha-response"];

  if (strlen($loginName) === 0 || strlen($password) === 0)
    $errorMsg = "The name or password is empty !";

  elseif (verifyRecaptcha($recaptcha_response) === true) {
    
    // get the username and password from database for check

    $user = queryDB('SELECT * FROM users WHERE name = ?', $loginName)->fetch();
    // ->fetch() is the same as  fetch(PDO::FETCH_ASSOC);  because of the PDO::ATTR_DEFAULT_FETCH_MODE option set when creating the connection

    if ($user === false)
      $errorMsg = "No user by that name !";

    else {
      // user has been found by name
      // first check that the user is activated
      if ($user["email_token"] !== "") {
        $errorMsg = "This user is not activated yet. You need to click the link in the email that has been sent just after registration. You can send this email again below.";
      }
      else {
        // then let's check the password
        if (password_verify($password, $user["password_hash"])) {
          // OK correct password
          $_SESSION["minicms_handmade_auth"] = $user["id"];
          redirect(); // to index
        }
        else
          $errorMsg = "Wrong password !";
      }
    }
  }
}

?>

  <h1>Login</h1>

  <?php include "messages-template.php"; ?>

  <form action="" method="POST">
    <label>Name : <input type="text" name="login_name" value="<?php echo $loginName; ?>" required></label> <br>
    <label>Password : <input type="password" name="login_password" required></label> <br>
    <?php require "../admin/recaptchaWidget.php"; ?>
    <input type="submit" name="login" value="Login">
  </form>




<?php
// --------------------------------------------------
// forgot password

if (isset($_POST["forgot_password_email"])) {
  $email = $_POST["forgot_password_email"];
  $errorMsg = checkEmailFormat($email);

  if ($errorMsg === "") {
    $user = queryDB("SELECT email FROM users WHERE email=?", $email)->fetch();

    if ($user !== false) {
      $token = md5(microtime(true)+mt_rand());
      $success = queryDB(
        'UPDATE users SET password_token=:token, password_change_time=:time WHERE email=:email',
        [
          "email" => $email,
          "token" => $token,
          "time" => time()
        ]
      );

      if ($success) {
        sendChangePasswordEmail($email, $token);
        $infoMsg = "An email has been sent to this address. Click the link within 48 hours.";
      }
    }
    else
      $errorMsg = "No users has that email.";
  }
}
?>

  <h2>Forgot password ?</h2>

  <?php include "messages-template.php"; ?>

  <p>If you forgot your password, you can fill the form below, we will send an email so that you can change your password.</p>
  <form action="" method="POST">
    <label>Email : <input type="email" name="forgot_password_email" required></label> <br>
    <input type="submit" value="Request password change">
  </form>




<?php
// --------------------------------------------------
// regsiter
$addedUser = [
  "name" => "",
  "email" => ""
];

if (isset($_POST["register"])) {
  $addedUser["name"] = $_POST["register_name"];
  $addedUser["email"] = $_POST["register_email"];
  $addedUser["password"] = $_POST["register_password"];
  $addedUser["password_confirm"] = $_POST["register_password_confirm"];

  $errorMsg = checkNewUserData($addedUser);

  if ($errorMsg === "") {
    // OK no error, let's add the user

    $role = "commenter";
    $user = queryDB("SELECT * FROM users")->fetch();
    if ($user === false) // the first user gets to be admin
      $role = "admin";

    $emailToken = md5(microtime(true)+mt_rand());

    $success = queryDB(
      'INSERT INTO users(name, email, email_token, password_hash, role, creation_date) VALUES(:name, :email, :email_token, :password_hash, :role, :creation_date)',
      [
        "name" => $addedUser["name"],
        "email" => $addedUser["email"],
        "email_token" => $emailToken,
        "password_hash" => password_hash($addedUser['password'], PASSWORD_DEFAULT),
        "role" => $role,
        "creation_date" => date("Y-m-d")
      ]
    );

    if ($success) {
      $erroMsg = sendConfirmEmail($addedUser['email'], $emailToken);
      $infoMsg = "You have successfully been registered. You need to activate your account by clicking the link that has been sent to your email address";
    }
    else
      $errorMsg .= "There was an error regsitering the user. \n";
  }
}

?>

  <h1>or Register</h1>

  <?php include "messages-template.php"; ?>

  <form action="" method="POST">
    <label>Name : <input type="text" name="register_name" value="<?php echo $addedUser['name']; ?>" required></label> <br>
    <label>Email : <input type="email" name="register_email" value="<?php echo $addedUser['email']; ?>" required></label> <br>
    <label>Password : <input type="password" name="register_password" required></label> <br>
    <label>Verify Password : <input type="password" name="register_password_confirm" required></label> <br>
    <br>
    <input type="submit" name="register" value="Register">
  </form>




<?php
// --------------------------------------------------
// resend confirm email

if (isset($_POST["confirm_email"])) {
  $email = $_POST["confirm_email"];
  $errorMsg = checkEmailFormat($email);
  $user = queryDB("SELECT email_token FROM users WHERE email=?", $email)->fetch();

  if ($user === false)
    $errorMsg .= "No user with that email";

  if ($user["email_token"] === "")
    $errorMsg .= "No need to resend the confirmation email.";

  if ($errorMsg === "") {
    sendConfirmEmail($email, $user["email_token"]);
    $infoMsg = "Confirmation email has been sent again.";
  }
}
?>

  <h2>Send confirmation email again</h2>

  <?php include "messages-template.php"; ?>

  <p>Fill the form below so that yu can receive the confirmation email again.</p>
  <form action="" method="POST">
    <label>Email : <input type="email" name="confirm_email" required></label> <br>
    <input type="submit" value="Resend the email">
  </form>