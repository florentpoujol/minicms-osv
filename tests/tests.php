<?php
const IS_TEST = true;

$testsToRun = [];
if (isset($argv[1])) {
    // run a single test if it's specified as the first argument
    $testsToRun[] = $argv[1] . "Test.php";
} else {
    function walkDir(string $dirStr)
    {
        global $testsToRun;
        $dir = opendir($dirStr);
        while (($file = readdir($dir)) !== false) {
            if ($file !== "." && $file !== ".." && is_dir($file)) {
                walkDir("$dirStr/$file");
                continue;
            }
            if (substr($file, -8) === "Test.php") {
                $testsToRun[] = $file;
            }
        }
        closedir($dir);
    }
    walkDir(__dir__);
}


$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];
$testDb = new \PDO("sqlite::memory:", null, null, $options);
$sql = file_get_contents(__dir__ . "/database.sql");
$testDb->exec($sql); // using query() only creates the first table...


$testConfig = json_decode(file_get_contents( __dir__ . "/config.json"), true);


$_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["REQUEST_URI"] = "/index.php";
$_SERVER["SCRIPT_NAME"] = realpath(__dir__ . "/../public/index.php");


$currentTestFile = ""; // updated in tests.php
$currentTestName = ""; // updated in each individual tests files/sections

function outputFailedTest(string $text)
{
    global $currentTestFile, $currentTestName;
    echo "\033[41mTest '$currentTestName' failed in file '$currentTestFile':\033[m\n";
    echo "$text\n";
    exit;
}

require_once __dir__ . "/asserts.php";
require_once __dir__ . "/../app/functions.php";


foreach ($testsToRun as $file) {
    $currentTestFile = $file;
    require_once $file;
}

echo "OK all tests successful !\n";
