<?php
require_once "../models/model.php";
Model::connect();

require_once "../app/app.php";
App::populate();


// check if user is logged in
session_start();
require_once "../models/users.php";
$user = false;
require_once "../app/helpers.php";

if (isset($_SESSION["minicms_mvc_auth"]) === true) {
    $id = (int)$_SESSION["minicms_mvc_auth"];
    $user = Users::get(["id" => $id]);

    if ($user === false) {
        logout(); // for some reason the logged in user isn't found in the databse... let's log it out, just in case
    }
}

require_once "../app/messages.php";
Messages::populate();

require_once "../controllers/controller.php";

require_once "../app/lang.php";

require_once "../phpmailer/emailconfig.php";
require_once "../phpmailer/class.smtp.php";
require_once "../phpmailer/class.phpmailer.php";
require_once "../app/emails.php";

require_once "../app/validator.php";


$controllerName = isset($_GET["c"]) ? $_GET["c"] : "";
$controllerName .= "Controller";

// var_dump($_SERVER);
// var_dump($_GET);

$action = isset($_GET["a"]) ? $_GET["a"] : "index";

if ($controllerName !== "") {
    if ($controllerName === "logoutController") {
        logout();
    }

    require_once "../controllers/$controllerName.php";
    $controller = new $controllerName;
    $controller->{App::$requestMethod.$action}();
}

// Message::saveForLater();
