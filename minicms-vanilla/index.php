<?php
require_once "admin/database.php";
require_once "admin/functions.php";

/*echo "<pre>";
print_r($_SERVER);
echo "</pre>";*/

$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]);
$siteDirectory = ltrim($siteDirectory, "/");

// process the parameters in the URL
$q = (isset($_GET["q"]) && $_GET["q"] !== "") ? $_GET["q"] : null; // for now suppose this is the id of the page the user wants to see

$menu = buildMenu();

if ($q === null)
  $q = $menu[0]["id"]; // first parent page

$field = "id";
if (is_numeric($q) === false)
  $field = "url_name";

$page = queryDB("SELECT * FROM pages WHERE $field = ?", $q)->fetch();

if($page === false) {
  header("HTTP/1.0 404 Not Found");
  $page = ["id" => -1, "title" => "Error page not found", "content" => "Error page not found"];
}

require_once "header.php";
require_once "menu.php";
?>
  <h1><?php echo $page["title"] ?></h1>

  <div id="page-content">
    <?php echo processPageContent($page["content"]); ?>
  </div>
<?php
require_once "footer.php";