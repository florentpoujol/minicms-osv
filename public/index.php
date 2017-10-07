<?php
declare(strict_types=1);

if (! file_exists("../app/config.json")) {
    header("Location: install.php");
    exit;
}

session_start();

/**
 * Logout the user by destroying the session cookie, the session itself
 * and redirecting it to the index
 */
function logout(): void
{
    setcookie(session_name(), null, 1); // destroy cookie
    session_destroy();
    header('Location: index.php');
    exit;
}

// logout the user here if the route is /logout
if (($_GET['r'] ?? '') === 'logout') {
    logout();
}

// --------------------------------------------------
// config

$configStr = file_get_contents("../app/config.json");
$config = json_decode($configStr, true);

$webServer = $_SERVER["SERVER_SOFTWARE"] ?? "";
$useApache = (strpos($webServer, "Apache") !== false);
if ($useApache && $config["use_url_rewrite"] && ! file_exists(".htaccess")) {
    $config["use_url_rewrite"] = false;
}

$config['useRecaptcha'] = ($config["recaptcha_secret"] !== "");

define('CONFIG', $config);
unset($config);

// --------------------------------------------------
// database

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

$db = new PDO(
    "mysql:host=" . CONFIG["db_host"] . ";dbname=" . CONFIG["db_name"] . ";charset=utf8",
    CONFIG["db_user"],
    CONFIG["db_password"],
    $options
);

/**
 * Run the specified query with the specified data against the database
 *
 * @param string     $strQuery
 * @param array|string $data
 * @param bool  $getSuccess
 * @return bool|PDOStatement
 */
function queryDB(string $strQuery, $data = null, bool $getSuccess = false)
{
    global $db;
    $query = $db->prepare($strQuery);

    if (is_string($data)) {
        $data = [$data];
    }
    $success = $query->execute($data);

    if ($getSuccess) {
        return $success;
    }
    return $query;
}

// --------------------------------------------------
// user

$user = [
    'id' => -1,
    'isLoggedIn' => false,
    'isAdmin' => false,
];

if (isset($_SESSION["minicms_vanilla_auth"])) {
    $user['id'] = (int)$_SESSION["minicms_vanilla_auth"];
    $dbUser = queryDB("SELECT * FROM users WHERE id = ?", $user['id'])->fetch();

    if ($dbUser === false || $dbUser["is_banned"] === 1) {
        // the "logged in" user isn't found in the db, or is banned
        logout();
    }

    $user['isLoggedIn'] = true;
    $user['isAdmin'] = ($dbUser["role"] === "admin");
    $user = array_merge($user, $dbUser);
}

define('USER', $user);
unset($user);

// --------------------------------------------------
// email and links

$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]);
// with a trailing slash
// only consists of a slash when the domain points toward the root directory

$domainUrl = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"];

define('SITE', [
    'directory' => $siteDirectory,
    'url' => $domainUrl . $siteDirectory, // used in emails
    'pageUrl' => $domainUrl . $_SERVER["REQUEST_URI"],
]);

require_once "../app/email.php";

// --------------------------------------------------

require_once "../includes/php-markdown/Michelf/Markdown.inc.php";

require_once "../app/functions.php";

populateMsg();

// --------------------------------------------------
// routing

// parse the query string
$query = [
    'section' => '', 'resource' => '', 'action' => '', 'id' => '',
    'orderbytable' => '', 'orderbyField' => 'id', 'orderDir' => 'ASC',
    'page' => 1, 'csrfToken' => '',
];
parse_str($_SERVER['QUERY_STRING'], $query);

$query['page'] = (int)$query['page'];
if ($query['page'] < 1) {
    $query['page'] = 1;
}
$maxPostPerPage = 5;
$adminMaxTableRows = 5;

// sanitize
if (! is_numeric($query['id'])) {
    $query['slug'] = $query['id'];
    unset($query['id']);
} else {
    $query['id'] = (int)$query['id'];
}

$query['orderDir'] = strtoupper($query['orderDir']);
if ($query['orderDir'] !== "ASC" && $query['orderDir'] !== "DESC") {
    $query['orderDir'] = "ASC";
}

define('QUERY', $query);
// unset($query);

// backend routing
if (QUERY['section'] === CONFIG['adminSectionName']) {
    if (USER['isLoggedIn']) {

        if (QUERY['action'] !== '') {
            $crud = ['create', 'read', 'edit', 'delete'];
            if (! in_array(QUERY['action'], $crud)) {
                logout();
            }
        }

        $adminPages = ["config", "posts", "categories", "pages", "medias", "menus", "users", "comments"];
        if (QUERY['resource'] === '' || ! in_array(QUERY['resource'], $adminPages)) {
            redirect(QUERY['section'], 'users', QUERY['action']);
        }

        echo "<p>Welcome " . USER["name"] . ", you are a " . USER["role"] . " </p>";

        $file = QUERY['resource'];
        if ($file === "posts" || $file === "pages") {
            $file = "posts-pages";
        }
        require_once "../app/backend/$file.php";
    } else {
        redirect(null, "login");
    }
}

// front-end routing
else {
    $resource = QUERY['resource'];
    $section = QUERY['section'];

    $menuStructure = [];
    $dbMenu = queryDB("SELECT * FROM menus WHERE in_use = 1")->fetch();
    if ($dbMenu !== false) {
        $menuStructure = json_decode($dbMenu["structure"], true);
    }

    $pageContent = ["id" => -1, "title" => "", "content" => ""];
    $specialPages = ["login", "register"];

    if (in_array($resource, $specialPages)) {
        $pageContent = ["id" => -2, "title" => $resource];
        include_once "../app/frontend/$resource.php";
    } else {
        if ($section === '' && $resource === '') {
            // the user hasn't requested any particular page
            // get home page from menu

            /**
             * @param array $menuItems
             * @return string|array
             */
            function getHomepage(array $menuItems)
            {
                foreach ($menuItems as $id => $item) {
                    if ($item["type"] === "homepage") {
                        return $item["target"];
                    } elseif (isset($item["children"]) && count($item["children"]) > 0) {
                        $homepage = getHomepage($item["children"]);
                        if (is_string($homepage)) {
                            return $homepage;
                        }
                    }
                }
                return null; // should not happens
            }
            $homepage = getHomepage($menuStructure);

            if (is_string($homepage)) {
                $resource = $homepage;
            } else {
                // no homepage set in the menu
                $section = "blog";
            }
        }

        $field = "id";
        if (! is_numeric($resource)) {
            $field = "slug";
        }

        if ($section === "blog" && $resource === "") {
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
                LIMIT ".$maxPostPerPage * (QUERY['page'] - 1).", $maxPostPerPage"
            );
            $pageContent["postsCount"] = queryDB("SELECT COUNT(*) FROM pages WHERE category_id IS NOT NULL")->fetch();
            $pageContent["postsCount"] = $pageContent["postsCount"]["COUNT(*)"];

            $pageContent["categories"] = queryDB("SELECT * FROM categories");
            $pageContent["categoriesCount"] = queryDB("SELECT COUNT(*) FROM categories")->fetch();
            $pageContent["categoriesCount"] = $pageContent["categoriesCount"]["COUNT(*)"];
        }

        elseif ($section === "category") {
            $pageContent = queryDB(
                "SELECT * FROM categories WHERE $field = ?",
                $resource
            )->fetch();

            if (is_array($pageContent)) {
                $count = queryDB("SELECT COUNT(*) FROM pages WHERE category_id = ?", $pageContent["id"])->fetch();
                $pageContent["postCount"] = $count["COUNT(*)"];

                $pageContent["posts"] = queryDB(
                    "SELECT * FROM pages WHERE category_id = ? AND published = 1
                    ORDER BY creation_date ASC
                    LIMIT ".$maxPostPerPage * (QUERY['page'] - 1).", $maxPostPerPage",
                    $pageContent["id"]
                );
            }
        }

        elseif ($section === "page" || $section === "post") { // signle page or post
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
                $resource
            )->fetch();

            if ($section !== "blog" && $pageContent["category_id"] !== null) {
                redirect("blog", $resource);
            }
        } else {

        }

        if (! is_array($pageContent) || (isset($pageContent["published"]) && $pageContent["published"] === 0 && ! $isLoggedIn)) {
            header("HTTP/1.0 404 Not Found");
            $pageContent = ["id" => -1, "title" => "Error page not found", "content" => "Error page not found"];
        }

        $file = "page";

        if (($section === "blog" && $resource === "") || $section === "category") {
            $file = $section;
        }

        include_once "../app/frontend/$file.php";
    }
}
