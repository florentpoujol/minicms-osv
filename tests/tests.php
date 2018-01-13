<?php

function runAllTestsOfFile(string $relativeFilePath)
{
    require_once __dir__ . "/$relativeFilePath";

    $testFileContent = file_get_contents(__dir__ . "/$relativeFilePath");
    $functions = [];
    preg_match_all("/function (test_[a-z0-9_]+)\(/i", $testFileContent, $functions);

    foreach ($functions[1] as $id => $functionToRun) {
        if (!function_exists($functionToRun)) {
            continue;
        }

        echo "  " . ($id + 1) . ". $functionToRun\n";

        $result = shell_exec(PHP_BINARY . " " . __FILE__ . " $relativeFilePath $functionToRun");

        if (trim($result) !== "") {
            echo $result;
            exit;
        }
    }
}

if (!isset($argv[1])) { // name of the file not specified
    // make sure a config.json file exists in the app folder
    // so that the site is considered as installed
    // and the user is not redirected to the install script
    $deleteConfigFile = false;
    $configFilePath = __dir__ . "/../app/config.json";
    if (!file_exists($configFilePath)) {
        $deleteConfigFile = true;
        touch($configFilePath);
    }

    echo "Setting up database...\n";
    require_once __dir__ . "/functions.php";
    require_once __dir__ . "/asserts.php";
    $testConfig = getConfig();
    $testDb = getTestDB();
    rebuildDB();
    seedDB();

    // find all test files in the current directory and all subdirectories
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
    sort($testFiles, SORT_NATURAL);

    $testFilesCount = count($testFiles);
    echo "Testing $testFilesCount files:\n";

    foreach ($testFiles as $id => $relativeFilePath) {
        echo ($id + 1) . ") $relativeFilePath\n";
        runAllTestsOfFile($relativeFilePath);
    }

    echo "\033[33;42m OK, all tests run successfully ! \033[m";
    exit;
}

// --------------------------------------------------

const IS_TEST = true;

require_once __dir__ . "/functions.php";
require_once __dir__ . "/asserts.php";

$testConfig = getConfig();
$testDb = getTestDB();
$testDb->exec("use `$testConfig[db_name]`");

$_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["REQUEST_URI"] = "/index.php";
$_SERVER["SCRIPT_NAME"] = realpath(__dir__ . "/../public/index.php");

session_start(); // session needs to start here instead of the front controller called from loadSite()
// mostly so that we can populate the $_SESSION superglobal

$currentTestFile = $argv[1];
require_once __dir__ . "/$currentTestFile";

$functionToRun = $argv[2];
$currentTestName = str_replace(["test_", "_"], ["", " "], $functionToRun);
$functionToRun();
