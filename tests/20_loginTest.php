<?php

$user = queryTestDB("SELECT * FROM users WHERE name='Florent'")->fetch();

$queryString = "section=login";

$currentTestName = "GET Login";

$content = loadSite($queryString);

assertStringContains($content, "<h1>Login");

// --------------------------------------------------
$currentTestName = "POST Login wrong csrf";

$_POST["login_name"] = "foobar";
$_POST["login_password"] = "fooBar1";
$_POST["csrf_token"] = "not_the_right_token";

$content = loadSite($queryString);

assertStringContains($content, "Wrong CSRF token for request");

// --------------------------------------------------
$currentTestName = "POST Login wrong name";

$_POST["csrf_token"] = setCSRFTokens("login");

$content = loadSite($queryString);

assertStringContains($content, "No user by that name !");

// --------------------------------------------------
$currentTestName = "POST Login user not activated";

$_POST["login_name"] = "Florent";
$_POST["csrf_token"] = setCSRFTokens("login");
queryTestDB("UPDATE users SET email_token='foobar' WHERE name='Florent'");

$content = loadSite($queryString);

assertStringContains($content, "This user is not activated yet.");
queryTestDB("UPDATE users SET email_token='' WHERE name='Florent'");

// --------------------------------------------------
$currentTestName = "POST Login wrong password";

$_POST["csrf_token"] = setCSRFTokens("login");
// wrong password = "fooBar1"
$content = loadSite($queryString);

assertStringContains($content, "Wrong password !");

// --------------------------------------------------
$currentTestName = "POST Login success";

$_POST["login_password"] = "Az3rty";
$_POST["csrf_token"] = setCSRFTokens("login");

$content = loadSite($queryString);

assertRedirect(buildUrl("admin"));
assertArrayHasKey($_SESSION, "user_id");
assertIdentical((int)$_SESSION["user_id"], (int)$user["id"]);
unset($_SESSION["user_id"]);

// --------------------------------------------------
$queryString .= "&action=forgotpassword";

$currentTestName = "GET Forgot password";

$content = loadSite($queryString);

assertStringContains($content, "<h2>Forgot password ?</h2>");

// --------------------------------------------------
$currentTestName = "Forgot password wrong csrf";

$_POST["forgot_password_email"] = "foo@bar.fr";
$_POST["csrf_token"] = "not_the_right_token";

$content = loadSite($queryString);

assertStringContains($content, "Wrong CSRF token for request");

// --------------------------------------------------
$currentTestName = "Forgot password wrong email";

$_POST["csrf_token"] = setCSRFTokens("forgotpassword");
// wrong email = foo@bar.fr

$content = loadSite($queryString);

assertStringContains($content, "No users has that email.");

// --------------------------------------------------
$currentTestName = "Forgot password success";

$user = queryTestDB("SELECT * FROM users WHERE name='Florent'")->fetch();
assertEmpty($user["password_token"]);

$_POST["forgot_password_email"] = $user["email"];
$_POST["csrf_token"] = setCSRFTokens("forgotpassword");

$content = loadSite($queryString);

$user = queryTestDB("SELECT * FROM users WHERE name='Florent'")->fetch();
assertNotEmpty($user["password_token"]);
assertStringContains($content, "An email has been sent to this address. Click the link within 48 hours.");
assertEmailContains("You have requested to change your password. <br> Click the link below within 48 hours to access the form");
$link = $site["domainUrl"] . buildUrl([
        "section" =>  "login",
        "action" => "changepassword",
        "id" => $user["id"],
        "token" => $user["password_token"]
    ]);
assertEmailContains($link);
deleteEmail();

// --------------------------------------------------
$queryString = "section=login&action=changepassword";

$currentTestName = "Change password wrong token";

$content = loadSite($queryString . "&id=$user[id]&token=aaa");

assertMessageSaved("Unknow user or token expired. Please ask again for a new password then follow the link in the email you will receive.");
assertRedirect(buildUrl("login", "forgotpassword"));

// --------------------------------------------------
$currentTestName = "Change password token expired";

queryTestDB("UPDATE users SET password_change_time=1 WHERE name='Florent'");

$content = loadSite($queryString . "&id=$user[id]&token=$user[password_token]");

assertMessageSaved("Unknow user or token expired. Please ask again for a new password then follow the link in the email you will receive.");
assertRedirect(buildUrl("login", "forgotpassword"));
queryTestDB("UPDATE users SET password_change_time=? WHERE name='Florent'", $user["password_change_time"]);

// --------------------------------------------------
$currentTestName = "Change password wrong CSRF";

$_POST["new_password"] = "Az3rty2";
$_POST["csrf_token"] = "not_the_right_token";

$content = loadSite($queryString . "&id=$user[id]&token=$user[password_token]");

// no error msg in this case
assertStringContains($content, "<h1>Change password</h1>");
assertIdentical(true, password_verify("Az3rty", $user["password_hash"]));

// --------------------------------------------------
$currentTestName = "Change password success";

$_POST["new_password"] = "Az3rty2";
$_POST["new_password_confirm"] = "Az3rty2";
$_POST["csrf_token"] = setCSRFTokens("changepassword");
$oldPasswordHash = $user["password_hash"];

$content = loadSite($queryString . "&id=$user[id]&token=$user[password_token]");

$user = queryTestDB("SELECT * FROM users WHERE name='Florent'")->fetch();

assertDifferent($oldPasswordHash, $user["password_hash"]);
assertIdentical(true, password_verify($_POST["new_password"], $user["password_hash"]));
assertIdentical("", $user["password_token"]);
assertIdentical("0", $user["password_change_time"]);
assertRedirect(buildUrl("login"));
