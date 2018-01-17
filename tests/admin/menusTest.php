<?php

function test_admin_menus_not_for_commenters()
{
    $user = getUser("commenter");
    loadSite("section=admin:menus", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
}

// CREATE

function test_admin_menus_create_wrong_csrf()
{
    $_POST["name"] = "Menumenu 1";
    $_POST["structure"] = [];
    $_POST["in_use"] = 1;
    setTestCSRFToken("wrongtoken");

    $user = getUser("admin");
    $content = loadSite("section=admin:menus&action=create", $user["id"]);
    assertStringContains($content, "Add a new menu");
    assertStringContains($content, "Wrong CSRF token for request 'menucreate'");
}

function test_admin_menus_create_wrong_name()
{
    $_POST["name"] = "Menu 1";
    $_POST["structure"] = [];
    $_POST["in_use"] = 1;
    setTestCSRFToken("menucreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:menus&action=create", $user["id"]);
    assertStringContains($content, "The name has the wrong format.");
}

function test_admin_menus_create_success()
{
    $_POST["name"] = "Menu1";
    $_POST["structure"] = [];
    $_POST["in_use"] = 1;
    setTestCSRFToken("menucreate");

    $user = getUser("admin");
    loadSite("section=admin:menus&action=create", $user["id"]);

    assertMessageSaved("Menu added or edited successfully.");

    $menu = queryTestDB("SELECT * FROM menus WHERE name='Menu1'")->fetch();
    assertRedirect(buildUrl("admin:menus", "update", $menu["id"]));
    assertIdentical("Menu1", $menu["name"]);
    assertIdentical(1, $menu["in_use"]);
}

function test_admin_menus_create_already_exists()
{
    $_POST["name"] = "Menu1";
    $_POST["structure"] = [];
    $_POST["in_use"] = 1;
    setTestCSRFToken("menucreate");

    $user = getUser("admin");
    $content = loadSite("section=admin:menus&action=create", $user["id"]);
    assertStringContains($content, "The menu with id 1 already has the name 'Menu1'.");
}

// UPDATE
// note: no tests on the structure is done
function test_admin_menus_update_no_id()
{
    $user = getUser("writer");
    loadSite("section=admin:menus&action=update", $user["id"]);
    assertMessageSaved("You must select a menu to update.");
    assertRedirect(buildUrl("admin:menus", "read"));
}

function test_admin_menus_update_unknow_id()
{
    $user = getUser("writer");
    loadSite("section=admin:menus&action=update&id=987", $user["id"]);
    assertMessageSaved("Unknown menu with id 987.");
    assertRedirect(buildUrl("admin:menus", "read"));
}

function test_admin_menus_update_read()
{
    $user = getUser("writer");
    $menu = queryTestDB("SELECT * FROM menus WHERE name='Menu1'")->fetch();

    $content = loadSite("section=admin:menus&action=update&id=$menu[id]", $user["id"]);

    assertStringContains($content, '<form action="'.buildUrl("admin:menus", "update", $menu["id"]).'"');
    assertStringContains($content, "Edit menu with id $menu[id]");
    assertStringContainsRegex($content, "/Name:.+$menu[name]/");
    $checked = "";
    if ($menu["in_use"] === 1) {
        $checked = "checked";
    }
    assertStringContainsRegex($content, '/Use this menu:.+name="in_use" '.$checked.'>/');
}

function test_admin_menus_update_name_exists()
{
    queryTestDB("INSERT INTO menus(name, structure, in_use) VALUES('Menu2', '[]', 0)");
    $menu = queryTestDB("SELECT * FROM menus WHERE name='Menu1'")->fetch();
    assertDifferent($menu, false);
    $menu2 = queryTestDB("SELECT * FROM menus WHERE name='Menu2'")->fetch();
    assertDifferent($menu2, false);

    $_POST["name"] = "Menu2";
    $_POST["structure"] = [];
    $_POST["in_use"] = 1;
    setTestCSRFToken("menuupdate");

    $user = getUser("writer");
    $content = loadSite("section=admin:menus&action=update&id=$menu[id]", $user["id"]);

    assertStringContains($content, "The menu with id $menu2[id] already has the name 'Menu2'.");
}

function test_admin_menus_update_success()
{
    $_POST["name"] = "Menu3";
    $_POST["structure"] = [];
    unset($_POST["in_use"]);
    setTestCSRFToken("menuupdate");

    $user = getUser("writer");
    $menu = queryTestDB("SELECT * FROM menus WHERE name='Menu1'")->fetch();
    assertDifferent($menu, false);
    loadSite("section=admin:menus&action=update&id=$menu[id]", $user["id"]);

    assertMessageSaved("Menu added or edited successfully.");
    assertRedirect(buildUrl("admin:menus", "update", $menu["id"]));
    $menu = queryTestDB("SELECT * FROM menus WHERE id = $menu[id]")->fetch();
    assertDifferent($menu, false);
    assertIdentical("Menu3", $menu["name"]);
    assertIdentical(0, $menu["in_use"]);

    queryTestDB("UPDATE menus SET name = 'Menu1', in_use = 1 WHERE id = $menu[id]");
}

// DELETE

function test_admin_menus_delete_not_for_writers()
{
    $user = getUser("writer");
    loadSite("section=admin:menus&action=delete", $user["id"]);
    assertMessageSaved("Must be admin.");
    assertRedirect(buildUrl("admin:menus", "read"));
}

function test_admin_menus_delete_wrong_csrf()
{
    $admin = getUser("admin");
    $menu2 = queryTestDB("SELECT * FROM menus WHERE name='Menu2'")->fetch();
    assertDifferent($menu2, false);
    $token = setTestCSRFToken("wrongtoken");

    loadSite("section=admin:menus&action=delete&id=$menu2[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Wrong CSRF token for request 'menudelete'");
    assertRedirect(buildUrl("admin:menus", "read"));
}

function test_admin_menus_delete_unknown_id()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("menudelete");

    loadSite("section=admin:menus&action=delete&id=987&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Unknown menu with id 987.");
    assertRedirect(buildUrl("admin:menus", "read"));
}

function test_admin_menus_delete_success()
{
    $admin = getUser("admin");
    $menu2 = queryTestDB("SELECT * FROM menus WHERE name='Menu2'")->fetch();
    assertDifferent($menu2, false);
    $token = setTestCSRFToken("menudelete");

    loadSite("section=admin:menus&action=delete&id=$menu2[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Menu deleted successfully.");
    assertRedirect(buildUrl("admin:menus", "read"));
    $menu2 = queryTestDB("SELECT * FROM menus WHERE name='Menu2'")->fetch();
    assertIdentical(false, $menu2);
}

// READ

function test_admin_menus_read_writer()
{
    $user = getUser("writer");
    $content = loadSite("section=admin:menus", $user["id"]);

    assertStringContains($content, "List of all menus");
    assertStringContains($content, "Menu1");
    assertStringContains($content, "<td>1</td>");
    assertStringContains($content, "Edit</a>");
    assertStringNotContains($content, "Delete</a>");
}

function test_admin_menus_read_admin()
{
    $user = getUser("admin");
    $content = loadSite("section=admin:menus", $user["id"]);

    assertStringContains($content, "List of all menus");
    assertStringContains($content, "Menu1");
    assertStringContains($content, "<td>1</td>");
    assertStringContains($content, "Edit</a>");
    assertStringContains($content, "Delete</a>");
}
