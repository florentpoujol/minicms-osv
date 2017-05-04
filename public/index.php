<?php
if (! file_exists("../app/config.php")) {
    header("Location: install.php");
    exit;
}

require_once "../app/init.php";

$menuHierarchy = buildMenuHierarchy();
$currentPage = ["id" => -1, "title" => "", "content" => ""];

$pageName = (isset($_GET["p"]) && $_GET["p"] !== "") ? $_GET["p"]: null;

$specialPages = ["login", "register", "changepassword"];

if (in_array($pageName, $specialPages)) {
    $currentPage = ["id" => -2, "title" => $pageName];
}
elseif (isset($menuHierarchy[0])) {
    // there is at least one page in the DB
    if ($pageName === null) {
        $pageName = $menuHierarchy[0]["id"];
    }

    $field = "id";
    if (! is_numeric($pageName)) {
        $field = "url_name";
    }

    $currentPage = queryDB("SELECT * FROM pages WHERE $field = ?", $pageName)->fetch();

    if ($currentPage === false || $currentPage["published"] === 0) {
        header("HTTP/1.0 404 Not Found");
        $currentPage = ["id" => -1, "title" => "Error page not found", "content" => "Error page not found"];
    }
}
else {
    $currentPage = ["id" => -1, "title" => "Default page", "content" => "There is no page yet, log in to add pages"];
}

require_once "../app/frontend/header.php";

if (in_array($pageName, $specialPages)) {
    $action = (isset($_GET["a"]) && $_GET["a"] !== "") ? $_GET["a"] : null;
    require_once "../app/frontend/$pageName.php";
}
else {
?>

<h1><?php echo $currentPage["title"] ?></h1>

<div id="page-content">
    <?php echo processPageContent($currentPage["content"]); ?>
</div> <!-- end #content -->

<?php
    if ($currentPage["id"] >= 1) {
        require_once "../app/frontend/comments.php";
    }
}

require_once "../app/frontend/footer.php";
