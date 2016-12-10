<?php
require_once "admin/database.php";
require_once "admin/functions.php";

// process the parameters in the URL
$q = (isset($_GET["q"]) && $_GET["q"] !== "") ? (int)$_GET["q"] : null; // for now suppose this is the id of the page the user wants to see

$menu = buildMenu();

if ($q === null)
  $q = $menu[0]["id"]; // first parent page

$query = $db->prepare('SELECT * FROM pages WHERE id = :id');
$query->execute(["id" => $q]);
$page = $query->fetch();

if($page === false) {
  header("HTTP/1.0 404 Not Found");
  $page = ["id" => -1, "title" => "Error page not found", "content" => "Error page not found"];
}

require_once "header.php";
require_once "menu.php";
?>
  <h1><?php echo $page["title"] ?></h1>

  <div id="page-content">
    <?php echo $page["content"]; ?>
  </div>
<?php
require_once "footer.php";