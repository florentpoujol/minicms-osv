<?php

$_SERVER["QUERY_STRING"] = "section=register";

// GET Register
$currentTestName = "GET Register";

ob_start();
require __dir__ . "/../public/index.php";
$content = ob_get_clean();

assertStringContains($content, "<title>Register");

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
$testDb->query("INSERT INTO users(name, email, password_hash, role, creation_date) VALUES ('admin', '', '', 'admin', '')");

$_POST["register_name"] = "Florent";
$_POST["register_email"] = "florent@flo.rent";
$_POST["register_password"] = "Az3rty";
$_POST["register_password_confirm"] = "Az3rty";
$_POST["csrf_token"] = setCSRFTokens("register");

$users = $testDb->query("SELECT * FROM users")->fetchAll();
assertIdentical(1, count($users));

ob_start();
require __dir__ . "/../app/frontend/register.php";
$content = ob_get_clean();

assertStringContains($content, "You have successfully been registered. You need to activate your account by clicking the link that has been sent to your email address"); // success msg

$users = $testDb->query("SELECT * FROM users")->fetchAll();
assertIdentical(2, count($users));

$user = $users[1];
assertIdentical($_POST["register_name"], $user["name"]);
assertIdentical($_POST["register_email"], $user["email"]);
assertIdentical("commenter", $user["role"]);
assertNotEmpty($user["password_hash"]);
assertNotEmpty($user["email_token"]);

assertEmailContains($_POST["register_email"]);
assertEmailContains("index.php?section=register&action=confirmemail&id=$user[id]&token=$user[email_token]");
deleteEmail();
