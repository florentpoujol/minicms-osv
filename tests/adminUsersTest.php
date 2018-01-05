<?php

// CREATE

function test_admin_users_create_not_for_commenters()
{
    $user = getUser("commenter");
    loadSite("section=admin:users&action=create", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
    assertHTTPResponseCode(403);
}

function test_admin_users_create_not_for_writers()
{
    $user = getUser("writer");
    loadSite("section=admin:users&action=create", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
    assertHTTPResponseCode(403);
}

function test_admin_users_create_wrong_csrf()
{
    $_POST["user_name"] = "newUser";
    $_POST["user_email"] = "new@email.fr";
    $_POST["user_password"] = "Az3rty";
    $_POST["user_password_confirm"] = "Az3rty";
    $_POST["user_role"] = "writer";
    $_POST["csrf_token"] = "wrong_token";

    $user = getUser("admin");
    $content = loadSite("section=admin:users&action=create", $user["id"]);
    assertStringContains($content, "Add a new user");
    assertStringContains($content, "Wrong CSRF token for request 'usercreate'");
}

function test_admin_users_create_wrong_form()
{
    $_POST["user_name"] = "new User";
    $_POST["user_email"] = "new@email";
    $_POST["user_password"] = "Azerty";
    $_POST["user_password_confirm"] = "sdf";
    $_POST["user_role"] = "aerz";
    setTestCSRFToken("usercreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:users&action=create", $user["id"]);
    assertStringContains($content, "The name has the wrong format");
    assertStringContains($content, "The email has the wrong format");
    assertStringContains($content, "The password must be at least");
    assertStringContains($content, "The password confirmation does not match the password");
    assertStringContains($content, "Role must be 'commenter', 'writer' or 'admin'");
}

function test_admin_users_create_user_with_name_already_exists()
{
    $_POST["user_name"] = "commenter";
    $_POST["user_email"] = "new@email.fr";
    $_POST["user_password"] = "Az3rty";
    $_POST["user_password_confirm"] = "Az3rty";
    $_POST["user_role"] = "writer";
    setTestCSRFToken("usercreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:users&action=create", $user["id"]);
    assertStringContains($content, "A user already exists with that name or email");
}

function test_admin_users_create_user_with_email_already_exists()
{
    $user = getUser("commenter");
    $_POST["user_name"] = "newUser";
    $_POST["user_email"] = $user["email"];
    $_POST["user_password"] = "Az3rty";
    $_POST["user_password_confirm"] = "Az3rty";
    $_POST["user_role"] = "writer";
    setTestCSRFToken("usercreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:users&action=create", $user["id"]);
    assertStringContains($content, "A user already exists with that name or email");
}

function test_admin_users_create_success()
{
    $_POST["user_name"] = "newUser";
    $_POST["user_email"] = "new@email.fr";
    $_POST["user_password"] = "Az3rty";
    $_POST["user_password_confirm"] = "Az3rty";
    $_POST["user_role"] = "writer";
    setTestCSRFToken("usercreate");

    $user = getUser("admin");
    loadSite("section=admin:users&action=create", $user["id"]);

    $user = getUser("newUser");
    assertMessageSaved("User added successfully");
    assertRedirect(buildUrl("admin:users", "edit", $user["id"]));
    assertIdentical("newUser", $user["name"]);
    assertIdentical("new@email.fr", $user["email"]);
    assertIdentical("writer", $user["role"]);
}

// UPDATE

function test_admin_users_commenter_can_only_access_the_update_action()
{
    $user = getUser("commenter");
    loadSite("section=admin:users", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
}

function test_admin_users_update_can_only_be_used_with_a_specified_user_id_commenter()
{
    $user = getUser("commenter");
    loadSite("section=admin:users&action=update", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
}
function test_admin_users_update_can_only_be_used_with_a_specified_user_id_writer()
{
    $user = getUser("writer");
    loadSite("section=admin:users&action=update", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
}
function test_admin_users_update_can_only_be_used_with_a_specified_user_id_admin()
{
    $user = getUser("admin");
    loadSite("section=admin:users&action=update", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
}

function test_admin_users_commenter_cannot_update_other_users()
{
    $user = getUser("commenter");
    $admin = getUser("admin");
    // let's try specifying another id in the URL
    loadSite("section=admin:users&action=update&id=$admin[id]", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
}
function test_admin_users_writer_cannot_update_other_users()
{
    $user = getUser("writer");
    $admin = getUser("admin");
    // let's try specifying another id in the URL
    loadSite("section=admin:users&action=update&id=$admin[id]", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
}

function test_admin_users_commenter_can_update_its_own_user()
{
    $user = getUser("commenter");
    $content = loadSite("section=admin:users&action=update&id=$user[id]", $user["id"]);
    assertStringContains($content, "Edit user with id $user[id]");
    assertStringNotContains($content, "Block user:"); // only for admins
    assertStringContainsRegex($content, "/Role:[ \n]+commenter/");
    assertStringNotContains($content, "<select name=\"user_role\">"); // only for admins
}
function test_admin_users_writer_can_update_its_own_user()
{
    $user = getUser("writer");
    $content = loadSite("section=admin:users&action=update&id=$user[id]", $user["id"]);
    assertStringContains($content, "Edit user with id $user[id]");
    assertStringNotContains($content, "Block user:"); // only for admins
    assertStringContainsRegex($content, "/Role:[ \n]+writer/");
    assertStringNotContains($content, "<select name=\"user_role\">"); // only for admins
}
function test_admin_users_admin_can_update_its_own_user()
{
    $user = getUser("admin");
    $content = loadSite("section=admin:users&action=update&id=$user[id]", $user["id"]);
    assertStringContains($content, "Edit user with id $user[id]");
    assertStringContains($content, "Block user:");
    assertStringContains($content, "<select name=\"user_role\">");
}

function test_admin_users_update_wrong_form()
{
    $_POST["user_name"] = "commenter";
    $_POST["user_email"] = "new@emai";
    $_POST["user_password"] = "sdf";
    $_POST["user_password_confirm"] = "aze";
    setTestCSRFToken("userupdate");

    $user = getUser("commenter");
    $content = loadSite("section=admin:users&action=update&id=$user[id]", $user["id"]);
    assertStringContains($content, "The email has the wrong format");
    assertStringContains($content, "The password must be at least");
    assertStringContains($content, "The password confirmation does not match the password");
}

function test_admin_users_update_success()
{
    $_POST["user_name"] = "newcommenter";
    $_POST["user_email"] = "new@email.fr";
    $_POST["user_password"] = "Azerty2";
    $_POST["user_password_confirm"] = "Azerty2";
    $_POST["is_banned"] = "on"; // no effect with commenter
    setTestCSRFToken("userupdate");

    $user = getUser("commenter");
    $content = loadSite("section=admin:users&action=update&id=$user[id]", $user["id"]);
    assertStringContains($content, "Modification saved");

    $oldUserId = $user["id"];
    $user = getUser("newcommenter");
    assertIdentical($oldUserId, $user["id"]);
    assertIdentical($user["name"], "newcommenter");
    assertIdentical($user["email"], "new@email.fr");
    assertIdentical($user["role"], "commenter");
    assertIdentical($user["is_banned"], 0);
    assertIdentical(true, password_verify("Azerty2", $user["password_hash"]));
}

// DELETE

function test_admin_users_delete_not_for_commenters()
{
    $user = getUser("commenter");
    loadSite("section=admin:users&action=delete", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
    assertHTTPResponseCode(403);
}
function test_admin_users_delete_not_for_writers()
{
    $user = getUser("writer");
    loadSite("section=admin:users&action=delete", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
    assertHTTPResponseCode(403);
}

function test_admin_users_delete_wrong_csrf()
{
    $admin = getUser("admin");
    $commenter = getUser("commenter");
    $token = setTestCSRFToken("wrong_request");

    loadSite("section=admin:users&action=delete&id=$commenter[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Wrong CSRF token for request 'deleteuser'");
    assertRedirect(buildUrl("admin:users"));
}

function test_admin_users_delete_cant_delete_own_user()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("deleteuser");

    loadSite("section=admin:users&action=delete&id=$admin[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Can't delete your own user");
    assertRedirect(buildUrl("admin:users"));
}

function test_admin_users_delete_success()
{
    $admin = getUser("admin");
    $writer = getUser("writer");
    assertNotEmpty($writer);

    $token = setTestCSRFToken("deleteuser");
    loadSite("section=admin:users&action=delete&id=$writer[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("User with id $writer[id] has been successfully deleted.");

    $writer = getUser("writer");
    assertIdentical(false, $writer);
    assertIdentical(2, (int)(queryTestDB("SELECT COUNT(*) FROM users")->fetch()["COUNT(*)"]));
}
// pages and medias re-attribution are tested in their respective test files

// READ

function test_admin_users_read_writer()
{
    $user = getUser("writer");

    $content = loadSite("section=admin:users", $user["id"]);

    assertStringContains($content, "List of all users");
    assertStringContains($content, "com@email.com");
    assertStringContains($content, "writer@email.com");
    assertStringContains($content, "admin@email.com");
    assertStringContains($content, "Edit</a>");
    assertStringNotContains($content, "Delete</a>");
}

function test_admin_users_read_admin()
{
    $user = getUser("admin");

    $content = loadSite("section=admin:users", $user["id"]);

    assertStringContains($content, "List of all users");
    assertStringContains($content, "com@email.com");
    assertStringContains($content, "writer@email.com");
    assertStringContains($content, "admin@email.com");
    assertStringContains($content, "Edit</a>");
    assertStringContains($content, "Delete</a>");
}
