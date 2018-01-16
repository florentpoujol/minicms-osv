<?php

function test_category_1()
{
    // add a published post for category with id 1
    queryTestDB("INSERT INTO pages
    (slug, title, content, category_id, user_id, creation_date, published, allow_comments) 
    VALUES('post-2', 'Another post for cat 1', 'Another post for cat 1', 1, 1, NOW(), 1, 1)");

    $category = queryTestDB("SELECT * FROM categories where id = 1")->fetch(); // category-0
    $posts = queryTestDB("SELECT * FROM pages WHERE category_id = ?", $category["id"])->fetchAll();
    assertNotEmpty($posts);

    $content = loadSite("section=category&id=$category[id]");

    assertStringContains($content, $category["title"]);
    foreach ($posts as $post) {
        $fn = "assertStringContains";
        if ($post["published"] === 0) {
            $fn = "assertStringNotContains";
        }
        $fn($content,
            '<li><a href="' . buildUrl("blog", null, idOrSlug($post)) . '">' . $post["title"] . '</a></li>'
        );
    }
    assertStringNotContains($content, "<p>No posts in this category</p>");
}

function test_category_2()
{
    $category = queryTestDB("SELECT * FROM categories where id = 2")->fetch(); // category-3
    $posts = queryTestDB("SELECT * FROM pages WHERE category_id = ?", $category["id"])->fetchAll();
    assertEmpty($posts);

    $content = loadSite("section=category&id=$category[id]");

    assertStringContains($content, "<p>No posts in this category</p>");
}
