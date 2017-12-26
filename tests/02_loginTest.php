<?php

$query["section"] = "login";
$query["action"] = "";

$currentTestName = "GET Login";

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertStringContains($content, "<h1>Login");

// --------------------------------------------------
$currentTestName = "POST Login wrong csrf";

$_POST["login_name"] = "foobar";
$_POST["login_password"] = "fooBar1";
$_POST["csrf_token"] = "not_the_right_token";

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertStringContains($content, "Wrong CSRF token for request");

// --------------------------------------------------
$currentTestName = "POST Login wrong name";

$_POST["csrf_token"] = setCSRFTokens("login");

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertStringContains($content, "No user by that name !");

// --------------------------------------------------
$currentTestName = "POST Login user not activated";

$_POST["login_name"] = "Florent";
$_POST["csrf_token"] = setCSRFTokens("login");
queryDB("UPDATE users SET email_token='foobar' WHERE name='Florent'");

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertStringContains($content, "This user is not activated yet.");
$user = resetUser();
queryDB("UPDATE users SET email_token='' WHERE name='Florent'");

// --------------------------------------------------
$currentTestName = "POST Login wrong password";

$_POST["csrf_token"] = setCSRFTokens("login");

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertStringContains($content, "Wrong password !");
$user = resetUser();

// --------------------------------------------------
$currentTestName = "POST Login success";

$_POST["login_password"] = "Az3rty";
$_POST["csrf_token"] = setCSRFTokens("login");

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertRedirect(buildUrl($config["admin_section_name"]));
assertArrayHasKey($_SESSION, "user_id");
assertIdentical($_SESSION["user_id"], $user["id"]);
$user = resetUser();


// --------------------------------------------------
$currentTestName = "GET Forgot password";
$query["action"] = "forgotpassword";

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertStringContains($content, "<h2>Forgot password ?</h2>");

// --------------------------------------------------
$currentTestName = "Forgot password wrong csrf";

$_POST["forgot_password_email"] = "foo@bar.fr";
$_POST["csrf_token"] = "not_the_right_token";

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertStringContains($content, "Wrong CSRF token for request");

// --------------------------------------------------
$currentTestName = "Forgot password wrong email";

$_POST["csrf_token"] = setCSRFTokens("forgotpassword");

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertStringContains($content, "No users has that email.");

// --------------------------------------------------
$currentTestName = "Forgot password success";

deleteEmail();
$_user = queryDB("SELECT * FROM users WHERE name='Florent'")->fetch();
assertEmpty($_user["password_token"]);

$_POST["forgot_password_email"] = $_user["email"];
$_POST["csrf_token"] = setCSRFTokens("forgotpassword");

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

$_user = queryDB("SELECT * FROM users WHERE name='Florent'")->fetch();
assertNotEmpty($_user["password_token"]);
assertStringContains($content, "An email has been sent to this address. Click the link within 48 hours.");
assertEmailContains("You have requested to change your password. <br> Click the link below within 48 hours to access the form");
$link = $site["domainUrl"] . buildUrl([
        "section" =>  "login",
        "action" => "changepassword",
        "id" => $_user["id"],
        "token" => $_user["password_token"]
    ]);
assertEmailContains($link);
deleteEmail();
$user = resetUser();

// --------------------------------------------------
$currentTestName = "Change password wrong token";
$query["action"] = "changepassword";

$_GET["id"] = $_user["id"];
$_GET["token"] = "a";

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertMessageSaved("Unknow user or token expired. Please ask again for a new password then follow the link in the email you will receive.");
assertRedirect(buildUrl("login", "forgotpassword"));

// --------------------------------------------------
$currentTestName = "Change password token expired";

$_GET["token"] = $_user["password_token"];
queryDB("UPDATE users SET password_change_time=1 WHERE name='Florent'");

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

assertMessageSaved("Unknow user or token expired. Please ask again for a new password then follow the link in the email you will receive.");
assertRedirect(buildUrl("login", "forgotpassword"));
queryDB("UPDATE users SET password_change_time=? WHERE name='Florent'", $_user["password_change_time"]);
$user = resetUser();

// --------------------------------------------------
$currentTestName = "Change password wrong CSRF";

$_POST["new_password"] = "Az3rty2";
$_POST["csrf_token"] = "not_the_right_token";

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

// no error msg
assertStringContains($content, "<h1>Change password</h1>");
assertIdentical(true, password_verify("Az3rty", $_user["password_hash"]));
$user = resetUser();

// --------------------------------------------------
$currentTestName = "Change password success";

$_POST["new_password"] = "Az3rty2";
$_POST["new_password_confirm"] = "Az3rty2";
$_POST["csrf_token"] = setCSRFTokens("changepassword");
$oldPasswordHash = $_user["password_hash"];

ob_start();
require __dir__ . "/../app/frontend/login.php";
$content = ob_get_clean();

$_user = queryDB("SELECT * FROM users WHERE name='Florent'")->fetch();

assertDifferent($oldPasswordHash, $_user["password_hash"]);
assertIdentical(true, password_verify($_POST["new_password"], $_user["password_hash"]));
assertIdentical("", $_user["password_token"]);
assertIdentical("0", $_user["password_change_time"]);
assertRedirect(buildUrl("login"));
