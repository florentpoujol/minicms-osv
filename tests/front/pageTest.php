<?php

function test_page_with_comment()
{
    $page = queryTestDB("SELECT * FROM pages WHERE slug = 'page-admin'")->fetch();

    $content = loadSite("section=page&id=$page[slug]");

    assertStringContains($content, $page["title"]);
    assertStringContains($content, $page["content"]);
    assertStringContains($content, "Comment section");
    assertStringContains($content, "New comment text"); // on of the comments
}

function test_page_without_comment()
{
    queryTestDB("UPDATE pages SET allow_comments = 0 WHERE slug = 'page-writer'");
    $page = queryTestDB("SELECT * FROM pages WHERE slug = 'page-writer'")->fetch();

    $content = loadSite("section=page&id=$page[id]");

    assertStringContains($content, $page["title"]);
    assertStringContains($content, $page["content"]);
    assertStringNotContains($content, "Comment section");
}

function test_page_not_published()
{
    $page = queryTestDB("SELECT * FROM pages WHERE published = 0 AND category_id IS NULL")->fetch();

    $content = loadSite("section=page&id=$page[id]");

    assertStringContains($content, "Error page not found");
    assertHTTPResponseCode(404);
}

function test_page_not_published_but_user_connected()
{
    $page = queryTestDB("SELECT * FROM pages WHERE published = 0 AND category_id IS NULL")->fetch();
    $admin = getUser("admin");
    $content = loadSite("section=page&id=$page[id]", $admin["id"]);

    assertStringNotContains($content, "Error page not found");
    assertStringContains($content, $page["title"]);
    assertStringContains($content, $page["content"]);
}
