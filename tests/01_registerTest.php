<?php

$currentTestName = "GET Register";

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "<h1>Register");

// --------------------------------------------------
$currentTestName = "POST Register CSRF fail";

$_POST["register_name"] = "Florent";
$_POST["register_email"] = "florent@flo.fr";
$_POST["register_password"] = "azerty";
$_POST["register_password_confirm"] = "azerty";
$_POST["csrf_token"] = "not_the_right_token";

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "Wrong CSRF token for request 'register'");

// --------------------------------------------------
$currentTestName = "POST Register Form fail";

$_POST["register_name"] = "Fl";
$_POST["register_email"] = "florent@flofr";
$_POST["register_password"] = "az";
$_POST["register_password_confirm"] = "a";
$_POST["csrf_token"] = setCSRFTokens("register");

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "The name has the wrong format. Minimum four letters, numbers, hyphens or underscores. No Spaces.");
assertStringContains($content, "The email has the wrong format.");
assertStringContains($content, "The password must be at least 3 characters long and have at least one lowercase letter, one uppercase letter and one number.");
assertStringContains($content, "The password confirmation does not match the password.");

// --------------------------------------------------
$currentTestName = "POST Register";

// create a first admin user
$db->query("INSERT INTO users(name, email, password_hash, role, creation_date) VALUES ('admin', '', '', 'admin', '')");

$_POST["register_name"] = "Florent";
$_POST["register_email"] = "florent@flo.rent";
$_POST["register_password"] = "Az3rty";
$_POST["register_password_confirm"] = "Az3rty";
$_POST["csrf_token"] = setCSRFTokens("register");

$users = $db->query("SELECT * FROM users")->fetchAll();
assertIdentical(1, count($users));

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "You have successfully been registered. You need to activate your account by clicking the link that has been sent to your email address"); // success msg

$users = $db->query("SELECT * FROM users")->fetchAll();
assertIdentical(2, count($users));

$_user = $users[1];
assertIdentical($_POST["register_name"], $_user["name"]);
assertIdentical($_POST["register_email"], $_user["email"]);
assertIdentical("commenter", $_user["role"]);
assertNotEmpty($_user["password_hash"]);
assertNotEmpty($_user["email_token"]);

assertEmailContains($_POST["register_email"]);
assertEmailContains("index.php?section=register&action=confirmemail&id=$_user[id]&token=$_user[email_token]");
deleteEmail();


// --------------------------------------------------


$user = resetUser();
$query["action"] = "confirmemail";

// --------------------------------------------------
$currentTestName = "Confirm register email bad token";

$query["token"] = "aaaa";
$query["id"] = $_user["id"];

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "No user match that id and token");

// --------------------------------------------------
$currentTestName = "Confirm register email bad id";

$query["id"] = 987;

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "No user match that id and token");

// --------------------------------------------------
$currentTestName = "Confirm register email success";

$query["id"] = $_user["id"];
$query["token"] = $_user["email_token"];

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "Your email has been confirmed, you can now log in.");
$tokenFromDB = queryDB("SELECT email_token FROM users WHERE id = ?", $_user["id"]);
assertIdentical("", $tokenFromDB->fetch()["email_token"]);
$user = resetUser();


// --------------------------------------------------

$query["action"] = "resendconfirmation";

// --------------------------------------------------
$currentTestName = "Register resend confirmation wrong email";

$_POST["confirm_email"] = "flo@flo.fr";
$_POST["csrf_token"] = setCSRFTokens("resendconfirmation");

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "No user with that email");
$user = resetUser();

// --------------------------------------------------
$currentTestName = "Register resend confirmation no need to resend";

$_POST["confirm_email"] = $_user["email"];
$_POST["csrf_token"] = setCSRFTokens("resendconfirmation");

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "No need to resend the confirmation email.");
$user = resetUser();

// --------------------------------------------------
$currentTestName = "Register resend confirmation success";

$_POST["csrf_token"] = setCSRFTokens("resendconfirmation");
$token = $_user["email_token"];
queryDB("UPDATE users SET email_token=? WHERE id=?", [$_user["email_token"], $_user["id"]]);

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "Confirmation email has been sent again.");
assertEmailContains("Confirm your email address");
deleteEmail();
$user = resetUser();
queryDB("UPDATE users SET email_token='' WHERE id=?", $_user["id"]);
