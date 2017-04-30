<?php
if (file_exists("dbconfig.php") === true) {
    header("Location: index.php");
    exit;
}

if (file_exists("dbconfig.sample.php") === false) {
    echo "Your installation seems to miss the 'dbconfig.sample.php' file. Can't continue !";
    exit;
}

if (file_exists("database.sample.sql") === false) {
    echo "Your installation seems to miss the 'database.sample.sql' file. Can't continue !";
    exit;
}

require_once "admin/functions.php";

$install = [
    "config" => [ // this holds the default value to be populated in DB
        "site_name" => "The Site Name",
        "use_url_rewrite" => "1",
        "allow_comments" => "1",
        "recaptcha_secret" => "",
        "mailer_from_address" => "flo@flo.fr",
        "smtp_host" => "",
        "smtp_user" => "",
        "smtp_password" => ""
    ],
    "db_host" => "localhost",
    "db_user" => "root",
    "db_password" => "root",
    "db_name" => "azerty",
    "db_prefix" => "va_",
    "user_name" => "Florent",
    "user_email" => "flo@flo.fr",
    "user_password" => "aZ1",
];

$errorMsg = "";

if (isset($_POST["site_name"])) {
    $_install = $install;
    foreach ($_install as $key => $value) {
        if ($key === "config") {
            continue;
        }

        $install[$key] = $_POST[$key];
    }
    $install["config"]["site_name"] = $_POST["site_name"];
    $install["config"]["use_url_rewrite"] = isset($_POST["use_url_rewrite"]) === true ? "1": "0";
    $install["config"]["mailer_from_address"] = $_POST["mailer_from_address"];

    $errorMsg = checkEmailFormat($install["config"]["mailer_from_address"]);
    $errorMsg .= checkNameFormat($install["user_name"]);
    $errorMsg .= checkEmailFormat($install["user_email"]);
    $errorMsg .= checkPasswordFormat($_POST["user_password"], $_POST["user_password_confirm"]);

    if ($errorMsg === "") {
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
                "mysql:host=".$install["db_host"].";charset=utf8", $install["db_user"], $install["db_password"],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // PDO::ATTR_EMULATE_PREPARES => false, // this causes a "General error: 2014" when uncommented
                ]
            );
        }
        catch (Exception $e) {
            $errorMsg .= "error connecting to the database <br>";
            $errorMsg .= $e->getMessage();
        }

        if ($errorMsg === "") {
            $dbName = $install["db_name"];
            $dbName = "`".str_replace("`", "``", $dbName)."`";
            $statement = $db->query("CREATE DATABASE IF NOT EXISTS $dbName");

            if ($statement !== false) {
                $db->query("use $dbName");

                $sql = file_get_contents("database.sample.sql");

                if (is_string($sql) === true) {
                    $query = $db->prepare($sql);
                    $success = $query->execute();

                    if ($success === true) {
                        $query = $db->prepare(
                            "INSERT INTO
                            users(name, email, password_hash, role, creation_date)
                            VALUES(:name, :email, :hash, :role :date)"
                        );
                        $params = [
                            "name" => $install["user_name"],
                            "email" => $install["user_email"],
                            "hash" => password_hash($install["user_password"], PASSWORD_DEFAULT),
                            "role" => "admin",
                            "date" => date("Y-m-d")
                        ];
                        $userSuccess = $query->execute($params);

                        $configSuccess = false;
                        $query = $db->prepare("INSERT INTO config(name, value) VALUES(:name, :value)");
                        foreach ($install["config"] as $name => $value) {
                            $configSuccess = $query->execute([$name => $value]);
                            if ($configSuccess === false) {
                                break;
                            }
                        }

                        if ($userSuccess === true && $configSuccess === true) {
                            $str = file_get_contents("dbconfig.sample.php");
                            if (is_string($str) === true) {
                                $str = str_replace('"host" => ""', '"host" => "'.$instal["db_host"].'"', $str);
                                $str = str_replace('"user" => ""', '"user" => "'.$instal["db_user"].'"', $str);
                                $str = str_replace('"password" => ""', '"password" => "'.$instal["db_password"].'"', $str);
                                $str = str_replace('"name" => ""', '"name" => "'.$instal["db_name"].'"', $str);

                                if (file_put_contents("dbconfig.php", $str) === false) {
                                    $errorMsg = "Couldn't write file 'dbconfig.php'";
                                }
                            }
                            else {
                                $errorMsg = "Couldn't read file 'dbconfig.sample.php'.";
                            }
                        }
                        else {
                            $errorMsg .= "Error populating the database";
                        }
                    }
                    else {
                        $errorMsg .= "Error creating tables in database.";
                    }
                }
                else {
                    $errorMsg .= "Error reading the 'database.sample.sql' file";
                }
            }
            else {
                $errorMsg .= "Error creating the database.";
            }
        }
    }
}

if (isset($_POST["site_name"]) === true && $errorMsg === "") {
    echo "all is well";
    exit;
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

    <?php require_once "admin/messages-template.php"; ?>

    <form action="" method="POST">
        <fieldset>
            <legend>Website</legend>

            <label> Site Name: <input type="text" name="site_name" value="<?php echo $install["config"]["site_name"]; ?>" required></label> <br>
            <label> Use nice URLs: <input type="checkbox" name="use_url_rewrite" <?php echo $install["config"]["use_url_rewrite"] === "1" ? "checked": ""; ?>></label> <br>

            <p>After installation, you will be able to go to the Config page to see more config stuffs.</p>
        </fieldset>

        <fieldset>
            <legend>Database</legend>

            <label>Host: <input type="text" name="db_host" value="localhost" value="<?php echo $install["db_host"]; ?>" required></label> <br>
            <label>User: <input type="text" name="db_user" value="<?php echo $install["db_user"]; ?>" required></label> <br>
            <label>Password: <input type="password" name="db_password" value="<?php echo $install["db_password"]; ?>" required></label> <br>
            <label>DB Name: <input type="text" name="db_name" value="<?php echo $install["db_name"]; ?>" required></label> <br>
            <label>table prefix: <input type="text" name="db_prefix" value="<?php echo $install["db_prefix"]; ?>" required></label> <br>
        </fieldset>

        <fieldset>
            <legend>Emails</legend>

            <label>Site's email address: <input type="email" name="mailer_from_address" value="<?php echo $install["config"]["mailer_from_address"]; ?>" ></label> <br>

            <p>After installation, you will be able to configure SMTP settings from the Config page.</p>
        </fieldset>

        <fieldset>
            <legend>Admin user</legend>

            <label>Username: <input type="text" name="user_name" value="<?php echo $install["user_name"]; ?>" required></label> <br>
            <label>Email: <input type="email" name="user_email" value="<?php echo $install["user_email"]; ?>" required></label> <br>
            <label>password: <input type="password" name="user_password" value="<?php echo $install["user_password"]; ?>" required></label> <br>
            <label>password confirm: <input type="password" name="user_password_confirm" value="<?php echo $install["user_password"]; ?>" required></label> <br>
        </fieldset>

        <input type="submit" value="Install">
    </form>
</body>
</html>