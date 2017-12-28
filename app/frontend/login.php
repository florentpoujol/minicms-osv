<?php

if ($user['isLoggedIn']) {
    redirect($config['admin_section_name']);
    return;
}

$currentPage["title"] = "Login";
require_once "../app/frontend/header.php";

if ($query['action'] === '') {
    $loginName = "";
    if (isset($_POST["login_name"])) {
        $loginName = $_POST["login_name"];
        $password = $_POST["login_password"];

        if (verifyCSRFToken($_POST["csrf_token"], "login")) {
            $recaptchaOK = true;
            if ($config['useRecaptcha']) {
                $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
            }

            if ($recaptchaOK && checkNameFormat($loginName) && checkPasswordFormat($password)) {
                // get the username and password from database for check
                $user = queryDB('SELECT * FROM users WHERE name = ?', $loginName)->fetch();

                if (is_array($user)) {
                    if ($user["email_token"] === "") {
                        if (password_verify($password, $user["password_hash"])) {
                            session_destroy();
                            session_start();
                            $_SESSION["user_id"] = $user["id"];
                            redirect($config['admin_section_name']);
                            return;
                        } else {
                            addError("Wrong password !");
                        }
                    } else {
                        addError("This user is not activated yet. You need to click the link in the email that has been sent just after registration. You can send this email again below.");
                    }
                } else {
                    addError("No user by that name !");
                }
            } elseif (! $recaptchaOK) {
                addError("Please fill the captcha before submitting the form.");
            }
        }
    }
?>

<h1>Login</h1>

<?php if ($config["allow_registration"]): ?>
<p>
    If you haven't registered yet <a href="<?= buildUrl("register"); ?>">click here</a>.
</p>
<?php endif; ?>

<?php include "../app/messages.php"; ?>

<form action="" method="POST">
    <label>Name : <input type="text" name="login_name" value="<?= $loginName; ?>" required></label> <br>
    <label>Password : <input type="password" name="login_password" required></label> <br>
<?php
if ($config['useRecaptcha']) {
    require "../app/recaptchaWidget.php";
}

addCSRFFormField("login");
?>
    <input type="submit" value="Login">
</form>

<p>
    <a href="<?= buildUrl("login", "forgotpassword"); ?>">Forgot password ?</a>
</p>

<?php
}
// --------------------------------------------------

elseif ($query['action'] === "forgotpassword") {
    if (isset($_POST["forgot_password_email"])) {
        $email = $_POST["forgot_password_email"];

        if (verifyCSRFToken($_POST["csrf_token"], "forgotpassword")) {
            $recaptchaOK = true;
            if ($config['useRecaptcha']) {
                $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
            }

            if ($recaptchaOK && checkEmailFormat($email)) {
                $user = queryDB("SELECT id, email FROM users WHERE email = ?", $email)->fetch();

                if (is_array($user)) {
                    $token = getRandomString();
                    $success = queryDB(
                        'UPDATE users SET password_token = :token, password_change_time =  :time WHERE email = :email',
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
                } else {
                    addError("No users has that email.");
                }
            } elseif (! $recaptchaOK) {
                addError("Please fill the captcha before submitting the form.");
            }
        }
    }
?>

<h2>Forgot password ?</h2>

<?php include "../app/messages.php"; ?>

<p>If you forgot your password, you can fill the form below, we will send an email so that you can change your password.</p>
<form action="" method="POST">
    <label>Email : <input type="email" name="forgot_password_email" required></label> <br>
<?php
if ($config['useRecaptcha']) {
    require "../app/recaptchaWidget.php";
}

addCSRFFormField("forgotpassword");
?>
    <input type="submit" value="Request password change">
</form>


<?php
}

elseif ($query['action'] === "changepassword") {
    $token = $query["token"];

    if (checkToken($token)) {
        $user = queryDB("SELECT password_change_time FROM users WHERE id = ? AND password_token = ?", [$query['id'], $token])->fetch();

        if (is_array($user) && time() < $user["password_change_time"] + 3600*48) {
            // process the change of password when user has forgotten it

            if (isset($_POST["new_password"])) { // the user has filled the form to change the email
                if (verifyCSRFToken($_POST["csrf_token"], "changepassword")) {

                    $newPassword = $_POST["new_password"];
                    if (checkPasswordFormat($newPassword, $_POST["new_password_confirm"])) {
                        $success = queryDB(
                            "UPDATE users SET password_hash = ?, password_token = '', password_change_time = 0 WHERE id = ?",
                            [
                                password_hash($newPassword, PASSWORD_DEFAULT),
                                $query['id']
                            ]
                        );

                        if ($success) {
                            addSuccess("Password changed successfully ! You can now login again.");
                            redirect("login");
                            return;
                        } else {
                            addError("There was an error changing the password.");
                        }
                    }
                }
            }
        } else {
            header("HTTP/1.0 403 Forbidden");
            addError('Unknow user or token expired. Please ask again for a new password then follow the link in the email you will receive.');
            redirect('login', 'forgotpassword');
            return;
        }
    } else {
        header("HTTP/1.0 403 Forbidden");
        addError('The token has the wrong format. Please ask again for a new password then follow the link in the email you will receive.');
        redirect('login', 'forgotpassword');
        return;
    }
?>

<h1>Change password</h1>

<?php include "../app/messages.php"; ?>

<p>If you forgot your password, you can change it below.</p>
<form action="" method="POST">
    <label>Password : <input type="password" name="new_password" required></label> <br>
    <label>Verify Password : <input type="password" name="new_password_confirm" required></label> <br>
    <?php addCSRFFormField("changepassword"); ?>
    <input type="submit" value="Change password">
</form>

<?php
}

else {
    // unknow action for login page
    redirect();
    return;
}
