<?php

if (!isset($argv[1])) { // name of the file
    $testFiles = [];
    function walkDir(string $dirStr)
    {
        global $testFiles;
        $dir = opendir($dirStr);
        while (($file = readdir($dir)) !== false) {
            if ($file !== "." && $file !== ".." && is_dir($file)) {
                walkDir("$dirStr/$file");
                continue;
            }
            if (preg_match("/test\.php$/i", $file) === 1) {
                $file = str_replace(__dir__ . "/", "", $dirStr . "/" . $file);
                $testFiles[] = $file;
            }
        }
        closedir($dir);
    }
    walkDir(__dir__);
    sort($testFiles); // for some reason they are not yet in alphabetical order ...

    $testFilesCount = count($testFiles);
    echo "Testing $testFilesCount files.\n";

    foreach ($testFiles as $id => $relativeFilePath) {
        // echo ($id + 1) . ") $relativeFilePath\n";
        echo ".";
        $result = shell_exec(PHP_BINARY . " " . __file__ . " $relativeFilePath");
        if (trim($result) !== "") {
            echo $result . "\n";
            exit;
        }
    }

    echo "\n\033[33;42mOK, all tests run successfully !\033[m\n";
    exit;
}

if (!isset($argv[2])) { // name of the function
    // get all function names that begins by "test_"
    $content = file_get_contents($argv[1]);
    $matches = [];
    preg_match_all("/function (test_[a-z_]+)\(/i", $content, $matches);

    foreach ($matches[1] as $funcToRun) {
        $result = shell_exec(PHP_BINARY . " " . __file__ . " $argv[1] $funcToRun");
        if (trim($result) !== "") {
            echo $result . "\n";
            exit;
        }
    }

    exit;
}

// --------------------------------------------------
// setup

const IS_TEST = true;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

$testDb = new \PDO("sqlite::memory:", null, null, $options);
$sql = file_get_contents(__dir__ . "/database.sql");
$testDb->exec($sql); // using query() only creates the first table...

// create the first three users
$passwordHash = password_hash("Az3rty", PASSWORD_DEFAULT);
$testDb->query(
    "INSERT INTO users(name, email, email_token, password_hash, password_token, role, creation_date) VALUES 
    ('admin', 'admin@email.com', '', '$passwordHash', '', 'admin', '1970-01-01'), 
    ('writer', 'writer@email.com', '', '$passwordHash', '', 'writer', '1970-01-02'), 
    ('commenter', 'com@email.com', '', '$passwordHash', '', 'commenter', '1970-01-03')"
);

$testConfig = json_decode(file_get_contents( __dir__ . "/config.json"), true);

$_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1"; // needed/used by setHTTPHeader()
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["REQUEST_URI"] = "/index.php";
$_SERVER["SCRIPT_NAME"] = realpath(__dir__ . "/../public/index.php");

// --------------------------------------------------

require_once __dir__ . "/functions.php";
require_once __dir__ . "/asserts.php";

session_start(); // session needs to start here instead of the front controller called from loadSite()
// mostly so that we can populate the $_SESSION superglobal

$currentTestFile = $argv[1];
require_once __dir__ . "/$currentTestFile";

$functionToRun = $argv[2];
$currentTestName = str_replace(["test_", "_"], ["", " "], $functionToRun);
$functionToRun();
