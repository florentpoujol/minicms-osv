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
$adminSectionName = $config["admin_section_name"];

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

require_once "../app/functions.php";

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
$pageURL = $siteProtocol."://".$siteDomain.$_SERVER["REQUEST_URI"];

require_once "../app/email.php";

// --------------------------------------------------

$folder = (isset($_GET["f"]) && $_GET["f"] !== "") ? $_GET["f"]: null;
$pageName = (isset($_GET["p"]) && $_GET["p"] !== "") ? $_GET["p"]: null; // can the page or article slug or id

$action = (isset($_GET["a"]) && $_GET["a"] !== "") ? $_GET["a"] : null;

$pageNumber = (isset($_GET["page"]) && $_GET["page"] !== "") ? (int)$_GET["page"] : 1;
$maxPostPerPage = 5;
$adminMaxTableRows = 10;

// var_dump($_SERVER, $_GET);

if ($pageName === "logout") {
    logout();
}

if ($folder === $adminSectionName) {
    if ($isLoggedIn) {
        $resourceId = isset($_GET["id"]) ? (int)$_GET["id"] : null;
        $adminPages = ["config", "posts", "categories", "pages", "medias", "menus", "users", "comments"];

        if ($pageName === null || ! in_array($pageName, $adminPages)) {
            redirect($folder, "users", $action);
        }

        $orderByTable = isset($_GET["orderbytable"]) ? $_GET["orderbytable"] : "";
        $orderByField = isset($_GET["orderbyfield"]) ? $_GET["orderbyfield"] : "id";
        $orderDir = isset($_GET["orderdir"]) ? strtoupper($_GET["orderdir"]) : "ASC";
        if ($orderDir !== "ASC" && $orderDir !== "DESC") {
            $orderDir = "ASC";
        }

        echo "<p>Welcome ".$user["name"].", you are a ".$user["role"]." </p>";

        $file = $pageName;
        if ($pageName === "posts") {
            $file = "pages";
        }
        require_once "../app/backend/$file.php";
    }
    else {
        redirect(null, "login");
    }
}

// all front-end stuff :
else {
    $menuStructure = [];
    $dbMenu = queryDB("SELECT * FROM menus WHERE in_use = 1")->fetch();

    if (is_array($dbMenu)) {
        $menuStructure = json_decode($dbMenu["structure"], true);
    }

    $currentPage = ["id" => -1, "title" => "", "content" => ""];
    $specialPages = ["login", "register"];

    if (in_array($pageName, $specialPages)) {
        $currentPage = ["id" => -2, "title" => $pageName];
        require_once "../app/frontend/$pageName.php";
    }
    else {
        if ($folder === null && $pageName === null) {
            // the user hasn't requested any particular page
            // get home page from menu
            function getHomepage($items)
            {
                foreach ($items as $id => $item) {
                    if ($item["type"] === "homepage") {
                        return $item["target"];
                    }
                    elseif (isset($item["children"]) && count($item["children"]) > 0) {
                        $homepage = getHomepage($item["children"]);
                        if (is_string($homepage)) {
                            return $homepage;
                        }
                    }
                }
            }

            $homepage = getHomepage($menuStructure);

            if (is_string($homepage)) {
                $pageName = $homepage;
            }
            else {
                $folder = "blog";
            }
        }

        if ($pageName === null) {
            $pageName = ""; // PDO don't like the null value
        }

        $field = "id";
        if (! is_numeric($pageName)) {
            $field = "slug";
        }

        $currentPage = queryDB(
            "SELECT pages.*, users.name as user_name, categories.name as category_name
            FROM pages
            LEFT JOIN users ON pages.user_id = users.id
            LEFT JOIN categories ON pages.category_id = categories.id
            WHERE pages.$field = ?",
            $pageName
        )->fetch();

        if ($currentPage === false || ($currentPage["published"] === 0 && ! $isLoggedIn)) {
            header("HTTP/1.0 404 Not Found");
            $currentPage = ["id" => -1, "title" => "Error page not found", "content" => "Error page not found"];
        }

        $file = "page";
        if ($folder === "blog") {
            $file = "blog";
        }

        require_once "../app/frontend/$file.php";
    }
}
