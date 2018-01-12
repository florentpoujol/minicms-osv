<?php

function test_admin_posts_not_for_commenters()
{
    $user = getUser("commenter");
    loadSite("section=admin:posts", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
    assertHTTPResponseCode(403);
}

// common checks between create and update actions

function test_admin_posts_create_wrong_form()
{
    // wrong name, slug, category_id that doesn't exists, user that don't exists
    $_POST["title"] = "Pos";
    $_POST["slug"] = "post 1";
    $_POST["category_id"] = 987;
    $_POST["user_id"] = 3; // id of the commenter, he exists but can't own a post
    setTestCSRFToken("postscreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:posts&action=create", $user["id"]);

    assertStringContains($content, "The title must be at least");
    assertStringContains($content, "The slug has the wrong format.");
    assertStringContains($content, "The category with id 987 does not exists.");
    assertStringContains($content, "User with id 3 doesn't exists or is not a writer or admin.");
}

function test_admin_posts_create_wrong_form_2()
{
    // slug already exists create + update
    $_POST["title"] = "Post 2";
    $_POST["slug"] = "post-1"; // already exists
    setTestCSRFToken("postscreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:posts&action=create", $user["id"]);

    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-1'")->fetch();
    assertStringContains($content, "The post with id $post[id] and title '$post[title]' already has the slug 'post-1'.");
}

// CREATE

function test_admin_posts_create_writer_see_correct_form()
{
    $user = getUser("writer");
    $content = loadSite("section=admin:posts&action=create", $user["id"]);
    assertStringContains($content, buildUrl("admin:posts", "create"));
    assertStringContains($content, "Add a new post");
    assertStringNotContains($content, ">View post"); // preview link
    assertStringContains($content, "Category");
    assertStringNotContains($content, "Parent Page");
    assertStringNotContains($content, "Owner");
}

function test_admin_posts_create_admin_see_correct_form()
{
    $user = getUser("admin");
    $content = loadSite("section=admin:posts&action=create", $user["id"]);
    assertStringContains($content, buildUrl("admin:posts", "create"));
    assertStringContains($content, "Add a new post");
    assertStringNotContains($content, ">View post"); // preview link
    assertStringContains($content, "Category");
    assertStringNotContains($content, "Parent Page");
    assertStringContains($content, "Owner:");
}

function test_admin_posts_create_wrong_csrf()
{
    $_POST["title"] = "Post 1";
    setTestCSRFToken("wrongtoken");

    $user = getUser("admin");
    $content = loadSite("section=admin:posts&action=create", $user["id"]);
    assertStringContains($content, "Add a new post");
    assertStringContains($content, "Wrong CSRF token for request 'postscreate'");
}

function test_admin_posts_create_success()
{
    $admin = getUser("admin");
    $category = queryTestDB("SELECT * FROM categories")->fetch();

    $_POST["title"] = "Post 2";
    $_POST["slug"] = "post-2";
    $_POST["content"] = "Content of the post 2";
    $_POST["user_id"] = $admin["id"];
    $_POST["category_id"] = $category["id"];
    $_POST["allow_comments"] = 1;
    setTestCSRFToken("postscreate");

    loadSite("section=admin:posts&action=create", $admin["id"]);

    assertMessageSaved("Post added with success.");
    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-2'")->fetch();
    assertRedirect(buildUrl("admin:posts", "update", $post["id"]));
    assertIdentical("Post 2", $post["title"]);
    assertIdentical("post-2", $post["slug"]);
    assertIdentical("Content of the post 2", $post["content"]);
    assertIdentical($category["id"], $post["category_id"]);
    assertIdentical($admin["id"], $post["user_id"]);
    assertIdentical(0, $post["published"]);
    assertIdentical(1, $post["allow_comments"]);
}

// UPDATE

function test_admin_posts_update_writer_see_correct_form()
{
    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-1'")->fetch();
    $user = getUser("writer");
    $content = loadSite("section=admin:posts&action=update&id=$post[id]", $user["id"]);

    assertStringContains($content, buildUrl("admin:posts", "update", $post["id"]));
    assertStringContains($content, "Edit post with id $post[id]");
    assertStringContains($content, ">View post"); // preview link
    assertStringContains($content, "Category");
    assertStringNotContains($content, "Owner");
}

function test_admin_posts_update_admin_see_correct_form()
{
    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-1'")->fetch();
    $user = getUser("admin");
    $content = loadSite("section=admin:posts&action=update&id=$post[id]", $user["id"]);

    assertStringContains($content, buildUrl("admin:posts", "update", $post["id"]));
    assertStringContains($content, "Edit post with id $post[id]");
    assertStringContains($content, ">View post"); // preview link
    assertStringContains($content, "Category");
    assertStringContains($content, "Owner:");
}

function test_admin_posts_update_wrong_csrf()
{
    $user = getUser("writer");
    $_POST["title"] = "";
    setTestCSRFToken("wrongtoken");
    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-1'")->fetch();

    $content = loadSite("section=admin:posts&action=update&id=$post[id]", $user["id"]);
    assertStringContains($content, "Wrong CSRF token for request 'postsupdate'");
}

function test_admin_posts_update_no_id()
{
    $user = getUser("writer");

    loadSite("section=admin:posts&action=update", $user["id"]);
    assertMessageSaved("You must select a post to update.");
    assertRedirect(buildUrl("admin:posts", "read"));
}

function test_admin_posts_update_unknow_id()
{
    $user = getUser("writer");
    loadSite("section=admin:posts&action=update&id=987", $user["id"]);
    assertMessageSaved("Unknown post with id 987.");
    assertRedirect(buildUrl("admin:posts", "read"));
}

function test_admin_posts_update_slug_exists()
{
    $post1 = queryTestDB("SELECT * FROM pages WHERE slug='post-1'")->fetch();
    $post2 = queryTestDB("SELECT * FROM pages WHERE slug='post-2'")->fetch();

    $_POST["title"] = "New post title";
    $_POST["slug"] = $post1["slug"];
    setTestCSRFToken("postsupdate");

    $user = getUser("writer");
    $content = loadSite("section=admin:posts&action=update&id=$post2[id]", $user["id"]);

    assertStringContains($content, "The post with id $post1[id] and title '$post1[title]' already has the slug '$post1[slug]'.");
}

function test_admin_posts_update_success()
{
    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-1'")->fetch();
    $category1 = queryTestDB("SELECT * FROM categories WHERE id = ?", $post["category_id"])->fetch();
    $category0 = queryTestDB("SELECT * FROM categories WHERE id = ?", 1)->fetch();

    $admin = getUser("admin");
    $writer = getUser("writer");

    assertIdentical("The first post", $post["title"]);
    assertIdentical("post-1", $post["slug"]);
    assertIdentical("The content of the first post", $post["content"]);
    assertIdentical($category1["id"], $post["category_id"]);
    assertIdentical($admin["id"], $post["user_id"]);
    assertIdentical(1, $post["published"]);
    assertIdentical(1, $post["allow_comments"]);

    $_POST["title"] = "New title for post 1";
    $_POST["content"] = "New content for post 1";
    $_POST["user_id"] = $writer["id"]; // old owner is writter
    $_POST["category_id"] = $category0["id"];
    $_POST["published"] = 0;
    // allow_comments is not setto 0
    setTestCSRFToken("postsupdate");

    loadSite("section=admin:posts&action=update&id=$post[id]", $admin["id"]);

    assertMessageSaved("Post edited with success.");
    assertRedirect(buildUrl("admin:posts", "update", $post["id"]));

    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-1'")->fetch();

    assertIdentical($_POST["title"], $post["title"]);
    assertIdentical("post-1", $post["slug"]);
    assertIdentical($_POST["content"], $post["content"]);
    assertIdentical($category0["id"], $post["category_id"]);
    assertIdentical($writer["id"], $post["user_id"]);
    assertIdentical(0, $post["published"]);
    assertIdentical(0, $post["allow_comments"]);
}

// DELETE

function test_admin_posts_writer_cant_delete_post_they_dont_own()
{
    $admin = getUser("admin");
    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-2'")->fetch();
    assertIdentical($admin["id"], $post["user_id"]);
    $token = setTestCSRFToken("deleteposts");

    $writer = getUser("writer");
    loadSite("section=admin:posts&action=delete&id=$post[id]&csrftoken=$token", $writer["id"]);

    // printSavedMessages();
    assertMessageSaved("As a writer, you can only delete your own posts.");
    assertRedirect(buildUrl("admin:posts", "read"));
}

function test_admin_posts_delete_wrong_csrf()
{
    $admin = getUser("admin");
    $parent = queryTestDB("SELECT * FROM pages WHERE slug='post-2'")->fetch();
    $token = setTestCSRFToken("wrongtoken");

    loadSite("section=admin:posts&action=delete&id=$parent[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Wrong CSRF token for request 'deleteposts'");
    assertRedirect(buildUrl("admin:posts", "read"));
}

function test_admin_posts_delete_unknown_id()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("deleteposts");

    loadSite("section=admin:posts&action=delete&id=987&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Unknown post with id 987.");
    assertRedirect(buildUrl("admin:posts", "read"));
}

function test_admin_posts_delete_success()
{
    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-2'")->fetch();

    // create a comment for the post
    queryTestDB("INSERT INTO comments(text, page_id, user_id, creation_time) 
    VALUES('comment on post 1', $post[id], 1, ".time().")");
    $comment = queryTestDB("SELECT * FROM comments WHERE page_id = $post[id]")->fetch();
    assertIdentical(true, is_array($comment));

    $admin = getUser("admin");
    $token = setTestCSRFToken("deleteposts");

    loadSite("section=admin:posts&action=delete&id=$post[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Post deleted with success.");
    assertRedirect(buildUrl("admin:posts", "read"));

    $comment = queryTestDB("SELECT * FROM comments WHERE page_id = ?", $post["id"])->fetch();
    assertIdentical(false, $comment);

    $post = queryTestDB("SELECT * FROM pages WHERE id = ?", $post["id"])->fetch();
    assertIdentical(false, $post);
}

// READ

function test_admin_posts_read_writer()
{
    $post = queryTestDB("SELECT * FROM pages WHERE slug='post-1'")->fetch();

    $user = getUser("writer");
    $content = loadSite("section=admin:posts", $user["id"]);

    assertStringContains($content, "List of all posts");
    assertStringContains($content, $post["title"]);
    assertStringContains($content, $post["slug"]);
    assertStringContains($content, "Edit</a>");
    assertStringContains($content, "Delete</a>");
}
