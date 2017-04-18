<?php
session_start();

require_once "functions.php";

if (isset($_POST["logout"])) // the form is in the admin menu
  logout();

require_once "database.php";
require_once "init.php";
require_once "email.php";


// info or error msg can be passed through the URL
$infoMsg = isset($_GET["infomsg"]) ? $_GET["infomsg"] : "";
$infoMsg .= isset($_GET["infoMsg"]) ? $_GET["infoMsg"] : "";
$errorMsg = isset($_GET["errormsg"]) ? $_GET["errormsg"] : "";
$errorMsg .= isset($_GET["errorMsg"]) ? $_GET["errorMsg"] : "";

// first check if the user is logged in
if (isset($currentUser)) {
  $isUserAdmin = ($currentUser["role"] === "admin");
  $userRole = ($currentUser["role"] === "admin");

  // process the parameters in the URL
  $section = (isset($_GET["section"]) && $_GET["section"] !== "") ? $_GET["section"] : "users";
  $action = (isset($_GET["action"]) && $_GET["action"] !== "") ? $_GET["action"] : "show"; // action can be  show (default), add, edit, delete
  $resourceId = isset($_GET["id"]) ? (int)($_GET["id"]) : 0;

  $orderByTable = isset($_GET["orderbytable"]) ? $_GET["orderbytable"] : "";
  $orderByField = isset($_GET["orderbyfield"]) ? $_GET["orderbyfield"] : "id";
  $orderDir = isset($_GET["orderdir"]) ? strtoupper($_GET["orderdir"]) : "ASC";
  if ($orderDir !== "ASC" && $orderDir !== "DESC")
    $orderDir = "ASC";

  echo "<p>Welcome ".$currentUser["name"].", you are a ".$currentUser["role"]." </p>";

  require_once $section.".php";
}

// if not, show the login/register form
elseif (isset($_GET["action"]) && ($_GET["action"] === "forgotPassword" || $_GET["action"] === "confirmEmail"))
  require_once $_GET["action"].".php";

else
  require_once "login.php";

require_once "footer.php";
