<?php

function outputFailedTest(string $text)
{
    global $currentTestFile, $currentTestName;
    echo "\033[41mTest '$currentTestName' failed in file '$currentTestFile':\033[m\n";
    echo $text . "\n";
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
    $user = queryTestDB("SELECT * FROM users WHERE $field = ?", $value)->fetch();
    $user["id"] = (int)$user["id"];
    return $user;
}

function setCSRFToken(string $requestName = ""): string
{
    $token = bin2hex( random_bytes(40 / 2) );
    $_SESSION[$requestName . "_csrf_token"] = $token;
    $_SESSION[$requestName . "_csrf_time"] = time();
    $_POST["csrf_token"] = $token;
    return $token;
}
