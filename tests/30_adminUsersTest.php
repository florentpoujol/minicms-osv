<?php

$testUser = queryTestDB("SELECT * FROM users WHERE name='commenter'")->fetch();

function test_commenter_can_only_access_the_Update_action_on_the_admin_users_page()
{
    global $testUser;
    loadSite("section=admin:users", $testUser["id"]);
    assertRedirect(buildUrl("admin:users", "update", $testUser["id"]));
}

function test_the_update_action_can_only_be_used_with_a_specified_user_id()
{
    global $testUser;
    loadSite("section=admin:users&action=update", $testUser["id"]);
    assertRedirect(buildUrl("admin:users", "update", $testUser["id"]));
}

function test_Commenter_cannot_update_other_users()
{
    global $testUser;
    // let's try specifying another id in the URL
    loadSite("section=admin:users&action=update&id=2", $testUser["id"]);
    assertRedirect(buildUrl("admin:users", "update", $testUser["id"]));
}

function test_Commenter_can_update_its_own_user()
{
    global $testUser;
    $content = loadSite("section=admin:users&action=update&id=$testUser[id]", $testUser["id"]);
    assertStringContains($content, "Edit user with id $testUser[id]");
    assertStringNotContains($content, "Block user:"); // only for admins
    assertStringNotContains($content, "<select name=\"user_role\">"); // only for admins
}

function test_POST_users_update_wrong_CSRF()
{
    global $testUser;
    $_POST["user_name"] = "commenter";
    $_POST["user_email"] = "new@emai.ll";
    $_POST["user_password"] = "";
    $_POST["user_password_confirm"] = "";
    $_POST["csrf_token"] = "wrong_token";

    $content = loadSite("section=admin:users&action=update&id=$testUser[id]", $testUser["id"]);
    assertStringContains($content, "Wrong CSRF token");
}
