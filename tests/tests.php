<?php

$keepDB = in_array("--keep-db", $argv);
// if the option is present in the cmd line, the key/value will exist, with keep-db as the key and false as the value
// note: can't use getopt() because it only works for options that are before any non-options arguments

$strKeepDB = $keepDB ? "--keep-db" : "";

if (($id = array_search("--keep-db", $argv)) !== false) {
    array_splice($argv, $id, 1);
}

if (!isset($argv[1])) { // name of the file
    // find all tet files in the current directory and all subdirectories
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

    // always drop and recreate the database when running all the tests
    // so that it is not done for all individual tests
    echo "Setting up database...\n";
    require_once __dir__ . "/functions.php";
    $testConfig = getConfig();
    $testDb = getTestDB();
    rebuildDB();
    seedDB();


    $testFilesCount = count($testFiles);
    echo "Testing $testFilesCount files:\n";

    foreach ($testFiles as $id => $relativeFilePath) {
        echo ($id + 1) . ") $relativeFilePath\n";
        // echo ".";

        $result = shell_exec(PHP_BINARY . " " . __file__ . " $relativeFilePath $strKeepDB");
        if (trim($result) !== "") {
            echo $result;
            exit;
        }
    }

    echo "\033[33;42m OK, all tests run successfully ! \033[m";
    exit;
}

if (!isset($argv[2])) { // name of the function
    // get all function names that begins by "test_"
    $testFileContent = file_get_contents(__dir__ . "/$argv[1]");
    $functions = [];
    preg_match_all("/function (test_[a-z0-9_]+)\(/i", $testFileContent, $functions);

    foreach ($functions[1] as $funcToRun) {
        $result = shell_exec(PHP_BINARY . " " . __file__ . " $argv[1] $funcToRun $strKeepDB");
        if (trim($result) !== "") {
            echo $result;
            exit;
        }
    }
    exit;
}

// --------------------------------------------------
// setup

const IS_TEST = true;

require_once __dir__ . "/functions.php";

$testConfig = getConfig();

$testDb = getTestDB();

if ($keepDB) {
    $testDb->exec("use `$testConfig[db_name]`");
} else {
    rebuildDB();
    seedDB();
}

$_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1"; // needed/used by setHTTPHeader()
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["REQUEST_URI"] = "/index.php";
$_SERVER["SCRIPT_NAME"] = realpath(__dir__ . "/../public/index.php");

// --------------------------------------------------

require_once __dir__ . "/asserts.php";

session_start(); // session needs to start here instead of the front controller called from loadSite()
// mostly so that we can populate the $_SESSION superglobal

$currentTestFile = $argv[1];
require_once __dir__ . "/$currentTestFile";

$functionToRun = $argv[2];
$currentTestName = str_replace(["test_", "_"], ["", " "], $functionToRun);
$functionToRun();
