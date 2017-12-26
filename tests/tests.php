<?php
const IS_TEST = true;
const PHP_CLI_CMD = "php7.0"; // in my computer, the php cmdd is PHP7.1

// --------------------------------------------------
// emulate index.php

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];
$db = new \PDO("sqlite::memory:", null, null, $options);
$sql = file_get_contents(__dir__ . "/database.sql");
$db->exec($sql); // using query() only creates the first table...

$config = json_decode(file_get_contents( __dir__ . "/config.json"), true);
$config["useRecaptcha"] = ($config["recaptcha_secret"] !== "");

function resetUser(int $id = -1, bool $isLoggedIn = false, bool $isAdmin = false)
{
    return compact("id", "isLoggedIn", "isAdmin");
}
$user = resetUser();
// this is needed because some part of script use a "local" $user variable that override the "global" variable

// $_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
// $_SERVER["HTTP_HOST"] = "localhost";
// $_SERVER["REQUEST_URI"] = "/index.php";
// $_SERVER["SCRIPT_NAME"] = realpath(__dir__ . "/../public/index.php");
$siteDirectory = "/";
$scheme = "http";
$domainUrl = "http://localhost";
$site = [
    "domainUrl" => $domainUrl,
    "directory" => $siteDirectory,
    "url" => $domainUrl . $siteDirectory, // used in emails
    "pageUrl" => $domainUrl,
];

$menuStructure = [];

require_once __dir__ . "/../app/functions.php";
require_once __dir__ . "/../app/email.php";
require_once __dir__ . "/../includes/php-markdown/Michelf/Markdown.inc.php";

populateMsg();

// routing
$query = [
    "section" => "", "action" => "", "id" => "", "page" => 1, "csrftoken" => "",
    "token" => "", "orderbytable" => "", "orderbyfield" => "id", "orderdir" => "ASC",
];
$maxPostPerPage = 5;
$adminMaxTableRows = 5;

// --------------------------------------------------


$currentTestFile = ""; // updated in tests.php (this file)
$currentTestName = ""; // updated in each individual tests files/sections

function outputFailedTest(string $text)
{
    global $currentTestFile, $currentTestName;
    echo "\033[41mTest '$currentTestName' failed in file '$currentTestFile':\033[m\n";
    echo "$text\n";
    exit;
}



require_once __dir__ . "/asserts.php";

$testsToRun = [];
if (isset($argv[1])) {
    // run a single test if it's specified as the first argument
    $file = $argv[1];
    if (substr($file, -8) !== "Test.php") {
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
            if (substr($file, -8) === "Test.php") {
                $relDirStr = str_replace(__dir__ . "/", "", $dirStr);
                if ($relDirStr !== "") {
                    $relDirStr .= "/";
                }
                $testsToRun[] = "$relDirStr$file";
            }
        }
        closedir($dir);
    }
    walkDir(__dir__);
}

foreach ($testsToRun as $file) {
    $currentTestFile = $file;
    require_once $file;
}

echo "OK all tests successful !\n";
