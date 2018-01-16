<?php
if (!defined("IS_TEST")) {
    define("IS_TEST", false);
}

if (! file_exists(__dir__ . "/../app/config.json")) {
    header("Location: install.php");
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    // session may already have been started by tests
    session_start();
}

require_once __dir__ . "/../app/functions.php";

// logout the user here if the route is /logout
if (isset($_GET["section"]) && $_GET["section"] === "logout") {
    logout();
}

// --------------------------------------------------
// config

$config = [];
if (IS_TEST) {
    $config = $testConfig;
} else {
    $configStr = file_get_contents(__DIR__ . "/../app/config.json");
    $config = json_decode($configStr, true);
}

$webServer = $_SERVER["SERVER_SOFTWARE"] ?? "";
$useApache = (strpos($webServer, "Apache") !== false);
if ($useApache && $config["use_url_rewrite"] && ! file_exists(".htaccess")) {
    $config["use_url_rewrite"] = false;
}

$config["useRecaptcha"] = ($config["recaptcha_secret"] !== "");

// --------------------------------------------------
// database

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

$db = null;
if (IS_TEST) {
    $db = $testDb;
} else {
    $db = new \PDO(
        "mysql:host=$config[db_host];dbname=$config[db_name];charset=utf8",
        $config["db_user"],
        $config["db_password"],
        $options
    );
}

// --------------------------------------------------
// user

$user = [
    "id" => -1,
    "isLoggedIn" => false,
    "isAdmin" => false,
];

if (isset($_SESSION["user_id"])) {
    $user["id"] = (int)$_SESSION["user_id"];
    $dbUser = queryDB("SELECT * FROM users WHERE id = ?", $user["id"])->fetch();

    if ($dbUser === false) {
        // the "logged in" user isn't found in the db
        setHTTPResponseCode(403);
        logout();
    }

    $user["isLoggedIn"] = true;
    $user["isAdmin"] = ($dbUser["role"] === "admin");
    $user = array_merge($user, $dbUser);
    $user["id"] = (int)$user["id"]; //
}

// --------------------------------------------------
// email and links

$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]);
// with a trailing slash
// only consists of a slash when the domain points toward the root directory

$scheme = isset($_SERVER["HTTPS"]) ? "https" : "http";
$domainUrl = "$scheme://" . $_SERVER["HTTP_HOST"];

$site = [
    "domainUrl" => $domainUrl,
    "directory" => $siteDirectory,
    "url" => $domainUrl . $siteDirectory, // used in emails
    "pageUrl" => $domainUrl . $_SERVER["REQUEST_URI"],
];

require_once __dir__ . "/../app/email.php";

// --------------------------------------------------

require_once __dir__ . "/../includes/php-markdown/Michelf/Markdown.inc.php";

populateMsg();

// --------------------------------------------------
// routing

/*
section=blog
section=blog&page=[page]

section=page&id=[id or slug]
section=post&id=[id or slug]

section=category&id=[id or slug]
section=category&id=[id or slug]&page=[page]

section=logout

section=login
section=login&action=lostpassword
section=login&action=resetpassword&id=[id]&token=[token]

section=register
section=register&action=resendconfirmationemail
section=register&action=confirmemail&id=[id]&token=[token]

section=admin:(config|users|pages|posts|comments|categories|medias|menus)&
    action=(create|read|update|delete)&
    id=id
other optional query string parameters for the admin section
    orderDir
    orderByTable
    orderByField
    csrfToken
*/

// parse the query string
$query = [
    "section" => "", "action" => "", "id" => "", "page" => 1, "csrftoken" => "",
    "token" => "", "orderbytable" => "", "orderbyfield" => "id", "orderdir" => "ASC",
];
$_query = [];
parse_str($_SERVER['QUERY_STRING'], $_query);
$query = array_merge($query, $_query);

// sanitize some params
if (is_numeric($query['id'])) {
    $query['id'] = (int)$query['id'];
}

$query['page'] = (int)$query['page'];
if ($query['page'] < 1) {
    $query['page'] = 1;
}
$maxPostPerPage = 5;
$adminMaxTableRows = 5;

$query['orderdir'] = strtoupper($query['orderdir']);
if ($query['orderdir'] !== "ASC" && $query['orderdir'] !== "DESC") {
    $query['orderdir'] = "ASC";
}
// end sanitize

$isAdminRoute = false;
$parts = explode(':', $query['section']);
if ($parts[0] === $config['admin_section_name']) {
    $isAdminRoute = true;
    $query['section'] = $parts[1] ?? '';
}

// backend routing
if ($isAdminRoute) {
    if ($user['isLoggedIn']) {

        if ($query['action'] !== '') {
            $crud = ['create', 'read', 'update', 'delete'];
            if (! in_array($query['action'], $crud)) {
                logout();
            }
        }

        $adminPages = ["config", "posts", "categories", "pages", "medias", "menus", "users", "comments"];
        if ($query['section'] === '' || ! in_array($query['section'], $adminPages)) {
            redirect('admin:users', $query['action']);
            return;
        }

        $file = $query['section'];
        if ($file === "posts" || $file === "pages") {
            $file = "posts-pages";
        }
        require __dir__ . "/../app/backend/$file.php";
    } else {
        setHTTPResponseCode(403);
        redirect('login');
        return;
    }
}

// front-end routing
else {
    $menuStructure = [];
    $dbMenu = queryDB("SELECT * FROM menus WHERE in_use = ?", 1)->fetch();
    if ($dbMenu !== false) {
        $menuStructure = json_decode($dbMenu["structure"], true);
    }

    $pageContent = ["id" => -1, "title" => "", "content" => ""];
    $section = $query['section'];

    if (in_array($section, ["login", "register"])) {
        $pageContent['title'] = $section;
        require __dir__ . "/../app/frontend/$section.php";
    } else {
        if ($section === '') {
            // user hasn't requested a particular page
            $homepage = getMenuHomepage($menuStructure);

            if (is_string($homepage)) {
                $section = $homepage;
            } else {
                // no homepage set in the menu
                $section = "blog";
            }
            $query['section'] = $section;
        }

        $field = "id";
        if (is_string($query['id'])) {
            $field = "slug";
        }

        $file = 'blog';

        if ($section === "blog") {
            $pageContent["title"] = "Blog";

            $pageContent["posts"] = queryDB(
                "SELECT pages.*,
                categories.id as category_id,
                categories.slug as category_slug,
                categories.title as category_title,
                users.name as user_name
                FROM pages
                LEFT JOIN categories ON pages.category_id = categories.id
                LEFT JOIN users ON pages.user_id = users.id
                WHERE pages.category_id IS NOT NULL AND pages.published = 1
                ORDER BY pages.creation_date DESC
                LIMIT " . $maxPostPerPage * ($query['page'] - 1) . ", $maxPostPerPage"
            );
            $pageContent["postsCount"] = queryDB("SELECT COUNT(*) FROM pages WHERE category_id IS NOT NULL")
                ->fetch()["COUNT(*)"];

            $pageContent["categories"] = queryDB("SELECT * FROM categories");
            $pageContent["categoriesCount"] = queryDB("SELECT COUNT(*) FROM categories")->fetch()["COUNT(*)"];
            $file = 'blog';
        }

        elseif ($section === "category") {
            $pageContent = queryDB(
                "SELECT * FROM categories WHERE $field = ?", $query['id']
            )->fetch();

            if (is_array($pageContent)) {
                $pageContent["posts"] = queryDB(
                    "SELECT * FROM pages WHERE category_id = ? AND published = 1
                    ORDER BY creation_date ASC
                    LIMIT " . $maxPostPerPage * ($query['page'] - 1) . ", $maxPostPerPage",
                    $pageContent["id"]
                );

                $pageContent["postCount"] = queryDB(
                    "SELECT COUNT(*) FROM pages WHERE category_id = ? AND published = 1", $pageContent["id"]
                )->fetch()["COUNT(*)"];
            }
            $file = 'category';
        }

        elseif ($section === "page" || $section === "post") {
            $pageContent = queryDB(
                "SELECT pages.*,
                users.name as user_name,
                categories.id as category_id,
                categories.slug as category_slug,
                categories.title as category_title
                FROM pages
                LEFT JOIN users ON pages.user_id = users.id
                LEFT JOIN categories ON pages.category_id = categories.id
                WHERE pages.$field = ?",
                $query['id']
            )->fetch();
            $file = 'page-post';
        }

        else {
            $section = '';
        }

        if (
            $section === '' ||
            $pageContent === false || // when id/slug not found or issue with the DB query
            (
                isset($pageContent["published"]) &&
                $pageContent["published"] === 0 &&
                (!$user['isLoggedIn'] || $user["role"] === "commenter")
            )
        ) {
            setHTTPResponseCode(404);
            $pageContent = ["id" => -3, "title" => "Error page not found", "content" => "Error page not found"];
        }

        require_once __dir__ . "/../app/frontend/$file.php";
    }
}

/*
usefull variables passed to the "views"
$config, $site, $user, $query, menuStructure
*/
