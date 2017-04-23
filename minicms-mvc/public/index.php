<?php
require_once "../Models/model.php";
Model::connect();

$siteProtocol = $_SERVER["REQUEST_SCHEME"];
$siteDomain = $_SERVER["HTTP_HOST"];
$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]);
$currentSiteURL = $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"].$siteDirectory;

// check if user is logged in
session_start();
require_once "../Models/users.php";
require_once "../helpers.php";

$user = false;
if (isset($_SESSION["minicms_mvc_auth"])) {
  $id = (int)$_SESSION["minicms_mvc_auth"];
  $user = Users::get(["id" => $id]);

  if ($user === false)
    logout(); // for some reason the logged in user isn't found in the databse... let's log it out, just in case
}

require_once "../messages.php";

require_once "../Controllers/controller.php";

// require_once "../views.php";
require_once "../lang.php";


// var_dump($_SERVER);
$requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);

$controllerName = isset($_GET["c"]) ? ucfirst($_GET["c"]) : "";

$action = isset($_GET["a"]) ? ucfirst($_GET["a"]) : "index";

if ($controllerName !== "") {
  $controllerName .= "Controller";
  require_once "../controllers/$controllerName.php";
  $controller = new $controllerName;
  $controller->{$requestMethod.$action}();
}
else {

  echo "index <br>";
  if ($user !== false)
    echo $user->name;
}

// Message::saveForLater();
