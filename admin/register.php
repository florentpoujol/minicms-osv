<?php
if (isset($db) === false) {
    exit;
}

$title = "Register";
require_once "header.php";

$errorMsg = "";
$addedUser = [
    "name" => "",
    "email" => "",
];

if (isset($_POST["name"]) && isset($_POST["password"]) && isset($_POST["password_confirm"]) && isset($_POST["email"])) {
    $addedUser["name"] = $_POST["name"];
    $addedUser["email"] = $_POST["email"];
    $addedUser["password"] = $_POST["password"];
    $addedUser["password_confirm"] = $_POST["password_confirm"];

    $errorMsg = checkNewUserData($addedUser);

    if ($errorMsg === "") {
        // OK no error, let's add the user

        $role = "commenter";
        $user = queryDB("SELECT * FROM users")->fetch();
        if ($user === false) {
            // the first user gets to be admin
            $role = "admin";
        }

        $emailConfirmToken = md5(microtime(true)+mt_rand());

        $query = $db->prepare('INSERT INTO users(name, email, password_hash, role, creation_date) VALUES(:name, :email, :password_hash, :role, :creation_date)');
        $success = $query->execute([
            "name" => $addedUser["name"],
            "email" => $addedUser["email"],
            "email_confirm_token" => $emailConfirmToken,
            "password_hash" => password_hash($addedUser['password'], PASSWORD_DEFAULT),
            "role" => $role,
            "creation_date" => date("Y-m-d")
        ]);

        if ($success) {
            if ($sendConfirmEmail($addedUser['email'], $emailConfirmToken)) {
                $infosMsg = "You have successfully been registered. You need to activate your account by clicking the link that has been sent to your email adress";
                redirect(["page" => "login", $infoMsg = $infoMsg]);
            }
            else {
                $errorMsg .= "There has been an error sending the confirmation email, please try again";
            }
        }
        else {
            $errorMsg .= "There was an error regsitering the user. \n";
        }
    }
}
?>

<?php require_once "admin/messages-template.php" ?>

<form action="" method="POST">
    <label>Name : <input type="text" name="name" value="<?php echo $addedUser['name']; ?>" required></label> <br>
    <label>Email : <input type="text" name="email" value="<?php echo $addedUser['email']; ?>" required></label> <br>
    <label>Password : <input type="password" name="password" required></label> <br>
    <label>Verify Password : <input type="password" name="password_confirm" required></label> <br>
    <br>
    <input type="submit" value="Register">
</form>
