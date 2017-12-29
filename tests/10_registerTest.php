<?php

function test_register()
{
    $content = loadSite("section=register");
    assertStringContains($content, "<h1>Register");
}

function test_register_wrong_csrf()
{
    $_POST["register_name"] = "Florent";
    $_POST["register_email"] = "florent@flo.fr";
    $_POST["register_password"] = "Az3rty";
    $_POST["register_password_confirm"] = "Az3rty";
    $_POST["csrf_token"] = "wrong_token";

    $content = loadSite("section=register");
    assertStringContains($content, "Wrong CSRF token for request 'register'");
}

function test_register_wrong_form()
{
    $_POST["register_name"] = "Fl";
    $_POST["register_email"] = "florent@flofr";
    $_POST["register_password"] = "az";
    $_POST["register_password_confirm"] = "a";
    setCSRFToken("register");

    $content = loadSite("section=register");

    assertStringContains($content, "The name has the wrong format. Minimum four letters, numbers, hyphens or underscores. No Spaces.");
    assertStringContains($content, "The email has the wrong format.");
    assertStringContains($content, "The password must be at least 3 characters long and have at least one lowercase letter, one uppercase letter and one number.");
    assertStringContains($content, "The password confirmation does not match the password.");
}

function test_register_success()
{
    $_POST["register_name"] = "Florent";
    $_POST["register_email"] = "florent@flo.fr";
    $_POST["register_password"] = "Az3rty";
    $_POST["register_password_confirm"] = "Az3rty";
    setCSRFToken("register");

    $users = queryTestDB("SELECT * FROM users")->fetchAll();
    assertIdentical(3, count($users));

    $content = loadSite("section=register");
    assertStringContains($content, "You have successfully been registered. You need to activate your account by clicking the link that has been sent to your email address"); // success msg

    $users = queryTestDB("SELECT * FROM users")->fetchAll();
    assertIdentical(4, count($users));

    $user = getUser("Florent");
    assertIdentical($_POST["register_name"], $user["name"]);
    assertIdentical($_POST["register_email"], $user["email"]);
    assertIdentical("commenter", $user["role"]);
    assertNotEmpty($user["password_hash"]);
    assertNotEmpty($user["email_token"]);

    assertEmailContains($_POST["register_email"]);
    assertEmailContains("index.php?section=register&action=confirmemail&id=$user[id]&token=$user[email_token]");
}

function test_register_confirmemail_bad_token()
{
    $user = getUser("commenter");
    $content = loadSite("section=register&action=confirmemail&id=$user[id]&token=aaa");
    assertStringContains($content, "No user match that id and token");
}

function test_register_confirmemail_bad_id()
{
    $content = loadSite("section=register&action=confirmemail&id=987&token=aaa");
    assertStringContains($content, "No user match that id and token");
}

function test_register_confirmemail_success()
{
    $user = getUser("commenter");
    $token = "aaa";
    queryTestDB("UPDATE users SET email_token='$token' WHERE name='commenter'");

    $content = loadSite("section=register&action=confirmemail&id=$user[id]&token=$token");

    assertMessageSaved("Your email has been confirmed, you can now log in.");
    $user = getUser("commenter");
    assertIdentical("", $user["email_token"]);
    assertRedirectUrlContains("login");
}

function test_register_resendconfirmation_wrong_email()
{
    $_POST["confirm_email"] = "flo@flo.fr";
    setCSRFToken("resendconfirmation");

    $content = loadSite("section=register&action=resendconfirmation");
    assertStringContains($content, "No user with that email");
}

function test_register_resendconfirmation_no_need_to_resend()
{
    $user = getUser("commenter");
    $_POST["confirm_email"] = $user["email"];
    setCSRFToken("resendconfirmation");

    $content = loadSite("section=register&action=resendconfirmation");
    assertStringContains($content, "No need to resend the confirmation email.");
}

function test_register_resendconfirmation_success()
{
    setCSRFToken("resendconfirmation");
    $_POST["confirm_email"] = getUser("commenter")["email"];
    $token = "aaa";
    queryTestDB("UPDATE users SET email_token='$token' WHERE name='commenter'");

    $content = loadSite("section=register&action=resendconfirmation");

    assertStringContains($content, "Confirmation email has been sent again.");
    assertEmailContains("Confirm your email address");
    queryTestDB("UPDATE users SET email_token='' WHERE name='commenter'");
}

