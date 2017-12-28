<?php
$queryString = "section=register";

$currentTestName = "GET Register";

$content = loadSite($queryString);

assertStringContains($content, "<h1>Register");

// --------------------------------------------------
$currentTestName = "POST Register CSRF fail";

$_POST["register_name"] = "Florent";
$_POST["register_email"] = "florent@flo.fr";
$_POST["register_password"] = "azerty";
$_POST["register_password_confirm"] = "azerty";
$_POST["csrf_token"] = "not_the_right_token";

$content = loadSite($queryString);

assertStringContains($content, "Wrong CSRF token for request 'register'");

// --------------------------------------------------
$currentTestName = "POST Register Form fail";

$_POST["register_name"] = "Fl";
$_POST["register_email"] = "florent@flofr";
$_POST["register_password"] = "az";
$_POST["register_password_confirm"] = "a";
$_POST["csrf_token"] = setCSRFTokens("register");

$content = loadSite($queryString);

assertStringContains($content, "The name has the wrong format. Minimum four letters, numbers, hyphens or underscores. No Spaces.");
assertStringContains($content, "The email has the wrong format.");
assertStringContains($content, "The password must be at least 3 characters long and have at least one lowercase letter, one uppercase letter and one number.");
assertStringContains($content, "The password confirmation does not match the password.");

// --------------------------------------------------
$currentTestName = "POST Register";

$_POST["register_name"] = "Florent";
$_POST["register_email"] = "florent@flo.rent";
$_POST["register_password"] = "Az3rty";
$_POST["register_password_confirm"] = "Az3rty";
$_POST["csrf_token"] = setCSRFTokens("register");

$users = queryTestDB("SELECT * FROM users")->fetchAll();
assertIdentical(3, count($users));

$content = loadSite($queryString);

assertStringContains($content, "You have successfully been registered. You need to activate your account by clicking the link that has been sent to your email address"); // success msg

$users = queryTestDB("SELECT * FROM users")->fetchAll();
assertIdentical(4, count($users));

$user = $users[$testDb->lastInsertId() - 1];
$user["id"] = (int)$user["id"];
assertIdentical($_POST["register_name"], $user["name"]);
assertIdentical($_POST["register_email"], $user["email"]);
assertIdentical("commenter", $user["role"]);
assertNotEmpty($user["password_hash"]);
assertNotEmpty($user["email_token"]);

assertEmailContains($_POST["register_email"]);
assertEmailContains("index.php?section=register&action=confirmemail&id=$user[id]&token=$user[email_token]");
deleteEmail();


// --------------------------------------------------

$queryString .= "&action=confirmemail";

$currentTestName = "Confirm register email bad token";

$content = loadSite($queryString . "&id=$user[id]&token=aaa");

assertStringContains($content, "No user match that id and token");

// --------------------------------------------------
$currentTestName = "Confirm register email bad id";

$content = loadSite($queryString . "&id=987&token=aaa");

assertStringContains($content, "No user match that id and token");

// --------------------------------------------------
$currentTestName = "Confirm register email success";

$content = loadSite($queryString . "&id=$user[id]&token=$user[email_token]");

assertMessageSaved("Your email has been confirmed, you can now log in.");
$tokenFromDB = queryTestDB("SELECT email_token FROM users WHERE id = ?", $user["id"]);
assertIdentical("", $tokenFromDB->fetch()["email_token"]);


// --------------------------------------------------

$queryString = "section=register&action=resendconfirmation";

$currentTestName = "Register resend confirmation wrong email";

$_POST["confirm_email"] = "flo@flo.fr";
$_POST["csrf_token"] = setCSRFTokens("resendconfirmation");

$content = loadSite($queryString);

assertStringContains($content, "No user with that email");

// --------------------------------------------------
$currentTestName = "Register resend confirmation no need to resend";

$_POST["confirm_email"] = $user["email"];
$_POST["csrf_token"] = setCSRFTokens("resendconfirmation");

$content = loadSite($queryString);

assertStringContains($content, "No need to resend the confirmation email.");

// --------------------------------------------------
$currentTestName = "Register resend confirmation success";

$_POST["csrf_token"] = setCSRFTokens("resendconfirmation");
$token = $user["email_token"];
queryTestDB("UPDATE users SET email_token=? WHERE id=?", [$user["email_token"], $user["id"]]);

$content = loadSite($queryString);

assertStringContains($content, "Confirmation email has been sent again.");
assertEmailContains("Confirm your email address");
deleteEmail();
queryTestDB("UPDATE users SET email_token='' WHERE id=?", $user["id"]);
