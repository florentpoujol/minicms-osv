<?php
session_start();

require_once "admin/init.php";
require_once "admin/functions.php";
require_once "admin/database.php";

// process the parameters in the URL
$q = (isset($_GET["q"]) && $_GET["q"] !== "") ? $_GET["q"] : null; // for now suppose this is the id of the page the user wants to see

$menuHierarchy = buildMenuHierarchy();

if (isset($menuHierarchy[0])) { // there are pages in the db
  if ($q === null)
  	$q = $menuHierarchy[0]["id"]; // first parent page

  $field = "id";
  if (is_numeric($q) === false)
    $field = "url_name"; // the value of the "q" parameterr can also be the url_name of the page

  $page = queryDB("SELECT * FROM pages WHERE $field = ?", $q)->fetch();

  if($page === false || $page["published"] === 0) {
    header("HTTP/1.0 404 Not Found");
    $page = ["id" => -1, "title" => "Error page not found", "content" => "Error page not found"];
  }
}
else { // no page in db, show default
  $page = ["id" => -1, "title" => "Default page", "content" => "There is no page yet, log in to add pages"];
}

require_once "header.php";
require_once "menu.php";
?>
  <h1><?php echo $page["title"] ?></h1>

  <div id="page-content">
    <?php echo processPageContent($page["content"]); ?>
  </div> <!-- end #content -->
<?php
require_once "footer.php";
