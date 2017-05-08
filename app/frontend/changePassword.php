<?php
if ($isLoggedIn) {
    redirect();
}

$currentPage["title"] = "Change password";
require_once "../app/frontend/header.php";

if (isset($_GET["token"])) {
    $token = $_GET["token"];

    $user = queryDB("SELECT password_change_time FROM users WHERE id = ? AND password_token = ?", [$resourceId, $token])->fetch();

    if (is_array($user) && time() < $user["password_change_time"] + 3600*48) {
        // process the change of password when user has forgotten it

        if (isset($_POST["new_password"])) {
            if (checkPasswordFormat($_POST["new_password"], $_POST["new_password_confirm"])) {
                $success = queryDB(
                    "UPDATE users SET password_hash = ?, password_token = '', password_change_time = 0 WHERE id = ?",
                    [
                        password_hash($_POST["new_password"], PASSWORD_DEFAULT),
                        $resourceId
                    ]
                );

                if ($success) {
                    addSuccess("Password changed successfully ! You can now login again.");
                    redirect(null, "login");
                }
                else {
                    addError("There was an error changing the password.");
                }
            }
        }
?>

<h1>Change password</h1>

<?php include "../app/messages.php"; ?>

<p>If you forgot your password, you can change it below.</p>
<form action="" method="POST">
    <label>Password : <input type="password" name="new_password" required></label> <br>
    <label>Verify Password : <input type="password" name="new_password_confirm" required></label> <br>
    <input type="submit" value="Change password">
</form>

<?php
    }
    else {
        echo "Not authorised to change or link expired.";
    }
}
else {
    redirect();
}
