<?php
if (! file_exists("../app/config.json")) {
    header("Location: install.php");
    exit;
}

session_start();

// --------------------------------------------------
// config

$configStr = file_get_contents("../app/config.json");
$config = json_decode($configStr, true);

$useApache = (strpos($_SERVER["SERVER_SOFTWARE"], "Apache") !== false);
if ($useApache && $config["use_url_rewrite"] && ! file_exists(".htaccess")) {
    $config["use_url_rewrite"] = false;
}

$useRecaptcha = ($config["recaptcha_secret"] !== "");

// --------------------------------------------------
// database

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

$db = new PDO(
    "mysql:host=".$config["db_host"].";dbname=".$config["db_name"].";charset=utf8",
    $config["db_user"],
    $config["db_password"],
    $options
);

function queryDB($strQuery, $data = null, $getSuccess = false)
{
    global $db;
    $query = $db->prepare($strQuery);

    if (isset($data) && ! is_array($data)) {
        $data = [$data];
    }

    $success = $query->execute($data);

    if ($getSuccess) {
        return $success;
    }

    return $query;
}

// --------------------------------------------------

require_once "../php-markdown/Michelf/Markdown.inc.php";

require_once "functions.php";

populateMsgs();

// --------------------------------------------------
// user

$user = false; // will be array if user is logged in
$userId = -1;
$isUserAdmin = false;
$isLoggedIn = false;

if (isset($_SESSION["minicms_vanilla_auth"])) {
    $userId = (int)$_SESSION["minicms_vanilla_auth"];
    $user = queryDB("SELECT * FROM users WHERE id=?", $userId)->fetch();

    if ($user === false || $user["is_banned"] === 1) {
        // the "logged in" user isn't found in the db, or is banned
        logout();
    }

    $isLoggedIn = true;
    $isUserAdmin = ($user["role"] === "admin");
}

// --------------------------------------------------
// email and links

$siteProtocol = $_SERVER["REQUEST_SCHEME"];
$siteDomain = $_SERVER["HTTP_HOST"];
$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]); // used in menus, with a trailing slash
$siteURL = $siteProtocol."://".$siteDomain.$siteDirectory; // used in emails

require_once "email.php";

// --------------------------------------------------

$folder = (isset($_GET["f"]) && $_GET["f"] !== "") ? $_GET["f"]: null;
$pageName = (isset($_GET["p"]) && $_GET["p"] !== "") ? $_GET["p"]: null; // can the page or article slug or id
$page = $pageName;
$action = (isset($_GET["a"]) && $_GET["a"] !== "") ? $_GET["a"] : null;

// var_dump($_SERVER, $_GET);

if ($pageName === "logout") {
    logout();
}

if ($folder === "admin") {
    if ($isLoggedIn) {
        $resourceId = isset($_GET["id"]) ? (int)($_GET["id"]) : null;
        if ($pageName === null ) {
            redirect($folder, "users", $action);
        }

        $orderByTable = isset($_GET["orderbytable"]) ? $_GET["orderbytable"] : "";
        $orderByField = isset($_GET["orderbyfield"]) ? $_GET["orderbyfield"] : "id";
        $orderDir = isset($_GET["orderdir"]) ? strtoupper($_GET["orderdir"]) : "ASC";
        if ($orderDir !== "ASC" && $orderDir !== "DESC") {
            $orderDir = "ASC";
        }

        echo "<p>Welcome ".$user["name"].", you are a ".$user["role"]." </p>";

        require_once "../app/backend/$pageName.php";
    }
    else {
        redirect(null, "login");
    }
}

// all front-end stuff :
else {
    $menuHierarchy = buildMenuHierarchy();
    $currentPage = ["id" => -1, "title" => "", "content" => ""];
    $specialPages = ["login", "register"];

    if ($folder === "blog") {
        // get the few last articles if no id or slug is provided
        require_once "../app/frontend/blog.php";
    }
    elseif (in_array($pageName, $specialPages)) {
        $currentPage = ["id" => -2, "title" => $pageName];
        require_once "../app/frontend/$pageName.php";
    }
    else {
        if (isset($menuHierarchy[0])) {
            // there is at least one page in the DB
            if ($pageName === null) {
                $pageName = $menuHierarchy[0]["id"];
            }

            $field = "id";
            if (! is_numeric($pageName)) {
                $field = "slug";
            }

            $currentPage = queryDB("SELECT * FROM pages WHERE $field = ?", $pageName)->fetch();

            if ($currentPage === false || ($currentPage["published"] === 0 && ! $isLoggedIn)) {
                header("HTTP/1.0 404 Not Found");
                $currentPage = ["id" => -1, "title" => "Error page not found", "content" => "Error page not found"];
            }
        }
        else {
            $currentPage = ["id" => -1, "title" => "Default page", "content" => "There is no page yet, log in to add pages"];
        }

        require_once "../app/frontend/page.php";
    }
}
