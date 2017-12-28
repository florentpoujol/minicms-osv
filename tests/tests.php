<?php
const IS_TEST = true;

// --------------------------------------------------
// emulate index.php

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];
$testDb = new \PDO("sqlite::memory:", null, null, $options);
$sql = file_get_contents(__dir__ . "/database.sql");
$testDb->exec($sql); // using query() only creates the first table...

// create a first three users
$testDb->query(
    "INSERT INTO users(name, email, password_hash, role, creation_date) VALUES 
    ('admin', 'admin@email.com', '', 'admin', '1970-01-01'), 
    ('writer', 'writer@email.com', '', 'writer', '1970-01-02'), 
    ('commenter', 'com@email.com', '', 'commenter', '1970-01-03')"
);


$testConfig = json_decode(file_get_contents( __dir__ . "/config.json"), true);


$_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1"; // needed/used by setHTTPHeader()
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["REQUEST_URI"] = "/index.php";
$_SERVER["SCRIPT_NAME"] = realpath(__dir__ . "/../public/index.php");


// --------------------------------------------------


$currentTestFile = ""; // updated in tests.php (this file)
$currentTestName = ""; // updated in each individual tests files/sections

function outputFailedTest(string $text)
{
    global $currentTestFile, $currentTestName;
    echo "\033[41mTest '$currentTestName' failed in file '$currentTestFile':\033[m\n";
    echo $text . "\n";
    exit;
}

function loadSite(string $queryString = null, string $userId = null): string
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

// session_start();

require_once __dir__ . "/asserts.php";

$testsToRun = [];
if (isset($argv[1])) {
    // run a single test if it's specified as the first argument
    $file = $argv[1];
    if (preg_match("/test\.php$/i", strtolower($file)) !== 1) {
        $file .= "Test.php";
    }
    $testsToRun[] = $file;
} else {
    // consider each files finishing by "Test.php" as a test to run
    // whatever (sub)directories they are in
    function walkDir(string $dirStr)
    {
        global $testsToRun;
        $dir = opendir($dirStr);
        while (($file = readdir($dir)) !== false) {
            if ($file !== "." && $file !== ".." && is_dir($file)) {
                walkDir("$dirStr/$file");
                continue;
            }
            if (preg_match("/test\.php$/i", strtolower($file)) === 1) {
                $file = str_replace(__dir__ . "/", "", $dirStr . "/" . $file);
                $testsToRun[] = $file;
            }
        }
        closedir($dir);
    }
    walkDir(__dir__);
}

sort($testsToRun); // for some reason they are not yet in alphabetical order ...
foreach ($testsToRun as $file) {
    $currentTestFile = $file;
    $_SESSION = [];
    $_POST = [];
    require_once $file;
}

/*foreach ($testsToRun as $file) {
    echo $file . "\n";
}
var_dump($testsToRun);*/
echo "OK, " . count($testsToRun) . " test files run successfully !\n";
