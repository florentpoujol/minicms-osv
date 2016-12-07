<?php
session_start();

require_once "functions.php";

// first check if the user is logged in
if (isset($_SESSION["minicms_handmade_auth"])) {

  require_once "database.php";

  $currentUserId = (int)$_SESSION["minicms_handmade_auth"];

  $query = $db->prepare('SELECT * FROM users WHERE id = :id');
  $query->execute(['id' => $currentUserId]);
  $currentUser = $query->fetch();

  if ($currentUser === false)
    logout(); // for some reason the logged in user isn't found in the databse... let's log it out, just in case

  $isUserAdmin = ($currentUser["role"] === "admin");

  // process the parameters in the URL
  $section = isset($_GET["section"]) ? $_GET["section"] : "pages";
  $action = isset($_GET["action"]) ? $_GET["action"] : "show"; // action can be  show (default), add, edit, delete
  $resourceId = isset($_GET["id"]) ? (int)($_GET["id"]) : 0;

  // info or error msg can be passed through the URL
  $infoMsg = isset($_GET["infomsg"]) ? $_GET["infomsg"] : "";
  $errorMsg = isset($_GET["errormsg"]) ? $_GET["errormsg"] : "";

  require_once "template.php";
}

// if not, show the login form
else
  redirect(["page" => "login.php"]);
?>

