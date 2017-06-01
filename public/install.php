<?php
if (file_exists("../app/config.json")) {
    header("Location: index.php");
    exit;
}

if (! is_readable("../app/config.sample.json")) {
    echo "Your installation seems to miss the 'app/config.sample.json' file or it is not readable.";
    exit;
}

if (! is_readable("../app/database.sample.sql")) {
    echo "Your installation seems to miss the 'app/database.sample.sql' file or it is not readable.";
    exit;
}

if (! is_writable("../app")) {
    echo "The folder 'app/' does not seems to be writable.";
    exit;
}

require_once "../app/functions.php";

$str = file_get_contents("../app/config.sample.json");
$defaultConfig = json_decode($str, true);

$install = [
    "config" => $defaultConfig,
    "user_name" => "Florent",
    "user_email" => "flo@flo.fr",
    "user_password" => "aZ1",
];

if (isset($_POST["user_name"])) {
    $install = array_merge($install, $_POST);
    $install["config"] = array_merge($defaultConfig, $_POST["config"]);

    $ok = checkNameFormat($_POST["user_name"]);
    $ok = checkEmailFormat($_POST["user_email"]) && $ok;
    $ok = checkPasswordFormat($_POST["user_password"], $_POST["user_password_confirm"]) && $ok;

    if ($ok) {
        // things to do in order :
        // test connection to db
        // create DB if not exist
        // read sql file
        // create table if not exists
        // populate config and user
        // create config file

        $db = null;
        try {
            $db = new PDO(
                "mysql:host=".$install["config"]["db_host"].";charset=utf8", $install["config"]["db_user"], $install["config"]["db_password"],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // PDO::ATTR_EMULATE_PREPARES => false, // this causes a "General error: 2014" when uncommented
                ]
            );
        }
        catch (Exception $e) {
            $ok = false;
            addError("Error connecting to the database. Probably wrong host, username or password.");
            addError($e->getMessage());
        }

        if ($ok) {
            $dbName = $install["config"]["db_name"];

            if (preg_match("/^[a-zA-Z0-9_-]{2,}$/", $dbName) === 1) {
                $DBCreated = $db->query("CREATE DATABASE IF NOT EXISTS `$dbName`");

                if ($DBCreated) {
                    $db->query("use `$dbName`");

                    $sql = file_get_contents("../app/database.sample.sql");

                    if (is_string($sql)) {
                        $query = $db->prepare($sql);
                        $tablesCreated = $query->execute();

                        if ($tablesCreated) {
                            $query = $db->prepare(
                                "INSERT INTO
                                users(name, email, password_hash, role, creation_date)
                                VALUES(:name, :email, :hash, :role, :date)"
                            );
                            $params = [
                                "name" => $install["user_name"],
                                "email" => $install["user_email"],
                                "hash" => password_hash($install["user_password"], PASSWORD_DEFAULT),
                                "role" => "admin",
                                "date" => date("Y-m-d")
                            ];
                            
                            $userSuccess = $query->execute($params);


                            $defaultMenu = [[
                                "type" => "external",
                                "name" => "Login",
                                "target" => "?p=login",
                                "children" => []
                            ]];
                            $query = $db->prepare(
                                "INSERT INTO menus(name, in_use, structure)
                                VALUES(:name, :in_use, :structure)"
                            );
                            $params = [
                                "name" => "DefaultMenu",
                                "in_use" => 1,
                                "structure" => json_encode($defaultMenu, JSON_PRETTY_PRINT)
                            ];

                            $menuSuccess = $query->execute($params);


                            if ($userSuccess && $menuSuccess) {
                                $str = json_encode($install["config"], JSON_PRETTY_PRINT);
                                if (file_put_contents("../app/config.json",  $str)) {
                                    addSuccess("Congratulation, the site is now installed, ou can login to start creating content. Take a look at the config page for more configuration options.");
                                    header("index.php?f=admin&p=config");
                                    exit;
                                }
                                else {
                                    addError("Error writing the 'app/config.json' file");
                                }
                            }
                            else {
                                addError("Error populating the database");
                            }
                        }
                        else {
                            addError("Error creating tables in database.");
                        }
                    }
                    else {
                        addError("Error reading the 'app/database.sample.sql' file");
                    }
                }
                else {
                    addError("Error creating the database.");
                }
            }
            else {
                addError("The database name ahs the wrong format");
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>MiniCMS Vanilla Installer</title>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" type="text/css" href="common.css">
</head>
<body>
    <p>
        You	are about to install MINICMS Vanilla. <br>
        Please fill the fields below <br>
    </p>

    <?php require_once "../app/messages.php"; ?>

    <form action="" method="POST">
        <fieldset>
            <legend>Website</legend>

            <label> Site Title: <input type="text" name="config[site_title]" value="<?php safeEcho($install["config"]["site_title"]); ?>" required></label> <br>

            <p>After installation, you will be able to go to the Config page to see more config settings.</p>
        </fieldset>

        <fieldset>
            <legend>Database</legend>

            <label>Host: <input type="text" name="config[db_host]" value="<?php safeEcho($install["config"]["db_host"]); ?>" required></label> <br>
            <label>User: <input type="text" name="config[db_user]" value="<?php safeEcho($install["config"]["db_user"]); ?>" required></label> <br>
            <label>Password: <input type="password" name="config[db_password]" value="" required></label> <br>
            <label>DB Name: <input type="text" name="config[db_name]" value="<?php safeEcho($install["config"]["db_name"]); ?>" required></label> <br>
        </fieldset>

        <fieldset>
            <legend>Admin user</legend>

            <label>Username: <input type="text" name="user_name" value="<?php safeEcho($install["user_name"]); ?>" required></label> <br>
            <label>Email: <input type="email" name="user_email" value="<?php safeEcho($install["user_email"]); ?>" required></label> <br>
            <label>password: <input type="password" name="user_password" required></label> <br>
            <label>password confirm: <input type="password" name="user_password_confirm" required></label> <br>
        </fieldset>

        <input type="submit" value="Install">
    </form>
</body>
</html>