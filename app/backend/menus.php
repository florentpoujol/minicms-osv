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


        $data = [
            [
                "type" => "page",
                "target" => "5"
            ],
            [
                "type" => "folder",
                "name" => "the folder"
            ],
            [
                "type" => "external",
                "name" => "The link",
                "target" => "http://the.link"
            ]
        ];

        // $menuData["structure_json"] = $data;

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

            // check structure
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

    <label>Structure :
        <textarea name="structure_json" cols="50" rows="15"><?php echo $menuData["structure_json"] ?></textarea>
    </label> <br>
    <br>


<?php

function processItems($items, $name = "") {
?>
    <ul>
<?php
    $maxId = 0;
    foreach ($items as $id => $item) {
        $localName = $name."[$id]";
        $maxId = $id;
?>
        <li>
            <select name="<?php echo $localName; ?>[type]">
                <option value="page" <?php echo ($item["type"] === "page" ? "selected": null); ?>>Pages</option>
                <option value="folder" <?php echo ($item["type"] === "folder" ? "selected": null); ?>>Folder</option>
                <option value="external" <?php echo ($item["type"] === "external" ? "selected": null); ?>>External</option>
            </select>

            <input type="text" name="<?php echo $localName; ?>[name]" value="<?php echo $item["name"]; ?>" placeholder="name">

            <input type="text" name="<?php echo $localName; ?>[target]" value="<?php echo $item["target"]; ?>" placeholder="target">

<?php
        if (! isset($item["children"])) {
            $item["children"] = [];
        }

        // var_dump( $item["children"]);
        processItems($item["children"], $localName."[children]");
?>

        </li>
<?php
    }
    if ($maxId !== 0)
        $maxId++;

    $localName = $name."[$maxId]";
?>
        <li>
            <select name="<?php echo $localName; ?>[type]">
                <option value="page">Page or post</option>
                <option value="folder">Folder</option>
                <option value="external">External link</option>
            </select>
            <input type="text" name="<?php echo $localName; ?>[name]" placeholder="name">
            <input type="text" name="<?php echo $localName; ?>[target]" placeholder="target">
        </li>
    </ul>
<?php

}

processItems($menuData["structure"], "structure");
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
