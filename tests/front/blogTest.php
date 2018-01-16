<?php

function test_blog_categories()
{
    $categories = queryTestDB("SELECT * FROM categories")->fetchAll();

    $content = loadSite("section=blog");

    assertStringContains($content, "<h1>Blog</h1>");
    assertStringContains($content, "<h2>Categories</h2>");

    foreach ($categories as $category) {
        assertStringContains($content,
            '<li><a href="' . buildUrl("category", null, idOrSlug($category)) . '">' . $category["title"] . '</a></li>');
    }
    assertStringNotContains($content, "<p>No category</p>");
}

function test_blog_posts()
{
    $posts = queryTestDB("SELECT * FROM pages WHERE category_id IS NOT NULL AND published = 1")->fetchAll();

    $content = loadSite("section=blog");

    assertStringContains($content, "<h1>Blog</h1>");
    assertStringContains($content, "<h2>Categories</h2>");

    foreach ($posts as $post) {
        assertStringContains($content, '<h2><a href="' . buildUrl("post", idOrSlug($post)) . '">' . $post["title"] . '</a></h2>');
        assertStringContains($content, "Posted on $post[creation_date] by ");
        assertStringContains($content, "Category: <a href=");
        assertStringContains($content, $post["content"]);
    }
    assertStringNotContains($content, "<p>No post yet</p>");

    $post = queryTestDB("SELECT * FROM pages WHERE category_id IS NOT NULL AND published = 0")->fetch();
    assertStringNotContains($content, $post["title"]);
    assertStringNotContains($content, $post["slug"]);
}

