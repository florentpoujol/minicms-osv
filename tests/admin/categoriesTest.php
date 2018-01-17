<?php

function test_admin_categories_not_for_commenters()
{
    $user = getUser("commenter");
    loadSite("section=admin:categories", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
}

// CREATE

function test_admin_categories_create_wrong_csrf()
{
    $_POST["title"] = "category 1";
    $_POST["slug"] = "category-1";
    $_POST["csrf_token"] = "wrong_token";

    $user = getUser("admin");
    $content = loadSite("section=admin:categories&action=create", $user["id"]);
    assertStringContains($content, "Add a new category");
    assertStringContains($content, "Wrong CSRF token for request 'categorycreate'");
}

function test_admin_categories_create_wrong_form()
{
    $_POST["title"] = "cat";
    $_POST["slug"] = "category 1";
    setTestCSRFToken("categorycreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:categories&action=create", $user["id"]);
    assertStringContains($content, "The title must be at least");
    assertStringContains($content, "The slug has the wrong format");
}

function test_admin_categories_create_success()
{
    $_POST["title"] = "Category 1";
    $_POST["slug"] = "category-1";
    setTestCSRFToken("categorycreate");

    $user = getUser("admin");
    loadSite("section=admin:categories&action=create", $user["id"]);

    assertMessageSaved("Category added with success.");

    $category = queryTestDB("SELECT * FROM categories WHERE slug='category-1'")->fetch();
    assertDifferent($category, false);
    assertRedirect(buildUrl("admin:categories", "update", $category["id"]));
    assertIdentical("Category 1", $category["title"]);
    assertIdentical("category-1", $category["slug"]);
}

function test_admin_categories_create_already_exists()
{
    $_POST["title"] = "Category 2";
    $_POST["slug"] = "category-1";
    setTestCSRFToken("categorycreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:categories&action=create", $user["id"]);
    assertStringContains($content, "The category with id 2 and title 'Category 1' already has the slug 'category-1'.");

    $category = queryTestDB("SELECT * FROM categories WHERE slug='category-1'")->fetch();
    assertDifferent($category, false);

}

// UPDATE

function test_admin_categories_update_no_id()
{
    $user = getUser("writer");
    loadSite("section=admin:categories&action=update", $user["id"]);
    assertMessageSaved("You must select a category to update.");
    assertRedirect(buildUrl("admin:categories", "read"));

    $category = queryTestDB("SELECT * FROM categories WHERE slug='category-1'")->fetch();
    assertDifferent($category, false);
}

function test_admin_categories_update_unknown_id()
{
    $user = getUser("writer");
    loadSite("section=admin:categories&action=update&id=987", $user["id"]);

    assertMessageSaved("Unknown category with id 987");
    assertRedirect(buildUrl("admin:categories", "read"));
}

function test_admin_categories_update_read()
{
    $user = getUser("writer");
    $cat = queryTestDB("SELECT * FROM categories WHERE slug='category-1'")->fetch();
    assertDifferent($cat, false);

    $content = loadSite("section=admin:categories&action=update&id=$cat[id]", $user["id"]);

    assertStringContains($content, '<form action="'.buildUrl("admin:categories", "update", $cat["id"]).'"');
    assertStringContains($content, "Edit category with id $cat[id]");
    assertStringContainsRegex($content, "/Title:.+$cat[title]/");
    assertStringContainsRegex($content, "/Slug:.+$cat[slug]/");
}

function test_admin_categories_update_slug_exists()
{
    queryTestDB("INSERT INTO categories(slug, title) VALUES('category-2', 'Category 2')");
    $cat = queryTestDB("SELECT * FROM categories WHERE slug='category-1'")->fetch();
    assertDifferent($cat, false);
    $cat2 = queryTestDB("SELECT * FROM categories WHERE slug='category-2'")->fetch();
    assertDifferent($cat2, false);

    $_POST["title"] = "Category 12";
    $_POST["slug"] = "category-2";
    setTestCSRFToken("categoryupdate");

    $user = getUser("writer");
    $content = loadSite("section=admin:categories&action=update&id=$cat[id]", $user["id"]);

    assertStringContains($content, "The category with id $cat2[id] and title '$cat2[title]' already has the slug 'category-2'.");
}

function test_admin_categories_update_success()
{
    $_POST["title"] = "Category 3";
    $_POST["slug"] = "category-3";
    setTestCSRFToken("categoryupdate");

    $user = getUser("writer");
    $cat = queryTestDB("SELECT * FROM categories WHERE slug='category-1'")->fetch();
    assertDifferent($cat, false);
    loadSite("section=admin:categories&action=update&id=$cat[id]", $user["id"]);

    assertMessageSaved("Category edited with success.");
    assertRedirect(buildUrl("admin:categories", "update", $cat["id"]));
}

// DELETE

function test_admin_categories_delete_not_for_writers()
{
    $user = getUser("writer");
    loadSite("section=admin:categories&action=delete", $user["id"]);
    assertMessageSaved("Must be admin.");
    assertRedirect(buildUrl("admin:categories", "read"));
}

function test_admin_categories_delete_wrong_csrf()
{
    $admin = getUser("admin");
    $cat2 = queryTestDB("SELECT * FROM categories WHERE slug='category-2'")->fetch();
    assertDifferent($cat2, false);
    $token = "aaaa";

    loadSite("section=admin:categories&action=delete&id=$cat2[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Wrong CSRF token for request 'categorydelete'");
    assertRedirect(buildUrl("admin:categories"));
}

function test_admin_categories_delete_unknown_id()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("categorydelete");

    loadSite("section=admin:categories&action=delete&id=987&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Unknown category with id 987");
    assertRedirect(buildUrl("admin:categories"));
}

function test_admin_categories_delete_success()
{
    $admin = getUser("admin");
    $cat2 = queryTestDB("SELECT * FROM categories WHERE slug='category-2'")->fetch();
    assertDifferent($cat2, false);
    $token = setTestCSRFToken("categorydelete");

    loadSite("section=admin:categories&action=delete&id=$cat2[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Category deleted with success");
    assertRedirect(buildUrl("admin:categories"));
    $cat2 = queryTestDB("SELECT * FROM categories WHERE slug='category-2'")->fetch();
    assertIdentical(false, $cat2);
}

// READ

function test_admin_categories_read_writer()
{
    $user = getUser("writer");
    $content = loadSite("section=admin:categories", $user["id"]);

    assertStringContains($content, "List of all categories");
    assertStringContains($content, "Category 3");
    assertStringContains($content, "category-3");
    assertStringContains($content, "Edit</a>");
    assertStringNotContains($content, "Delete</a>");
}

function test_admin_categories_read_admin()
{
    $user = getUser("admin");
    $content = loadSite("section=admin:categories", $user["id"]);

    assertStringContains($content, "List of all categories");
    assertStringContains($content, "Category 3");
    assertStringContains($content, "category-3");
    assertStringContains($content, "Edit</a>");
    assertStringContains($content, "Delete</a>");
}
