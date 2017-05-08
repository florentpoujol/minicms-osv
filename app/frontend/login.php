<?php
if ($isLoggedIn) {
    redirect();
}

$currentPage["title"] = "Login";
require_once "../app/frontend/header.php";

if ($action === null) {
    $loginName = "";
    if (isset($_POST["login_name"])) {
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
                        redirect("admin");
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
        elseif (! $recaptchaOK) {
            addError("Please fill the captcha before submitting the form.");
        }
    }
?>

<h1>Login</h1>

<p>
    If you haven't registered yet <a href="<?php echo buildLink(null, "login"); ?>">click here</a>.
</p>

<?php include "../app/messages.php"; ?>

<form action="" method="POST">
    <label>Name : <input type="text" name="login_name" value="<?php echo $loginName; ?>" required></label> <br>
    <label>Password : <input type="password" name="login_password" required></label> <br>
<?php
if ($useRecaptcha) {
    require "../app/recaptchaWidget.php";
}
?>
    <input type="submit" value="Login">
</form>

<p>
    <a href="<?php echo buildLink(null, "login", "forgotpassword"); ?>">Forgot password ?</a>
</p>

<?php
}
// --------------------------------------------------

elseif ($action === "forgotpassword") {
    if (isset($_POST["forgot_password_email"])) {
        $email = $_POST["forgot_password_email"];

        $recaptchaOK = true;
        if ($useRecaptcha) {
            $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
        }

        if ($recaptchaOK && checkEmailFormat($email)) {
            $user = queryDB("SELECT id, email FROM users WHERE email=?", $email)->fetch();

            if ($isLoggedIn) {
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
        elseif (! $recaptchaOK) {
            addError("Please fill the captcha before submitting the form.");
        }
    }
?>

<h2>Forgot password ?</h2>

<?php include "../app/messages.php"; ?>

<p>If you forgot your password, you can fill the form below, we will send an email so that you can change your password.</p>
<form action="" method="POST">
    <label>Email : <input type="email" name="forgot_password_email" required></label> <br>
<?php
if ($useRecaptcha) {
    require "../app/recaptchaWidget.php";
}
?>
    <input type="submit" value="Request password change">
</form>

<?php
}
else {
    redirect();
}
