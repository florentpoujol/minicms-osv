<?php
if (! file_exists("../app/config.php")) {
    header("Location: install.php");
    exit;
}

require_once "../app/init.php";

$menuHierarchy = buildMenuHierarchy();
$page = ["id" => -1, "title" => "", "content" => ""];

$q = (isset($_GET["q"]) && $_GET["q"] !== "") ? $_GET["q"]: null;
$specialPages = ["login", "register", "changepassword"];

if (in_array($q, $specialPages)) {
    $page = ["id" => -2, "title" => $q, ];
}
elseif (isset($menuHierarchy[0])) {
    // there is at least one page in the DB
    if ($q === null) {
        $q = $menuHierarchy[0]["id"];
    }

    $field = "id";
    if (! is_numeric($q)) {
        $field = "url_name";
    }

    $page = queryDB("SELECT * FROM pages WHERE $field = ?", $q)->fetch();

    if ($page === false || $page["published"] === 0) {
        header("HTTP/1.0 404 Not Found");
        $page = ["id" => -1, "title" => "Error page not found", "content" => "Error page not found"];
    }
}
else {
    $page = ["id" => -1, "title" => "Default page", "content" => "There is no page yet, log in to add pages"];
}

require_once "../app/frontend/header.php";

if (in_array($q, $specialPages)) {
    require_once "../app/frontend/$q.php";
}
else {
?>

<h1><?php echo $page["title"] ?></h1>

<div id="page-content">
    <?php echo processPageContent($page["content"]); ?>
</div> <!-- end #content -->

<?php
    require_once "../app/frontend/comments.php";
}

require_once "../app/frontend/footer.php";
