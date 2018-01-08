<?php

// CREATE

function test_comments_section_dont_show_comments_disabled_in_config()
{
    global $testConfig;
    $testConfig["allow_comments"] = false;
    $content = loadSite("section=page&id=2");
    assertStringNotContains($content, "Comment section");
}

function test_comments_section_dont_show_comments_disabled_in_config_and_page_params()
{
    global $testConfig;
    $testConfig["allow_comments"] = false;
    queryTestDB("UPDATE pages SET allow_comments = 0 WHERE id = 2");

    $content = loadSite("section=page&id=2");
    assertStringNotContains($content, "Comment section");
}

function test_comments_section_dont_show_comments_enabled_in_config_but_not_in_page_params()
{
    global $testConfig;
    $testConfig["allow_comments"] = true;
    queryTestDB("UPDATE pages SET allow_comments = 0 WHERE id = 2");

    $content = loadSite("section=page&id=2");
    assertStringNotContains($content, "Comment section");

    queryTestDB("UPDATE pages SET allow_comments = 1 WHERE id = 2");
}



function test_comments_form_dont_show_when_user_not_connected()
{
    $content = loadSite("section=page&id=2");

    assertStringContains($content, "Comment section");
    assertStringContains($content, '<a href="'.buildUrl("login").'">Login to post a new comment</a>');
    $comments = queryTestDB("SELECT * FROM comments WHERE page_id = 2")->fetchAll();
    foreach ($comments as $comment) {
        assertStringContains($content, $comment["text"]);
        assertIdentical(3, $comment["user_id"]); // commenter
    }
    assertIdentical(1, count($comments));
}

function test_comments_comment_dont_show_when_user_is_banned()
{
    queryTestDB("UPDATE users SET is_banned = 1 WHERE id = 3"); // ban commenter
    $content = loadSite("section=page&id=2");

    $comments = queryTestDB("SELECT * FROM comments WHERE page_id = 2")->fetchAll();
    foreach ($comments as $comment) {
        assertStringNotContains($content, $comment["text"]); // comment exists but is not displayed
        assertIdentical(3, $comment["user_id"]); // commenter
    }
    assertIdentical(1, count($comments));

    queryTestDB("UPDATE users SET is_banned = 0 WHERE id = 3");
}

function test_comments_create_wrong_csrf()
{
    $_POST["comment_text"] = "A new comment on page 2 by admin";
    setTestCSRFToken("wrongtoken");

    $user = getUser("admin");
    $content = loadSite("section=page&id=2", $user["id"]);

    assertStringContains($content, "Leave a comment:");
    assertStringContains($content, "Wrong CSRF token for request 'commentcreate'");
}

function test_comments_create_wrong_form()
{
    $_POST["comment_text"] = "azerty";
    setTestCSRFToken("commentcreate");

    $user = getUser("admin");
    $content = loadSite("section=page&id=2", $user["id"]);

    assertStringContains($content, "Comments must have at least 10 characters");
}

function test_comments_create_success()
{
    $_POST["comment_text"] = "A new comment on page 2 by admin";
    setTestCSRFToken("commentcreate");

    $user = getUser("admin");
    $content = loadSite("section=page&id=2", $user["id"]);

    assertMessageSaved("Comment added successfully.");
    assertRedirect(buildUrl("page", null, 2));
}


// UPDATE

function test_admin_comments_update_no_id()
{
    $user = getUser("writer");
    loadSite("section=admin:comments&action=update", $user["id"]);
    assertMessageSaved("You must select a comment to update.");
    assertRedirect(buildUrl("admin:comments", "read"));
}

function test_admin_comments_update_unknow_id()
{
    $user = getUser("writer");
    loadSite("section=admin:comments&action=update&id=987", $user["id"]);
    assertMessageSaved("Unknown comment with id 987.");
    assertRedirect(buildUrl("admin:comments", "read"));
}

function test_admin_comments_commenters_can_only_update_their_own_comments()
{
    $writer = getUser("writer");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $writer["id"])->fetch();

    $commenter = getUser("commenter");
    loadSite("section=admin:comments&action=update&id=$comment[id]", $commenter["id"]);

    assertMessageSaved("You are not authorized to edit this comment.");
    assertHTTPResponseCode(403);
    assertRedirect(buildUrl("admin:comments", "read"));
}

function test_admin_comments_writers_cannot_update_comments_that_are_not_theirs_on_pages_that_are_not_theirs()
{
    $commenter = getUser("commenter");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ? AND page_id = ?", [$commenter["id"], 1])->fetch(); // get comment by commenter on page written by admin

    $writer = getUser("writer");
    loadSite("section=admin:comments&action=update&id=$comment[id]", $writer["id"]);

    assertMessageSaved("You are not authorized to edit this comment.");
    assertHTTPResponseCode(403);
    assertRedirect(buildUrl("admin:comments", "read"));
}

function test_admin_comments_writers_can_update_other_users_comments_on_pages_that_they_wrote()
{
    $commenter = getUser("commenter");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ? AND page_id = ?", [$commenter["id"], 2])->fetch(); // get comment by commenter on page written by writer

    $writer = getUser("writer");
    $content = loadSite("section=admin:comments&action=update&id=$comment[id]", $writer["id"]);

    assertStringContains($content, "Edit comment with id $comment[id]");

    // also test that the form is OK
    assertStringContains($content, '<form action="'.buildUrl("admin:comments", "update", $comment["id"]).'"');
    assertStringContains($content, $comment["text"]);
    assertStringContains($content, '<option value="'.$comment["page_id"].'" selected>');
    assertStringContains($content, '<option value="1"');
    assertStringContains($content, '<option value="'.$commenter["id"].'" selected>');
    assertStringContains($content, '<option value="'.$writer["id"].'"');
}

function test_admin_comments_commenter_see_correct_form()
{
    $commenter = getUser("commenter");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $commenter["id"])->fetch();

    $content = loadSite("section=admin:comments&action=update&id=$comment[id]", $commenter["id"]);

    assertStringContains($content, "Edit comment with id $comment[id]");
    assertStringNotContains($content, "User:");
    assertStringNotContains($content, '<select name="comment_page_id">');
}

function test_admin_comments_update_wrong_csrf()
{
    $_POST["comment_text"] = "";
    $_POST["comment_page_id"] = "";
    $_POST["comment_user_id"] = "";
    setTestCSRFToken("wrong_token");

    $user = getUser("writer");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $user["id"])->fetch();
    $content = loadSite("section=admin:comments&action=update&id=$comment[id]", $user["id"]);

    assertStringContains($content, "Wrong CSRF token for request 'commentupdate'");
}

function test_admin_comments_update_wrong_form()
{
    $_POST["comment_text"] = "";
    $_POST["comment_page_id"] = 654;
    $_POST["comment_user_id"] = 987;
    setTestCSRFToken("commentupdate");

    $writer = getUser("writer");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $writer["id"])->fetch();

    $content = loadSite("section=admin:comments&action=update&id=$comment[id]", $writer["id"]);

    assertStringContains($content, "The page with id '654' does not exist.");
    assertStringContains($content, "The user with id '987' does not exist.");
}

function test_admin_comments_update_commenter_cant_update_page_and_user_id()
{
    $commenter = getUser("commenter");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $commenter["id"])->fetch();

    $_POST["comment_text"] = "New comment text";
    $_POST["comment_page_id"] = 2; // second page
    $_POST["comment_user_id"] = 1; // admin
    setTestCSRFToken("commentupdate");

    assertStringContains($comment["text"], "A comment on page admin by commenter");
    assertIdentical($comment["user_id"], $commenter["id"]);
    assertIdentical($comment["page_id"], 1);

    $content = loadSite("section=admin:comments&action=update&id=$comment[id]", $commenter["id"]);

    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $commenter["id"])->fetch();
    assertStringContains($content, "Comment edited successfully");
    assertStringContains($comment["text"], "New comment text");
    assertIdentical($comment["user_id"], $commenter["id"]);
    assertIdentical($comment["page_id"], 1);
}

function test_admin_comments_update_success()
{
    $admin = getUser("admin");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $admin["id"])->fetch();
    // this comment is on page written by admin

    $_POST["comment_text"] = "New comment text";
    $_POST["comment_page_id"] = 2; // the other page
    $_POST["comment_user_id"] = 2; // writer
    setTestCSRFToken("commentupdate");

    assertStringContains($comment["text"], "A comment on page admin by admin");
    assertIdentical($comment["user_id"], $admin["id"]);
    assertIdentical($comment["page_id"], 1);

    $content = loadSite("section=admin:comments&action=update&id=$comment[id]", $admin["id"]);

    $comment = queryTestDB("SELECT * FROM comments WHERE id = ?", $comment["id"])->fetch();
    assertStringContains($content, "Comment edited successfully");
    assertStringContains($comment["text"], "New comment text");
    $writer = getUser("writer");
    assertIdentical($comment["user_id"], $writer["id"]);
    assertIdentical($comment["page_id"], 2);
}

// DELETE

function test_admin_comments_delete_not_for_commenters()
{
    $user = getUser("commenter");
    $token = setTestCSRFToken("commentdelete");

    loadSite("section=admin:comments&action=delete&id=1&csrftoken=$token", $user["id"]);
    assertMessageSaved("Forbidden.");
    assertHTTPResponseCode(403);
    assertRedirect(buildUrl("admin:comments", "read"));
}

function test_admin_comments_delete_wrong_csrf()
{
    $admin = getUser("admin");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $admin["id"])->fetch();
    $token = setTestCSRFToken("wrongtoken");

    loadSite("section=admin:comments&action=delete&id=$comment[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Wrong CSRF token for request 'commentdelete'");
    assertRedirect(buildUrl("admin:comments", "read"));
}

function test_admin_comments_writers_can_only_delete_their_comments_or_comments_on_their_page()
{
    $writer = getUser("writer");
    $commenter = getUser("commenter");
    $comment = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $commenter["id"])->fetch();
    //comment posted by admin on an admin page
    $token = setTestCSRFToken("commentdelete");
    loadSite("section=admin:comments&action=delete&id=$comment[id]&csrftoken=$token", $writer["id"]);

    assertMessageSaved("You can only delete your own comment or the ones posted on the pages you created.");
    assertRedirect(buildUrl("admin:comments", "read"));
}

function test_admin_comments_delete_unknown_id()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("commentdelete");

    loadSite("section=admin:comments&action=delete&id=987&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Unknown comment with id 987.");
    assertRedirect(buildUrl("admin:comments", "read"));
}

function test_admin_comments_delete_success()
{
    $admin = getUser("admin");
    $comment = queryTestDB("SELECT * FROM comments WHERE id = 1")->fetch();
    assertIdentical(true, is_array($comment));
    $token = setTestCSRFToken("commentdelete");

    loadSite("section=admin:comments&action=delete&id=1&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Comment deleted with success.");
    assertRedirect(buildUrl("admin:comments", "read"));
    $comment = queryTestDB("SELECT * FROM comments WHERE id = 1")->fetch();
    assertIdentical(false, $comment);
}

// READ

function test_admin_comments_read_commenter()
{
    $commenter = getUser("commenter");
    $content = loadSite("section=admin:comments", $commenter["id"]);

    assertStringContains($content, "List of all comments");
    assertStringContains($content, "Edit</a>");

    $comments = queryTestDB("SELECT * FROM comments WHERE user_id = ?", $commenter["id"])->fetchAll();
    foreach ($comments as $comment) {
        assertStringContains($content, $comment["text"]);
    }
    assertIdentical(2, count($comments));

    $comments = queryTestDB("SELECT * FROM comments WHERE user_id <> ?", $commenter["id"])->fetchAll();
    foreach ($comments as $comment) {
        assertStringNotContains($content, $comment["text"]);
    }
    assertIdentical(2, count($comments));
    assertStringNotContains($content, "Delete</a>");
}

function test_admin_comments_read_writer()
{
    $writer = getUser("writer");
    $content = loadSite("section=admin:comments", $writer["id"]);

    assertStringContains($content, "List of all comments");
    assertStringContains($content, "Edit</a>");
    assertStringContains($content, "Delete</a>");

    $comments = queryTestDB("SELECT * FROM comments WHERE user_id = ? OR page_id = 2", $writer["id"])->fetchAll();
    foreach ($comments as $comment) {
        assertStringContains($content, $comment["text"]);
    }
    assertIdentical(3, count($comments));

    $comments = queryTestDB("SELECT * FROM comments WHERE user_id <> ? AND page_id <> 2", $writer["id"])->fetchAll();
    foreach ($comments as $comment) {
        assertStringNotContains($content, $comment["text"]);
    }
    assertIdentical(1, count($comments));
}

function test_admin_comments_read_admin()
{
    $user = getUser("admin");
    $content = loadSite("section=admin:comments", $user["id"]);

    assertStringContains($content, "List of all comments");
    $comments = queryTestDB("SELECT * FROM comments")->fetchAll();
    foreach ($comments as $comment) {
        assertStringContains($content, $comment["text"]);
    }
    assertIdentical(4, count($comments));
    assertStringContains($content, "Edit</a>");
    assertStringContains($content, "Delete</a>");
}
