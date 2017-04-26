<?php
require_once "../Models/model.php";
Model::connect();

require_once "../app/app.php"
App::populate();


// check if user is logged in
session_start();
require_once "../Models/users.php";
$user = false;
require_once "../app/helpers.php";

if (isset($_SESSION["minicms_mvc_auth"])) {
    $id = (int)$_SESSION["minicms_mvc_auth"];
    $user = Users::get(["id" => $id]);

    if ($user === false) {
        logout(); // for some reason the logged in user isn't found in the databse... let's log it out, just in case
    }
}

require_once "../app/messages.php";

require_once "../controllers/controller.php";

require_once "../app/lang.php";

require_once "../phpmailer/emailconfig.php";
require_once "../phpmailer/class.smtp.php";
require_once "../phpmailer/class.phpmailer.php";
require_once "../app/emails.php";


// var_dump($_SERVER);
$requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);

$controllerName = isset($_GET["c"]) ? ucfirst($_GET["c"]) : "";
$controllerName .= "Controller";

$action = isset($_GET["a"]) ? ucfirst($_GET["a"]) : "index";

if ($controllerName !== "") {
    require_once "../controllers/$controllerName.php";
    $controller = new $controllerName;
    $controller->{$requestMethod.$action}();
}

// Message::saveForLater();
