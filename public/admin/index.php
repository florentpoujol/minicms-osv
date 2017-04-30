<?php
require_once "../../app/init.php";

$page = (isset($_GET["p"]) && $_GET["p"] !== "") ? $_GET["p"] : "users";
$action = (isset($_GET["a"]) && $_GET["a"] !== "") ? $_GET["a"] : "show";
// action can be  show (default), add, edit, delete, logout, ...
$resourceId = isset($_GET["id"]) ? (int)($_GET["id"]) : -1;

if ($action === "logout") {
    logout();
}

if (is_array($user)) {
    $userRole = $user["role"];
    $isUserAdmin = ($user["role"] === "admin");

    $orderByTable = isset($_GET["orderbytable"]) ? $_GET["orderbytable"] : "";
    $orderByField = isset($_GET["orderbyfield"]) ? $_GET["orderbyfield"] : "id";
    $orderDir = isset($_GET["orderdir"]) ? strtoupper($_GET["orderdir"]) : "ASC";

    echo "<p>Welcome ".$user["name"].", you are a ".$user["role"]." </p>";

    require_once "../../app/backend/$page.php";
}
elseif ($page === "register" || $page === "login" || $page === "changepassword") {
    require_once "../../app/backend/$page.php";
}
else {
    // require_once "../../app/backend/login.php";
    redirect(["p" => "login"]);
}
?>

</body>
</html>
