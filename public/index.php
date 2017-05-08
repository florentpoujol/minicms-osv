<?php
if (! file_exists("../app/config.json")) {
    header("Location: install.php");
    exit;
}

require_once "../app/init.php";

/*
site.com/page_id
site.com/page_slug
site.com/login
site.com/login/forgotpassword
site.com/changepassword
site.com/register
site.com/register/resendconfirmation
    site.com?p=pageslug_or_id
    site.com?p=special_page&a=action

site.com/blog/article_id
site.com/blog/article_slup
    site.com?f=blog&p=articleeslug_or_id

site.com/admin/page_name/
site.com/admin/page_name/action/resourceId
    site.com?f=admin&p=page_name&a=action&id=id
*/

$folder = (isset($_GET["f"]) && $_GET["f"] !== "") ? $_GET["f"]: null;
$pageName = (isset($_GET["p"]) && $_GET["p"] !== "") ? $_GET["p"]: null; // can the page or article slug or id
$page = $pageName;
$action = (isset($_GET["a"]) && $_GET["a"] !== "") ? $_GET["a"] : null;

if ($pageName === "logout") {
    logout();
}

if ($folder === "admin") {
    if ($isLoggedIn) {
        $resourceId = isset($_GET["id"]) ? (int)($_GET["id"]) : null;
        if ($pageName === null ) {
            redirect($folder, "users", $action, $resourceId);
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
    $specialPages = ["login", "register", "changepassword"];

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
