<?php
$title = "Register";
require_once "header.php";

$useRecaptcha = ($config["recaptcha_secret"] !== "");


// process confirmation of email
if (isset($_GET["email"]) && isset($_GET["confirmtoken"])) {
    $email = $_GET["email"];
    checkEmailFormat($email);
    $user = queryDB("SELECT email_token FROM users WHERE email=?", $email)->fetch();

    if ($user === false) {
        $errorMsg .= "No user with that email.";
    }

    if ($_GET["confirmtoken"] !== $user["email_token"]) {
        $errorMsg .= "Can not confirm the user.";
    }

    if ($errorMsg === "") {
        $success = queryDB("UPDATE users SET email_token='' WHERE email=?", $email);

        if ($success) {
            $infoMsg = "Your email has been confirmed, you can now log in.";
        }
        else {
            $errorMsg .= "There has been an error confirming the email.";
        }
    }
}


// --------------------------------------------------

$newUser = [
    "name" => "",
    "email" => ""
];

if (isset($_POST["register"])) {
    $newUser["name"] = $_POST["register_name"];
    $newUser["email"] = $_POST["register_email"];
    $newUser["password"] = $_POST["register_password"];
    $newUser["password_confirm"] = $_POST["register_password_confirm"];

    $recaptchaOK = true;
    if ($useRecaptcha) {
        $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
    }

    if ($recaptchaOK && checkNewUserData($newUser)) {
        // OK no error, let's add the user

        $role = "commenter";
        $user = queryDB("SELECT * FROM users")->fetch();
        if ($user === false) {// the first user gets to be admin
            $role = "admin";
        }

        $emailToken = md5(microtime(true)+mt_rand());

        $success = queryDB(
            'INSERT INTO users(name, email, email_token, password_hash, role, creation_date) VALUES(:name, :email, :email_token, :password_hash, :role, :creation_date)',
            [
                "name" => $newUser["name"],
                "email" => $newUser["email"],
                "email_token" => $emailToken,
                "password_hash" => password_hash($newUser['password'], PASSWORD_DEFAULT),
                "role" => $role,
                "creation_date" => date("Y-m-d")
            ]
        );

        if ($success) {
            $erroMsg = sendConfirmEmail($newUser['email'], $emailToken);
            $infoMsg = "You have successfully been registered. You need to activate your account by clicking the link that has been sent to your email address";
        }
        else {
            $errorMsg .= "There was an error regsitering the user. \n";
        }
    }
}
?>

<h1>or Register</h1>

<?php include "messages-template.php"; ?>

<form action="" method="POST">
    <label>Name : <input type="text" name="register_name" value="<?php echo $newUser['name']; ?>" required></label> <br>
    <label>Email : <input type="email" name="register_email" value="<?php echo $newUser['email']; ?>" required></label> <br>
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
    addError(checkEmailFormat($email));
    $user = queryDB("SELECT email_token FROM users WHERE email=?", $email)->fetch();

    if ($user === false) {
        $errorMsg .= "No user with that email";
    }

    if ($user["email_token"] === "") {
        $errorMsg .= "No need to resend the confirmation email.";
    }

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
