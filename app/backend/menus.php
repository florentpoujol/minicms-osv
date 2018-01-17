<?php

$action = $query['action'];
$userId = $user['id'];
$queryId = $query['id'] === '' ? null : $query['id'];

if ($user["role"] === "commenter") {
    redirect("admin:users", "update", $user["id"]);
    return;
}

if ($action === "update" && $queryId === null) {
    addError("You must select a menu to update.");
    redirect("admin:menus", "read");
    return;
}

if ($action === "delete" && ! $user['isAdmin']) {
    addError("Must be admin.");
    redirect("admin:menus", "read");
    return;
}

$title = "Menus";
require_once __dir__ . "/header.php";
?>

<h1>Menus</h1>

<?php
if ($action === "create" || $action === "update") {
    $menuData = [
        "id" => $queryId,
        "name" => "",
        "in_use" => 0,
        "structure" => [],
    ];

    $isUpdate = ($action === "update");

    if (isset($_POST["name"])) {
        $menuData["name"] = $_POST["name"];
        $menuData["structure"] = $_POST["structure"];
        $menuData["in_use"] = (int)isset($_POST["in_use"]);

        if (verifyCSRFToken($_POST["csrf_token"], "menu$action")) {
            $dataOK = checkNameFormat($menuData["name"]);

            $strQuery = "SELECT id, name FROM menus WHERE name = ?";
            $params = [$menuData["name"]];

            if ($isUpdate) {
                $strQuery .= " AND id <> ?";
                $params[] = $queryId;
            }

            $menu = queryDB($strQuery, $params)->fetch();
            if (is_array($menu)) {
                addError("The menu with id $menu[id] already has the name '$menu[name]'.");
                $dataOK = false;
            }

            if ($dataOK) {
                $strQuery = "";
                $params = $menuData;

                if ($action === "create") {
                    $strQuery = "INSERT INTO menus(name, in_use, structure) VALUES(:name, :in_use, :structure)";
                    unset($params["id"]);
                } else {
                    $strQuery = "UPDATE menus SET name = :name, structure = :structure, in_use = :in_use WHERE id = :id";
                }

                unset($params["structure_json"]);

                // remove item where name and target are empty
                cleanMenuStructure($params["structure"]); // passed by reference
                $params["structure"] = json_encode($params["structure"], JSON_PRETTY_PRINT);

                $success = queryDB($strQuery, $params, true);

                if ($success) {
                    addSuccess("Menu added or edited successfully.");

                    $redirectId = $queryId;
                    if ($action === "create") {
                        $redirectId = $db->lastInsertId();
                    }
                    if ($params["in_use"] === 1) {
                        queryDB("UPDATE menus SET in_use = 0 WHERE id <> ?", $redirectId);
                    }

                    redirect("admin:menus", "update", $redirectId);
                    return;
                } else {
                    addError("There was an error adding or editing the menu");
                }
            }
        }
    }
    // no post data
    elseif ($isUpdate) {
        $dbMenu = queryDB("SELECT *, structure as structure_json FROM menus WHERE id = ?", $queryId)->fetch();

        if ($dbMenu === false) {
            addError("Unknown menu with id $queryId.");
            redirect("admin:menus", "read");
            return;
        }

        $menuData = $dbMenu;
        $menuData["structure"] = json_decode($menuData["structure_json"], true);
    }

    $formTarget = buildUrl("admin:menus", $action, $queryId);
?>

<?php if ($isUpdate): ?>
    <h2>Edit menu with id <?= $menuData["id"]; ?></h2>
<?php else: ?>
    <h2>Add a new menu</h2>
<?php endif; ?>

<?php require_once __dir__ . "/../messages.php"; ?>

<form action="<?= $formTarget; ?>" method="post">
    <label>Name: <input type="text" name="name" required value="<?php safeEcho($menuData["name"]); ?>"></label> <br>
    <br>

    <label>Use this menu: <input type="checkbox" name="in_use" <?= ($menuData["in_use"] === 1) ? "checked" : null; ?>></label> <br>
    <br>

    <ul>
        <li>Description of the types:
            <ul>
                <li>page, post, category: Link to the specified page, post or category which id or slug must be set in the target field. The item's title can be overriden when setting the name field</li>
                <li>blog: Link to the list of the last posts. The name of the link is by default "blog", can be overrriden by setting the name field.</li>
                <li>external: an arbitrary link to any URL. Both name and target fields must be set.</li>
                <li>folder: an item that does not link to anything, which purpose is to have children. Just set the name field</li>
                <li>homepage: define a particular page as the homepage of the site instead of the list of posts. Otherwise same as the "page" type.</li>
            </ul>
        </li>
        <li>To delete an entry (and all its children), just put nothing in both the "name" and "target" fields</li>
    </ul>

<?php

function buildMenuStructure($items, $name = "")
{
?>
    <ul class="menu">
<?php
    $maxId = -1;
    foreach ($items as $id => $item) {
        $itemName = $name."[$id]";
        $maxId++;
?>
        <li>
            <select name="<?= $itemName; ?>[type]">
                <option value="page" <?= ($item["type"] === "page" ? "selected": null); ?>>Page</option>
                <option value="post" <?= ($item["type"] === "post" ? "selected": null); ?>>Post</option>
                <option value="category" <?= ($item["type"] === "category" ? "selected": null); ?>>Category</option>
                <option value="folder" <?= ($item["type"] === "folder" ? "selected": null); ?>>Folder</option>
                <option value="external" <?= ($item["type"] === "external" ? "selected": null); ?>>External</option>
                <option value="homepage" <?= ($item["type"] === "homepage" ? "selected": null); ?>>Home page</option>
            </select>

            <input type="text" name="<?= $itemName; ?>[name]" value="<?php safeEcho($item["name"]); ?>" placeholder="name">

            <input type="text" name="<?= $itemName; ?>[target]" value="<?php safeEcho($item["target"]); ?>" placeholder="target">

<?php
        if (! isset($item["children"])) {
            $item["children"] = [];
        }

        buildMenuStructure($item["children"], $itemName."[children]");
?>
        </li>
<?php
    }

    $maxId++;
    $itemName = $name."[$maxId]";
?>
        <li>
            <select name="<?= $itemName; ?>[type]">
                <option value="page">Page</option>
                <option value="post">Post</option>
                <option value="category">Category</option>
                <option value="folder">Folder</option>
                <option value="external">External</option>
                <option value="home">Home</option>
            </select>
            <input type="text" name="<?= $itemName; ?>[name]" placeholder="name">
            <input type="text" name="<?= $itemName; ?>[target]" placeholder="target">
        </li>
    </ul>
<?php
}

buildMenuStructure($menuData["structure"], "structure");
?>

    <?php addCSRFFormField("menu$action"); ?>

    <input type="submit" value="Edit menu">
</form>

<?php
}

// --------------------------------------------------

elseif ($action === "delete") {
    if (verifyCSRFToken($query['csrftoken'], "menudelete")) {
        $menu = queryDB("SELECT id FROM menus WHERE id = ?", $queryId)->fetch();

        if (is_array($menu)) {
            $success = queryDB("DELETE FROM menus WHERE id = ?", $queryId, true);

            if ($success) {
                addSuccess("Menu deleted successfully.");

                // if the deleted menu was in use, try to select the first one that still exists
                $menuInUse = queryDB('SELECT id FROM menus WHERE in_use = 1')->fetch();
                if ($menuInUse === false) {
                    $menu = queryDB('SELECT id FROM menus')->fetch();
                    if (is_array($menu)) {
                        queryDB('UPDATE menus SET in_use = 1 WHERE id = ?', $menu['id']);
                    }
                }
            } else {
                addError("Error deleting menu");
            }
        } else {
            addError("Unknown menu with id $queryId.");
        }
    }

    redirect("admin:menus", "read");
    return;
}

// --------------------------------------------------
// if action === "show" or other actions are fobidden for that user

else {
?>

<h2>List of all menus</h2>

<?php require_once __dir__ . "/../messages.php"; ?>

<div>
    <a href="<?= buildUrl("admin:menus", "create"); ?>">Add a menu</a>
</div>

<br>

<table>
    <tr>
        <th>id <?= getTableSortButtons("menus", "id"); ?></th>
        <th>name <?= getTableSortButtons("menus", "name"); ?></th>
        <th>In use <?= getTableSortButtons("menus", "in_use"); ?></th>
        <th>Structure</th>
    </tr>

<?php
    $query['orderbytable'] = "menus";

    $fields = ["id", "name", "in_use"];
    if (! in_array($query['orderbyfield'], $fields)) {
        $query['orderbyfield'] = "id";
    }

    $menus = queryDB(
        "SELECT * FROM menus
        ORDER BY $query[orderbytable].$query[orderbyfield] $query[orderdir]
        LIMIT " . $adminMaxTableRows * ($query['page'] - 1) . ", $adminMaxTableRows"
    );

    if ($user['isAdmin']) {
        $deleteToken = setCSRFToken("menudelete");
    }

    while($menu = $menus->fetch()):
?>

        <tr>
            <td><?= $menu["id"]; ?></td>
            <td><?php safeEcho($menu["name"]); ?></td>
            <td><?= $menu["in_use"]; ?></td>
            <td><?= "structure"; ?></td>

            <td><a href="<?= buildUrl("admin:menus", "update", $menu["id"]); ?>">Edit</a></td>

            <?php if($user['isAdmin']): ?>
            <td><a href="<?= buildUrl("admin:menus", "delete", $menu["id"], $deleteToken); ?>">Delete</a></td>
            <?php endif; ?>
        </tr>

    <?php endwhile; ?>

</table>

<?php
    $table = "menus";
    require_once __dir__ . "/pagination.php";
} // end if action = show
