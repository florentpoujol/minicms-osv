<?php
$title = "Menus";
require_once "header.php";
?>

<h1>Menus</h1>

<?php
if ($action === "add" || $action === "edit") {
    $menuData = [
        "id" => $resourceId,
        "name" => "",
        "in_use" => 0,
        "structure" => ""
    ];

    $isEdit = ($action === "edit");

    if (isset($_POST["name"])) {

        $menuData["name"] = $_POST["name"];
        $menuData["structure"] = $_POST["structure"];
        $menuData["in_use"] = (int)isset($_POST["in_use"]);

        $dataOK = checkNameFormat($menuData["name"]);

        $strQuery = "SELECT id FROM menus WHERE name=?";
        $params = [$menuData["name"]];

        if ($isEdit) {
            $strQuery .= " AND id <> ?";
            $params[] = $resourceId;
        }

        $menu = queryDB($strQuery, $params)->fetch();
        if (is_array($menu)) {
            addError("There already is a menu with the same name.");
            $dataOK = false;
        }

        if ($dataOK) {
            $strQuery = "";
            $params = $menuData;

            if ($action === "add") {
                $strQuery = "INSERT INTO menus(name, in_use, structure) VALUES(:name, :in_use, :structure)";
                unset($params["id"]);
            }
            else {
                $strQuery = "UPDATE menus SET name=:name, structure=:structure, in_use=:in_use WHERE id=:id";
            }

            unset($params["structure_json"]);

            // remove item where name and target are empty
            function cleanStructure(&$array) {
                // do not make local copies !
                for ($i = count($array)-1; $i >= 0; $i--) {
                    if (isset($array[$i]["children"])) {
                        cleanStructure($array[$i]["children"]);
                    }

                    if (trim($array[$i]["name"]) === "" && trim($array[$i]["target"]) === "") {
                        unset($array[$i]);
                    }
                }
            }
            cleanStructure($params["structure"]);
            $params["structure"] = json_encode($params["structure"], JSON_PRETTY_PRINT);

            $success = queryDB($strQuery, $params, true);

            if ($success) {
                addSuccess("Menu added or edited successfully.");

                if ($params["in_use"] === 1) {
                    queryDB("UPDATE menus SET in_use = 0 WHERE name <> ?", $params["name"]);
                }

                $id = $resourceId;
                if (!$isEdit) {
                    $id = $db->lastInsertId();
                }
                redirect($folder, "menus", "edit", $resourceId);
            }
            else {
                addError("There was an error adding or editing the menu");
            }
        }
    }
    elseif ($isEdit) {
        $menuFromDB = queryDB("SELECT *, structure as structure_json FROM menus WHERE id=?", $resourceId)->fetch();

        if ($menuFromDB === false) {
            addError("Unknow menu");
            redirect($folder, "menus");
        }

        $menuData = $menuFromDB;
        $menuData["structure"] = json_decode($menuData["structure_json"], true);
    }

    $formTarget = buildLink($folder, $pageName, $action, $resourceId);
?>

<?php if ($isEdit): ?>
    <h2>Edit menu with id <?php echo $menuData["id"]; ?></h2>
<?php else: ?>
    <h2>Add a new menu</h2>
<?php endif; ?>

<?php require_once "../app/messages.php"; ?>

<form action="<?php echo $formTarget; ?>" method="post">
    <label>Name : <input type="text" name="name" required value="<?php echo $menuData["name"]; ?>"></label> <br>
    <br>

    <label>Use this menu : <input type="checkbox" name="in_use" <?php echo ($menuData["in_use"] === 1) ? "checked" : null; ?>></label> <br>
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
            <select name="<?php echo $itemName; ?>[type]">
                <option value="page" <?php echo ($item["type"] === "page" ? "selected": null); ?>>Page</option>
                <option value="post" <?php echo ($item["type"] === "post" ? "selected": null); ?>>Post</option>
                <option value="category" <?php echo ($item["type"] === "category" ? "selected": null); ?>>Category</option>
                <option value="folder" <?php echo ($item["type"] === "folder" ? "selected": null); ?>>Folder</option>
                <option value="external" <?php echo ($item["type"] === "external" ? "selected": null); ?>>External</option>
                <option value="homepage" <?php echo ($item["type"] === "homepage" ? "selected": null); ?>>Home page</option>
            </select>

            <input type="text" name="<?php echo $itemName; ?>[name]" value="<?php echo $item["name"]; ?>" placeholder="name">

            <input type="text" name="<?php echo $itemName; ?>[target]" value="<?php echo $item["target"]; ?>" placeholder="target">

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
            <select name="<?php echo $itemName; ?>[type]">
                <option value="page">Page</option>
                <option value="post">Post</option>
                <option value="category">Category</option>
                <option value="folder">Folder</option>
                <option value="external">External</option>
                <option value="home">Home</option>
            </select>
            <input type="text" name="<?php echo $itemName; ?>[name]" placeholder="name">
            <input type="text" name="<?php echo $itemName; ?>[target]" placeholder="target">
        </li>
    </ul>
<?php

}

buildMenuStructure($menuData["structure"], "structure");
?>


    <input type="submit" value="Edit menu">
</form>

<?php
}

// --------------------------------------------------

elseif ($action === "delete") {
    if ($user["role"] !== "menuer") {
        $menu = queryDB(
            "SELECT pages.user_id as page_user_id
            FROM menus
            LEFT JOIN pages on pages.id=menus.page_id
            WHERE menus.id=?",
            $resourceId
        )->fetch();

        if (! $isUserAdmin && $menu["page_user_id"] !== $userId) {
            addError("Can only delete your own menu or the ones posted on the pages you created");
        }
        else {
            $success = queryDB('DELETE FROM menus WHERE id=?', $resourceId, true);

            if ($success) {
                addSuccess("Comment deleted");
            }
            else {
                addError("Error deleting menu");
            }
        }
    }

    redirect(["p" => "menus"]);
}

// --------------------------------------------------
// if action === "show" or other actions are fobidden for that user

else {
?>

<h2>List of all menus</h2>

<?php require_once "../app/messages.php"; ?>

<div>
    <a href="<?php echo buildLink($folder, $pageName, "add"); ?>">Add a menu</a>
</div>

<br>

<table>
    <tr>
        <th>id <?php echo printTableSortButtons("menus", "id"); ?></th>
        <th>name <?php echo printTableSortButtons("menus", "name"); ?></th>
        <th>In use <?php echo printTableSortButtons("menus", "in_use"); ?></th>
        <th>Structure</th>
    </tr>

<?php
    $tables = ["menus"];
    if (! in_array($orderByTable, $tables)) {
        $orderByTable = "menus";
    }

    $fields = ["id", "name", "in_use"];
    if (! in_array($orderByField, $fields)) {
        $orderByField = "id";
    }

    $menus = queryDB(
        "SELECT * FROM menus
        ORDER BY $orderByTable.$orderByField $orderDir
        LIMIT ".$adminMaxTableRows * ($pageNumber - 1).", $adminMaxTableRows"
    );

    while($menu = $menus->fetch()) {
?>

    <tr>
        <td><?php echo $menu["id"]; ?></td>
        <td><?php echo $menu["name"]; ?></td>
        <td><?php echo $menu["in_use"]; ?></td>
        <td><?php echo "structure"; ?></td>

        <td><a href="<?php echo buildLink($folder, "menus", "edit", $menu["id"]); ?>">Edit</a></td>

        <?php if($isUserAdmin): ?>
        <td><a href="<?php echo buildLink($folder, "menus", "delete", $menu["id"]); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>

<?php
    }
?>

</table>

<?php
    $table = "menus";
    require_once "pagination.php";
} // end if action = show
