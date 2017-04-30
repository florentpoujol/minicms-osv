<?php
$title = "Login";
require_once "header.php";

$useRecaptcha = ($config["recaptcha_secret"] !== "");

$loginName = "";
if (isset($_POST["login_name"])) {
// check that the fields are not empty
    $loginName = $_POST["login_name"];
    $password = $_POST["login_password"];

    $recaptchaOK = true;
    if ($useRecaptcha) {
        $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
    }

    if ($recaptchaOK && checkNameFormat($loginName) && checkPasswordFormat($password)) {
        // get the username and password from database for check
        $user = queryDB('SELECT * FROM users WHERE name = ?', $loginName)->fetch();

        if (is_array($user)) {
            if ($user["email_token"] === "") {
                if (password_verify($password, $user["password_hash"])) {
                    $_SESSION["minicms_vanilla_auth"] = $user["id"];
                    redirect();
                }
                else {
                    addError("Wrong password !");
                }
            }
            else {
                addError("This user is not activated yet. You need to click the link in the email that has been sent just after registration. You can send this email again below.");
            }
        }
        else {
            addError("No user by that name !");
        }
    }
}
?>

<h1>Login</h1>

<p>
    If you haven't registered yet <a href="admin/register">click here</a>.
</p>

<?php include "../../app/messages.php"; ?>

<form action="" method="POST">
    <label>Name : <input type="text" name="login_name" value="<?php echo $loginName; ?>" required></label> <br>
    <label>Password : <input type="password" name="login_password" required></label> <br>
<?php
if ($useRecaptcha) {
    require "../../app/recaptchaWidget.php";
}
?>
    <input type="submit" value="Login">
</form>

<?php
// --------------------------------------------------

if (isset($_POST["forgot_password_email"])) {
    $email = $_POST["forgot_password_email"];

    $recaptchaOK = true;
    if ($useRecaptcha) {
        $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
    }

    if ($recaptchaOK && checkEmailFormat($email)) {
        $user = queryDB("SELECT id, email FROM users WHERE email=?", $email)->fetch();

        if (is_array($user)) {
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
                sendChangePasswordEmail($email, $user["id"], $token);
                addSuccess("An email has been sent to this address. Click the link within 48 hours.");
            }
        }
        else {
            addError("No users has that email.");
        }
    }
}
?>

<h2>Forgot password ?</h2>

<?php include "../../app/messages.php"; ?>

<p>If you forgot your password, you can fill the form below, we will send an email so that you can change your password.</p>
<form action="" method="POST">
    <label>Email : <input type="email" name="forgot_password_email" required></label> <br>
<?php
if ($useRecaptcha) {
    require "../../app/recaptchaWidget.php";
}
?>
    <input type="submit" value="Request password change">
</form>
