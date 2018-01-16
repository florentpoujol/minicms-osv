<?php

function outputFailedTest(string $text)
{
    global $currentTestFile, $currentTestName;
    echo "\033[41mTest '$currentTestName' failed in file '$currentTestFile':\033[m\n";
    echo $text . "\n";
    debug_print_backtrace();
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
    // would only exist in the scope of this function (loadSite()), which isn't in this case the global scope

    ob_start();
    require_once __dir__ . "/../public/index.php";
    return ob_get_clean();
}

function loadInstallScript(): string
{
    global $testDb, $db, $errors, $successes; // keep that, see in loadSite()

    ob_start();
    require_once __dir__ . "/../public/install.php";
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

    // users
    $passwordHash = password_hash("Az3rty", PASSWORD_DEFAULT);
    $testDb->exec(
        "INSERT INTO users(name, email, email_token, password_hash, password_token, password_change_time, role, creation_date, is_banned) VALUES 
    ('admin', 'admin@email.com', '', '$passwordHash', '', 0, 'admin', '1970-01-01', 0), 
    ('writer', 'writer@email.com', '', '$passwordHash', '', 0, 'writer', '1970-01-02', 0), 
    ('commenter', 'com@email.com', '', '$passwordHash', '', 0, 'commenter', '1970-01-03', 0)"
    );

    $admin = getUser("admin");
    $writer = getUser("writer");

    // pages
    $testDb->exec("INSERT INTO pages
    (slug, title, content, user_id, creation_date, published, allow_comments) 
    VALUES('page-admin', 'The first page', 'The content of the first page', $admin[id], NOW(), 1, 1),
    ('page-writer', 'The second page', 'The content of the page written by the writer', $writer[id], NOW(), 1, 1)");

    // comments
    $commenter = getUser("commenter");
    $testDb->exec("INSERT INTO comments(page_id, user_id, text, creation_time) 
        VALUES(1, $admin[id], 'A comment on page admin by admin', ".time()."),
        (1, $writer[id], 'A comment on page admin by writer', ".time()."),
        (1, $commenter[id], 'A comment on page admin by commenter', ".time()."),
        (2, $commenter[id], 'A comment on page writer by commenter', ".time().")");

    // categories
    $testDb->exec("INSERT INTO categories(slug, title) 
    VALUES('category-0', 'Category 0')"); // category 1 is created in the categories test file

    // posts
    $testDb->exec("INSERT INTO pages
    (slug, title, content, category_id, user_id, creation_date, published, allow_comments) 
    VALUES('post-1', 'The first post', 'The content of the first post', 1, $admin[id], NOW(), 1, 1)");
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
    assertDifferent($user, false);
    return $user;
}

function quickQuery(string $type, string $table, array $conditions)
{
    $strQuery = "";
    if ($type === "select") {
        $strQuery = "SElECT * FROM $table WHERE ";
        foreach ($conditions as $field => $value) {
            $strQuery .= "$field = :$field";
        }
    }
    return queryTestDB($strQuery);
}

function setTestCSRFToken(string $requestName = ""): string
{
    $token = bin2hex( random_bytes(40 / 2) );
    $_SESSION[$requestName . "_csrf_token"] = $token;
    $_SESSION[$requestName . "_csrf_time"] = time();
    $_POST["csrf_token"] = $token;
    return $token;
}

function moveUploadedFile(string $src, string $dest): bool
{
    return copy($src, $dest);
}