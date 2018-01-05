<?php

function outputFailedTest(string $text)
{
    global $currentTestFile, $currentTestName;
    echo "\n\033[41mTest '$currentTestName' failed in file '$currentTestFile':\033[m\n";
    echo $text;
    exit;
}

function loadSite(string $queryString = null, int $userId = null): string
{
    if ($queryString !== null) {
        $_SERVER["QUERY_STRING"] = $queryString;
    }
    if ($userId !== null) {
        $_SESSION["user_id"] = $userId;
    }

    global $testDb, $testConfig,
           $db, $config, $site, $query, $errors, $successes;
    // this last line is needed to make these variables exist in the global scope
    // so that functions (in app/functions.php) can get them via "global $db;" for instance
    // Otherwise, these variable which are defined in the scope of the index.php file
    // would only exist in the scope of this function (loadApp()), which isn't in this case the global scope

    ob_start();
    require __dir__ . "/../public/index.php";
    return ob_get_clean();
}

function getConfig()
{
    return json_decode(file_get_contents( __dir__ . "/config.json"), true);
}

function getTestDB()
{
    global $testConfig;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new \PDO(
        "mysql:host=$testConfig[db_host];charset=utf8",
        $testConfig["db_user"],
        $testConfig["db_password"],
        $options
    );
}

function rebuildDB()
{
    global $testDb, $testConfig;

    $testDb->exec("DROP DATABASE IF EXISTS `$testConfig[db_name]`");
    $testDb->exec("CREATE DATABASE `$testConfig[db_name]`");

    $testDb->exec("use `$testConfig[db_name]`");

    $sql = file_get_contents(__dir__ . "/../app/database.sample.sql");
    $testDb->exec($sql); // using query() only creates the first table...
}

function seedDB()
{
    global $testDb;
    $passwordHash = password_hash("Az3rty", PASSWORD_DEFAULT);
    $testDb->exec(
        "INSERT INTO users(name, email, email_token, password_hash, password_token, password_change_time, role, creation_date, is_banned) VALUES 
    ('admin', 'admin@email.com', '', '$passwordHash', '', 0, 'admin', '1970-01-01', 0), 
    ('writer', 'writer@email.com', '', '$passwordHash', '', 0, 'writer', '1970-01-02', 0), 
    ('commenter', 'com@email.com', '', '$passwordHash', '', 0, 'commenter', '1970-01-03', 0)"
    );
}

function queryTestDB(string $strQuery, $data = null)
{
    global $testDb;
    $query = $testDb->prepare($strQuery);

    if ($data === null) {
        $query->execute();
    } else {
        if (! is_array($data)) {
            $data = [$data];
        }
        $query->execute($data);
    }

    return $query;
}

function getUser(string $value, string $field = "name")
{
    return queryTestDB("SELECT * FROM users WHERE $field = ?", $value)->fetch();
}

function setTestCSRFToken(string $requestName = ""): string
{
    $token = bin2hex( random_bytes(40 / 2) );
    $_SESSION[$requestName . "_csrf_token"] = $token;
    $_SESSION[$requestName . "_csrf_time"] = time();
    $_POST["csrf_token"] = $token;
    return $token;
}
