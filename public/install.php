<?php
$appFolder = __dir__ . "/../app";

if (!defined("IS_TEST")) {
    define("IS_TEST", false);
}

if (!IS_TEST && file_exists("$appFolder/config.json")) {
    header("Location: index.php");
    exit;
}

$filesAreOk = true;
if (!is_readable("$appFolder/config.sample.json")) {
    echo "Error: Your installation seems to miss the 'app/config.sample.json' file or it is not readable. \n";
    $filesAreOk = false;
}

if (!is_readable("$appFolder/database.sample.sql")) {
    echo "Error: Your installation seems to miss the 'app/database.sample.sql' file or it is not readable.\n";
    $filesAreOk = false;
}

if (!is_writable($appFolder)) {
    echo "Error: The folder 'app/' does not seems to be writable.\n";
    $filesAreOk = false;
}

if (!$filesAreOk) {
    exit;
}

require_once "$appFolder/functions.php";

$defaultConfigJson = file_get_contents("$appFolder/config.sample.json");
$defaultConfig = json_decode($defaultConfigJson, true);

$install = [
    "config" => $defaultConfig,
    "user_name" => "",
    "user_email" => "",
    "user_password" => "",
];

if (isset($_POST["user_name"])) {
    $install = array_merge($install, $_POST);
    $install["config"] = array_merge($defaultConfig, $_POST["config"]);

    $isFormOk = checkNameFormat($_POST["user_name"]);
    $isFormOk = checkEmailFormat($_POST["user_email"]) && $isFormOk;
    $isFormOk = checkPasswordFormat($_POST["user_password"], $_POST["user_password_confirm"]) && $isFormOk;

    if ($isFormOk) {
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
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (Exception $e) {
            $db = null;
            addError("Error connecting to the database. Probably wrong host, username or password.");
            addError($e->getMessage());
        }

        if ($db !== null) {
            $dbName = $install["config"]["db_name"];

            if (preg_match("/^[a-zA-Z0-9_-]{2,}$/", $dbName) === 1) {
                $db->exec("DROP DATABASE IF EXISTS `$dbName`");
                $DBCreated = $db->exec("CREATE DATABASE `$dbName`");

                if ($DBCreated) {
                    $db->exec("use `$dbName`");

                    $sql = file_get_contents("$appFolder/database.sample.sql");

                    if (is_string($sql)) {
                        $schemaCreated = $db->exec($sql); // wil be 0 on success, false on error

                        if ($schemaCreated !== false) {
                            $query = $db->prepare(
                                "INSERT INTO
                                users(name, email, email_token, password_hash, password_token, password_change_time, role, creation_date, is_banned)
                                VALUES(:name, :email, '', :hash, '', 0, :role, :date, 0)"
                            );
                            $params = [
                                "name" => $install["user_name"],
                                "email" => $install["user_email"],
                                "hash" => password_hash($install["user_password"], PASSWORD_DEFAULT),
                                "role" => "admin",
                                "date" => date("Y-m-d")
                            ];
                            $userSuccess = $query->execute($params);

                            // also add a default menu
                            $defaultMenu = [
                                // the nested array is normal
                                [
                                    "type" => "external",
                                    "name" => "Login",
                                    "target" => "?section=login",
                                    "children" => []
                                ]
                            ];
                            $query = $db->prepare(
                                "INSERT INTO menus(name, in_use, structure)
                                VALUES(:name, 1, :structure)"
                            );
                            $params = [
                                "name" => "DefaultMenu",
                                "structure" => json_encode($defaultMenu, JSON_PRETTY_PRINT)
                            ];
                            $menuSuccess = $query->execute($params);

                            if ($userSuccess && $menuSuccess) {
                                $configFilePath = "$appFolder/config.json";
                                if (IS_TEST) {
                                    $configFilePath .= ".testinstall";
                                }
                                $configJson = json_encode($install["config"], JSON_PRETTY_PRINT);

                                if (file_put_contents($configFilePath, $configJson)) {
                                    addSuccess("Congratulation, the site is now installed, you can login and start creating content. Take a look at the config page for more configuration options.");
                                    redirect("login");
                                    return;
                                } else {
                                    addError("Error writing the 'app/config.json' file.");
                                }
                            } else {
                                addError("Error populating the database.");
                            }
                        } else {
                            addError("Error creating tables in the database.");
                        }
                    } else {
                        addError("Error reading the 'app/database.sample.sql' file");
                    }
                } else {
                    addError("Error creating the database.");
                }
            } else {
                addError("The database name has the wrong format.");
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>MiniCMS OSV Installer</title>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" type="text/css" href="common.css">
</head>
<body>
    <p>
        You	are about to install MINICMS OSV. <br>
        Please fill the fields below: <br>
    </p>

    <?php require_once "$appFolder/messages.php"; ?>

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
            <label>Password: <input type="password" name="user_password" required></label> <br>
            <label>Password confirm: <input type="password" name="user_password_confirm" required></label> <br>
        </fieldset>

        <input type="submit" value="Install">
    </form>
</body>
</html>
