<?php

function test_admin_pages_not_for_commenters()
{
    $user = getUser("commenter");
    loadSite("section=admin:pages", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
    assertHTTPResponseCode(403);
}

// common checks between create and update actions

function test_admin_pages_create_wrong_form()
{
    // wrong name, slug, parent_page that doesn't exists, user that don't exists
    $_POST["title"] = "Pag";
    $_POST["slug"] = "page 1";
    $_POST["parent_page_id"] = 987;
    $_POST["user_id"] = 3; // id of the commenter, he exists but can't own a page
    setTestCSRFToken("pagescreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:pages&action=create", $user["id"]);

    assertStringContains($content, "The title must be at least");
    assertStringContains($content, "The slug has the wrong format.");
    assertStringContains($content, "The parent page with id '987' does not exists.");
    assertStringContains($content, "User with id 3 doesn't exists or is not a writer or admin.");
}

function test_admin_pages_create_wrong_form_2()
{
    // slug already exists create + update
    // parent page that isn't a parent page

    queryTestDB("UPDATE pages SET parent_page_id = 0 WHERE slug = 'page-admin'"); // just set thee parent page id to something else than NULL so that it is considered a child
    $parentPage = queryTestDB("SELECT * FROM pages WHERE slug = 'page-admin'")->fetch();

    $_POST["title"] = "Page 2";
    $_POST["slug"] = "page-admin"; // already exists
    $_POST["parent_page_id"] = $parentPage["id"]; // already exists
    setTestCSRFToken("pagescreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:pages&action=create", $user["id"]);

    assertStringContains($content, "The selected parent page (with id '$parentPage[id]') is actually a children of another page (with id '$parentPage[parent_page_id]'), so it can't be a parent page itself.");
    assertStringContains($content, "The page with id $parentPage[id] and title '$parentPage[title]' already has the slug 'page-admin'.");
}

// CREATE

function test_admin_pages_create_writer_see_correct_form()
{
    $user = getUser("writer");
    $content = loadSite("section=admin:pages&action=create", $user["id"]);
    assertStringContains($content, buildUrl("admin:pages", "create"));
    assertStringContains($content, "Add a new page");
    assertStringNotContains($content, ">View page"); // preview link
    assertStringNotContains($content, "Category");
    assertStringNotContains($content, "Owner");
}

function test_admin_pages_create_admin_see_correct_form()
{
    $user = getUser("admin");
    $content = loadSite("section=admin:pages&action=create", $user["id"]);
    assertStringContains($content, buildUrl("admin:pages", "create"));
    assertStringContains($content, "Add a new page");
    assertStringNotContains($content, ">View page"); // preview link
    assertStringNotContains($content, "Category");
    assertStringContains($content, "Owner:");
}

function test_admin_pages_create_wrong_csrf()
{
    $_POST["title"] = "Page 1";
    setTestCSRFToken("wrongtoken");

    $user = getUser("admin");
    $content = loadSite("section=admin:pages&action=create", $user["id"]);
    assertStringContains($content, "Add a new page");
    assertStringContains($content, "Wrong CSRF token for request 'pagescreate'");
}

function test_admin_pages_create_parent_success()
{
    $admin = getUser("admin");

    $_POST["title"] = "Parent Page 1";
    $_POST["slug"] = "parent-page-1";
    $_POST["content"] = "Content of the parent page 1";
    $_POST["user_id"] = $admin["id"];
    $_POST["allow_comments"] = 1;
    setTestCSRFToken("pagescreate");

    loadSite("section=admin:pages&action=create", $admin["id"]);

    assertMessageSaved("Page added with success.");
    $page = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();
    assertRedirect(buildUrl("admin:pages", "update", $page["id"]));
    assertIdentical("Parent Page 1", $page["title"]);
    assertIdentical("parent-page-1", $page["slug"]);
    assertIdentical("Content of the parent page 1", $page["content"]);
    assertIdentical(null, $page["parent_page_id"]);
    assertIdentical($admin["id"], $page["user_id"]);
    assertIdentical(0, $page["published"]);
    assertIdentical(1, $page["allow_comments"]);
}

function test_admin_pages_create_child_success()
{
    $admin = getUser("admin");
    $writer = getUser("writer");
    $parentPage = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();

    $_POST["title"] = "Child Page 1";
    $_POST["slug"] = "child-page-1";
    $_POST["content"] = "Content of the child page 1";
    $_POST["parent_page_id"] = $parentPage["id"];
    $_POST["user_id"] = $writer["id"];
    $_POST["published"] = 1;
    $_POST["allow_comments"] = "on";
    setTestCSRFToken("pagescreate");

    loadSite("section=admin:pages&action=create", $admin["id"]);

    assertMessageSaved("Page added with success.");
    $page = queryTestDB("SELECT * FROM pages WHERE slug='child-page-1'")->fetch();
    assertRedirect(buildUrl("admin:pages", "update", $page["id"]));
    assertIdentical("Child Page 1", $page["title"]);
    assertIdentical("child-page-1", $page["slug"]);
    assertIdentical("Content of the child page 1", $page["content"]);
    assertIdentical($parentPage["id"], $page["parent_page_id"]);
    assertIdentical($writer["id"], $page["user_id"]);
    assertIdentical(1, $page["published"]);
    assertIdentical(1, $page["allow_comments"]);
}

// UPDATE

function test_admin_pages_update_writer_see_correct_form()
{
    $page = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();
    $user = getUser("writer");
    $content = loadSite("section=admin:pages&action=update&id=$page[id]", $user["id"]);

    assertStringContains($content, buildUrl("admin:pages", "update", $page["id"]));
    assertStringContains($content, "Edit page with id $page[id]");
    assertStringContains($content, ">View page"); // preview link
    assertStringNotContains($content, "Category");
    assertStringNotContains($content, "Owner");
}

function test_admin_pages_update_admin_see_correct_form()
{
    $page = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();
    $user = getUser("admin");
    $content = loadSite("section=admin:pages&action=update&id=$page[id]", $user["id"]);

    assertStringContains($content, buildUrl("admin:pages", "update", $page["id"]));
    assertStringContains($content, "Edit page with id $page[id]");
    assertStringContains($content, ">View page"); // preview link
    assertStringNotContains($content, "Category");
    assertStringContains($content, "Owner:");
}

function test_admin_pages_update_wrong_csrf()
{
    $user = getUser("writer");
    $_POST["title"] = "";
    setTestCSRFToken("wrongtoken");
    $parent = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();

    $content = loadSite("section=admin:pages&action=update&id=$parent[id]", $user["id"]);
    assertStringContains($content, "Wrong CSRF token for request 'pagesupdate'");
}

function test_admin_pages_update_no_id()
{
    $user = getUser("writer");

    loadSite("section=admin:pages&action=update", $user["id"]);
    assertMessageSaved("You must select a page to update.");
    assertRedirect(buildUrl("admin:pages", "read"));
}

function test_admin_pages_update_unknow_id()
{
    $user = getUser("writer");
    loadSite("section=admin:pages&action=update&id=987", $user["id"]);
    assertMessageSaved("Unknown page with id 987.");
    assertRedirect(buildUrl("admin:pages", "read"));
}

function test_admin_pages_update_slug_exists()
{
    $parent = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();
    $child = queryTestDB("SELECT * FROM pages WHERE slug='child-page-1'")->fetch();

    $_POST["title"] = "New child page title";
    $_POST["slug"] = $parent["slug"];
    setTestCSRFToken("pagesupdate");

    $user = getUser("writer");
    $content = loadSite("section=admin:pages&action=update&id=$child[id]", $user["id"]);

    assertStringContains($content, "The page with id $parent[id] and title '$parent[title]' already has the slug '$parent[slug]'.");
}

function test_admin_pages_update_success()
{
    $parent = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();
    $child = queryTestDB("SELECT * FROM pages WHERE slug='child-page-1'")->fetch();
    $admin = getUser("admin");
    $writer = getUser("writer");

    assertIdentical("Child Page 1", $child["title"]);
    assertIdentical("child-page-1", $child["slug"]);
    assertIdentical("Content of the child page 1", $child["content"]);
    assertIdentical($parent["id"], $child["parent_page_id"]);
    assertIdentical($writer["id"], $child["user_id"]);
    assertIdentical(1, $child["published"]);
    assertIdentical(1, $child["allow_comments"]);

    $_POST["title"] = "New title for old child page";
    $_POST["content"] = "New content for old child page";
    $_POST["user_id"] = $admin["id"]; // old owner is writter
    $_POST["parent_page_id"] = 0; // sets to null in DB
    $_POST["published"] = 0;
    // allow_comments is not setto 0
    setTestCSRFToken("pagesupdate");

    loadSite("section=admin:pages&action=update&id=$child[id]", $admin["id"]);

    assertMessageSaved("Page edited with success.");
    assertRedirect(buildUrl("admin:pages", "update", $child["id"]));

    $child = queryTestDB("SELECT * FROM pages WHERE slug='child-page-1'")->fetch();

    assertIdentical($_POST["title"], $child["title"]);
    assertIdentical("child-page-1", $child["slug"]);
    assertIdentical($_POST["content"], $child["content"]);
    assertIdentical(null, $child["parent_page_id"]);
    assertIdentical($admin["id"], $child["user_id"]);
    assertIdentical(0, $child["published"]);
    assertIdentical(0, $child["allow_comments"]);
}

// DELETE

function test_admin_pages_writer_cant_delete_page_they_dont_own()
{
    $admin = getUser("admin");
    $parent = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();
    assertIdentical($admin["id"], $parent["user_id"]);
    $token = setTestCSRFToken("deletepages");

    $user = getUser("writer");
    loadSite("section=admin:pages&action=delete&id=$parent[id]&csrftoken=$token", $user["id"]);

    assertMessageSaved("As a writer, you can only delete your own pages.");
    assertRedirect(buildUrl("admin:pages", "read"));
}

function test_admin_pages_delete_wrong_csrf()
{
    $admin = getUser("admin");
    $parent = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();
    $token = setTestCSRFToken("wrongtoken");

    loadSite("section=admin:pages&action=delete&id=$parent[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Wrong CSRF token for request 'deletepages'");
    assertRedirect(buildUrl("admin:pages", "read"));
}

function test_admin_pages_delete_unknown_id()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("deletepages");

    loadSite("section=admin:pages&action=delete&id=987&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Unknown page with id 987.");
    assertRedirect(buildUrl("admin:pages", "read"));
}

function test_admin_pages_delete_success()
{
    $parent = queryTestDB("SELECT * FROM pages WHERE slug='parent-page-1'")->fetch();
    $child = queryTestDB("SELECT * FROM pages WHERE slug='child-page-1'")->fetch();

    // reset child as an actual child of parent
    queryTestDB("UPDATE pages SET parent_page_id = ? WHERE id = ?", [$parent["id"], $child["id"]]);
    $child = queryTestDB("SELECT * FROM pages WHERE slug='child-page-1'")->fetch();
    assertIdentical($parent["id"], $child["parent_page_id"]);

    // create a comment for the parent page
    queryTestDB("INSERT INTO comments(text, page_id, user_id, creation_time) 
    VALUES('comment on parent page', $parent[id], 1, ".time().")");
    $comment = queryTestDB("SELECT * FROM comments WHERE page_id = $parent[id]")->fetch();
    assertIdentical(true, is_array($comment));

    $admin = getUser("admin");
    $token = setTestCSRFToken("deletepages");

    loadSite("section=admin:pages&action=delete&id=$parent[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Page deleted with success.");
    assertRedirect(buildUrl("admin:pages", "read"));

    $comment = queryTestDB("SELECT * FROM comments WHERE page_id = ?", $parent["id"])->fetch();
    assertIdentical(false, $comment);

    $parent = queryTestDB("SELECT * FROM pages WHERE id = ?", $parent["id"])->fetch();
    assertIdentical(false, $parent);

    $child = queryTestDB("SELECT * FROM pages WHERE id = ?", $child["id"])->fetch();
    assertIdentical(null, $child["parent_page_id"]);
}

// READ

function test_admin_pages_read_writer()
{
    // admin still owns a page , the one with the "page-admin" slug
    // make the child page an actual child of that page, again
    $parent = queryTestDB("SELECT * FROM pages WHERE slug='page-admin'")->fetch();
    $child = queryTestDB("SELECT * FROM pages WHERE slug='child-page-1'")->fetch();
    queryTestDB("UPDATE pages SET parent_page_id = ? WHERE id = ?", [$parent["id"], $child["id"]]);
    $child = queryTestDB("SELECT * FROM pages WHERE slug='child-page-1'")->fetch();
    assertIdentical($parent["id"], $child["parent_page_id"]);

    $user = getUser("writer");
    $content = loadSite("section=admin:pages", $user["id"]);

    assertStringContains($content, "List of all pages");
    assertStringContains($content, $child["title"]);
    assertStringContains($content, $child["slug"]);
    assertStringContains($content, $parent["title"]);
    assertStringContains($content, $parent["slug"]);
    assertStringContains($content, "Edit</a>");
    assertStringContains($content, "Delete</a>");
}
