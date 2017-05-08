<?php
session_start();

$configStr = file_get_contents("../app/config.json");
$config = json_decode($configStr, true);

if ($config["use_url_rewrite"] && ! file_exists(".htaccess")) {
    $config["use_url_rewrite"] = false;
}

require_once "database.php";
require_once "functions.php";

populateMsgs();

$user = false; // will be array if user is logged in
$userId = -1;
$isUserAdmin = false;
$isLoggedIn = false;

if (isset($_SESSION["minicms_vanilla_auth"])) {
    $userId = (int)$_SESSION["minicms_vanilla_auth"];
    $user = queryDB("SELECT * FROM users WHERE id=?", $userId)->fetch();

    if ($user === false) {
        // the "logged in" user isn't found in the db...
        logout();
    }

    $isLoggedIn = true;
    $isUserAdmin = ($user["role"] === "admin");
}

// email
$siteProtocol = $_SERVER["REQUEST_SCHEME"];
$siteDomain = $_SERVER["HTTP_HOST"];
$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]); // used in menus, with a trailing slash
$siteURL = $siteProtocol."://".$siteDomain.$siteDirectory; // used in emails

require_once "email.php";

$useRecaptcha = ($config["recaptcha_secret"] !== "");
