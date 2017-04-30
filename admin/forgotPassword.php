<?php
if (isset($db) === false) {
    exit;
}

$title = "Change Password";
require_once "header.php";

if (isset($_GET["email"]) && isset($_GET["token"])) {
    $email = $_GET["email"];
    $token = $_GET["token"];

    $user = queryDB("SELECT password_token, password_change_time  FROM users WHERE email=?", $email)->fetch();
    if ($user === false) {
        exit("No user with email $email");
    }

    if ($token !== "" && $token === $user["password_token"] && time() < $user["password_change_time"] + 3600*48) {
        // process the change of password when user has forgotten it

        if (isset($_POST["new_password"])) {
            $errorMsg = checkPasswordFormat($_POST["new_password"], $_POST["new_password_confirm"]);

            if ($errorMsg === "") {
                $success = queryDB(
                    "UPDATE users SET password_hash=:hash, password_token='', password_change_time=0 WHERE email=:email",
                    [
                        "email" => $email,
                        "hash" => password_hash($_POST["new_password"], PASSWORD_DEFAULT),
                    ]
                );

                if ($success) {
                    redirect([
                        "action" => "login",
                        "infoMsg" => "Password changed successfully ! You can now login again."
                    ]);
                }
                else {
                    $errorMsg = "There was an error changing the password.";
                }
            }
        }
?>

<h2>Change password</h2>

<?php include "messages-template.php"; ?>

<p>If you forgot your password, you can change it below.</p>
<form action="?action=forgotPassword&email=<?php echo $email; ?>&token=<?php echo $token; ?>" method="POST">
    <label>Password : <input type="password" name="new_password" required></label> <br>
    <label>Verify Password : <input type="password" name="new_password_confirm" required></label> <br>
    <!-- <input type="hidden" name="change_password_email" value="<?php echo $email; ?>"> -->
    <!-- <input type="hidden" name="password_token" value="<?php echo $token; ?>"> -->
    <input type="submit" value="Change password">
</form>

<?php
    }
    else {
        echo "Not authorised to change or link expired.";
    }
}
