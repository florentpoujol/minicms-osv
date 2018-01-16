<?php

function test_post_with_comment()
{
    $post = queryTestDB("SELECT * FROM pages WHERE slug = 'post-2'")->fetch();

    $content = loadSite("section=post&id=$post[id]");

    assertStringContains($content, "Posted on $post[creation_date] by ");
    assertStringContains($content, "Category: <a href=");
    assertStringContains($content, $post["title"]);
    assertStringContains($content, $post["content"]);
    assertStringContains($content, "Comment section");
}

function test_post_without_comment()
{
    $post = queryTestDB("SELECT * FROM pages WHERE slug = 'post-1'")->fetch();
    $admin = getUser("admin");
    $content = loadSite("section=post&id=$post[slug]", $admin["id"]);

    assertStringContains($content, $post["title"]);
    assertStringContains($content, $post["content"]);
    assertStringNotContains($content, "Comment section");
}

function test_post_not_published()
{
    $post = queryTestDB("SELECT * FROM pages WHERE published = 0 AND category_id IS NOT NULL")->fetch();

    $content = loadSite("section=post&id=$post[id]");

    assertStringContains($content, "Error page not found");
    assertHTTPResponseCode(404);
}

function test_post_not_published_but_user_connected()
{
    $post = queryTestDB("SELECT * FROM pages WHERE published = 0 AND category_id IS NOT NULL")->fetch();
    $admin = getUser("admin");
    $content = loadSite("section=post&id=$post[slug]", $admin["id"]);

    assertStringNotContains($content, "Error post not found");
    assertStringContains($content, $post["title"]);
    assertStringContains($content, $post["content"]);
}
