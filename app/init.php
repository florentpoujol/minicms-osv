<?php
session_start();

require_once "config.php";
require_once "database.php";
require_once "functions.php";

populateMsgs();

$user = false; // will be array if user is logged in
$userId = -1;
$userRole = "";

if (isset($_SESSION["minicms_vanilla_auth"])) {
    $userId = (int)$_SESSION["minicms_vanilla_auth"];
    $user = queryDB("SELECT * FROM users WHERE id=?", $userId)->fetch();

    if ($user === false) {
        // the "logged in" user isn't found in the db...
        logout();
    }
}

// email
$siteProtocol = $_SERVER["REQUEST_SCHEME"];
$siteDomain = $_SERVER["HTTP_HOST"];
$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]); // used in menus
$siteURL = $siteProtocol."://".$siteDomain.$siteDirectory; // used in emails

require_once "email.php";
