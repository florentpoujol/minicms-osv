<?php

if ($user['isLoggedIn']) {
    redirect();
    return;
}

if (! $config["allow_registration"]) {
    addError("Registration is disabled");
    redirect();
    return;
}

$pageContent["title"] = "Register";
require_once __dir__ . "/header.php";

$newUser = [
    "name" => "",
    "email" => ""
];

if ($query['action'] === '') {
    if (isset($_POST["register_name"])) {
        $newUser["name"] = $_POST["register_name"];
        $newUser["email"] = $_POST["register_email"];
        $newUser["password"] = $_POST["register_password"];
        $newUser["password_confirm"] = $_POST["register_password_confirm"];

        if (verifyCSRFToken($_POST["csrf_token"], "register")) {
            $recaptchaOK = true;
            if ($config['useRecaptcha']) {
                $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
            }

            if ($recaptchaOK && checkNewUserData($newUser)) {
                $role = "commenter";
                $user = queryDB("SELECT * FROM users")->fetch();
                if ($user === false) {
                    addError('No user exists, there must have been something wrong during installation');
                    // the first user is created during the install process
                    // if there is none, something went wrong during install
                    rename(__dir__ . "/../config.json", __dir__ . "/../config.json.old");
                    redirect();
                    return;
                }

                $emailToken = getRandomString();
                $success = queryDB(
                    'INSERT INTO users(name, email, email_token, password_hash, password_token, password_change_time, role, creation_date, is_banned) VALUES(:name, :email, :email_token, :password_hash, :password_token, :password_change_time, :role, :creation_date, :is_banned)',
                    [
                        "name" => $newUser["name"],
                        "email" => $newUser["email"],
                        "email_token" => $emailToken,
                        "password_hash" => password_hash($newUser['password'], PASSWORD_DEFAULT),
                        "password_token" => "",
                        "password_change_time" => 0,
                        "role" => $role,
                        "creation_date" => date("Y-m-d"),
                        "is_banned" => 0,
                    ]
                );

                if ($success) {
                    $id = (int)$db->lastInsertId();
                    $newUser = queryDB("SELECT * FROM users WHERE id = '$id'")->fetch();
                    sendConfirmEmail($newUser["email"], $id, $newUser["email_token"]);
                    addSuccess("You have successfully been registered. You need to activate your account by clicking the link that has been sent to your email address");
                } else {
                    addError("There was an error registering the user.");
                }
            } elseif (! $recaptchaOK) {
                addError("Please fill the captcha before submitting the form.");
            }
        }
    }
?>

<h1>Register</h1>

<?php require __dir__ . "/../messages.php"; ?>

<form action="" method="POST">
    <label>Name : <input type="text" name="register_name" value="<?= $newUser['name']; ?>" required></label> <br>
    <label>Email : <input type="email" name="register_email" value="<?= $newUser['email']; ?>" required></label> <br>
    <label>Password : <input type="password" name="register_password" required></label> <br>
    <label>Verify Password : <input type="password" name="register_password_confirm" required></label> <br>
<?php
if ($config['useRecaptcha']) {
    require __dir__ . "/../recaptchaWidget.php";
}

addCSRFFormField("register");
?>
    <input type="submit" value="Register">
</form>

<p>
    I want to <a href="<?= buildUrl("register", "resendconfirmation"); ?>">receive the confirmation email</a> again.
</p>

<?php
}

// --------------------------------------------------

elseif ($query['action'] === "resendconfirmation") {
    if (isset($_POST["confirm_email"])) {
        $email = $_POST["confirm_email"];

        if (verifyCSRFToken($_POST["csrf_token"], "resendconfirmation")) {

            $recaptchaOK = true;
            if ($config['useRecaptcha']) {
                $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
            }

            if ($recaptchaOK && checkEmailFormat($email)) {
                $resendEmail = true;

                $user = queryDB("SELECT id, email_token FROM users WHERE email = ?", $email)->fetch();
                if ($user === false) {
                    addError("No user with that email");
                    $resendEmail = false;
                }

                if ($user["email_token"] === "") {
                    addError("No need to resend the confirmation email.");
                    $resendEmail = false;
                }

                if ($resendEmail) {
                    if (sendConfirmEmail($email, $user["id"], $user["email_token"])) {
                        addSuccess("Confirmation email has been sent again.");
                    }
                }
            }
            elseif (! $recaptchaOK) {
                addError("Please fill the captcha before submitting the form.");
            }
        }
    }

?>

<h2>Send confirmation email again</h2>

<?php require __dir__ . "/../messages.php"; ?>

<p>Fill the form below so that yu can receive the confirmation email again.</p>
<form action="" method="POST">
    <label>Email : <input type="email" name="confirm_email" required></label> <br>
    <?php addCSRFFormField("resendconfirmation"); ?>
    <input type="submit" value="Resend the email">
</form>

<?php
}

elseif ($query['action'] === "confirmemail") {
    $token = $query['token'];
    if (checkToken($token)) {
        $id = $query['id'];
        $user = queryDB("SELECT email_token FROM users WHERE id = ? AND email_token = ?", [$id, $token])->fetch();

        if (is_array($user)) {
            $success = queryDB("UPDATE users SET email_token = '' WHERE id = ?", $id);

            if ($success) {
                addSuccess("Your email has been confirmed, you can now log in.");
                redirect("login");
                return;
            } else {
                addError("There has been an error confirming the email.");
            }
        } else {
            addError('No user match that id and token.');
        }
    }
}

else {
    addError("Bad action");
}

require __dir__ . "/../messages.php";
