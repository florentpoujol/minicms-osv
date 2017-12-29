<?php

function test_login()
{
    $content = loadSite("section=login");
    assertStringContains($content, "<h1>Login");
}

function test_login_wrong_csrf()
{
    $_POST["login_name"] = "foobar";
    $_POST["login_password"] = "fooBar1";
    $_POST["csrf_token"] = "wrong_token";

    $content = loadSite("section=login");
    assertStringContains($content, "Wrong CSRF token for request");
}

function test_login_no_user_for_name()
{
    $_POST["login_name"] = "foobar";
    $_POST["login_password"] = "fooBar1";
    setCSRFToken("login");

    $content = loadSite("section=login");

    assertStringContains($content, "No user by that name !");
}

function test_login_user_not_activated()
{
    $_POST["login_name"] = "commenter";
    $_POST["login_password"] = "Az3rty";
    setCSRFToken("login");
    queryTestDB("UPDATE users SET email_token='foobar' WHERE name='commenter'");

    $content = loadSite("section=login");
    assertStringContains($content, "This user is not activated yet.");
}

function test_login_wrong_name_format()
{
    $_POST["login_name"] = "what ever";
    $_POST["login_password"] = "foobar";
    setCSRFToken("login");

    $content = loadSite("section=login");
    assertStringContains($content, "The name has the wrong format.");
}

function test_login_wrong_password_format()
{
    $_POST["login_name"] = "commenter";
    $_POST["login_password"] = "foobar";
    setCSRFToken("login");

    $content = loadSite("section=login");
    assertStringContains($content, "The password must be at least");
}

function test_login_wrong_password()
{
    $_POST["login_name"] = "commenter";
    $_POST["login_password"] = "fooBar1";
    setCSRFToken("login");

    $content = loadSite("section=login");
    assertStringContains($content, "Wrong password !");
}

function test_login_success()
{
    $_POST["login_name"] = "commenter";
    $_POST["login_password"] = "Az3rty";
    setCSRFToken("login");

    loadSite("section=login");

    assertRedirectUrlContains("admin");
    assertArrayHasKey($_SESSION, "user_id");
    assertIdentical((int)$_SESSION["user_id"], getUser("commenter")["id"]);
}

function test_login_forgotpassword()
{
    $content = loadSite("section=login&action=forgotpassword");
    assertStringContains($content, "<h2>Forgot password ?</h2>");
}

function test_login_forgotpassword_wrong_csrf()
{
    $_POST["forgot_password_email"] = "foo@bar.fr";
    $_POST["csrf_token"] = "wrong_token";

    $content = loadSite("section=login&action=forgotpassword");
    assertStringContains($content, "Wrong CSRF token for request");
}

function test_login_forgotpassword_wrong_email()
{
    $_POST["forgot_password_email"] = "foo@bar.fr";
    setCSRFToken("forgotpassword");

    $content = loadSite("section=login&action=forgotpassword");
    assertStringContains($content, "No users has that email.");
}

function test_login_forgotpassword_success()
{
    $user = getUser("commenter");
    assertEmpty($user["password_token"]);

    $_POST["forgot_password_email"] = $user["email"];
    setCSRFToken("forgotpassword");

    $content = loadSite("section=login&action=forgotpassword");

    $user = getUser("commenter");
    assertNotEmpty($user["password_token"]);
    assertStringContains($content, "An email has been sent to this address. Click the link within 48 hours.");
    assertEmailContains("You have requested to change your password. <br> Click the link below within 48 hours to access the form");
    assertEmailContains("section=login&action=changepassword&id=$user[id]&token=$user[password_token]");
}

function test_login_changepassword_wrong_token()
{
    $user = getUser("commenter");
    loadSite("section=login&action=changepassword&id=$user[id]&token=aaaa");

    assertMessageSaved("Unknow user or token expired. Please ask again for a new password then follow the link in the email you will receive.");
    assertRedirectUrlContains("section=login&action=forgotpassword");
}

function test_login_changepassword_token_expired()
{
    $token = "aaaa";
    queryTestDB("UPDATE users SET password_token='$token', password_change_time=1 WHERE name='commenter'");
    $user = getUser("commenter");

    loadSite("section=login&action=changepassword&id=$user[id]&token=$user[password_token]");

    assertMessageSaved("Unknow user or token expired. Please ask again for a new password then follow the link in the email you will receive.");
    assertRedirectUrlContains("section=login&action=forgotpassword");
}

function test_login_changepassword_wrong_csrf()
{
    $_POST["new_password"] = "Az3rty2";
    $_POST["csrf_token"] = "wrong_token";

    $token = "aaaa";
    queryTestDB("UPDATE users SET password_token='$token', password_change_time=? WHERE name='commenter'", time());
    $user = getUser("commenter");

    $content = loadSite("section=login&action=changepassword&id=$user[id]&token=$user[password_token]");

    // no error msg in this case
    assertStringContains($content, "<h1>Change password</h1>");
    assertIdentical(true, password_verify("Az3rty", $user["password_hash"]));
}

function test_login_changepassword_success()
{
    $_POST["new_password"] = "Az3rty2";
    $_POST["new_password_confirm"] = "Az3rty2";
    setCSRFToken("changepassword");

    $token = "aaaa";
    queryTestDB("UPDATE users SET password_token='$token', password_change_time=? WHERE name='commenter'", time());
    $user = getUser("commenter");
    $oldPasswordHash = $user["password_hash"];

    loadSite("section=login&action=changepassword&id=$user[id]&token=$user[password_token]");

    $user = getUser("commenter");
    assertIdentical("", $user["password_token"]);
    assertIdentical("0", $user["password_change_time"]);
    assertDifferent($oldPasswordHash, $user["password_hash"]);
    assertIdentical(true, password_verify($_POST["new_password"], $user["password_hash"]));
    assertRedirectUrlContains("section=login");
}
