<?php
if ($user["role"] === "commenter") {
    redirect($folder);
}

$title = "Categories";
require_once "header.php";
?>

<h1>Categories</h1>

<?php
if ($action === "add" || $action === "edit") {

    $catData = [
        "id" => $resourceId,
        "name" => "",
        "slug" => ""
    ];

    $isEdit = ($action === "edit");

    if (isset($_POST["name"])) {
        $catData["name"] = $_POST["name"];
        $catData["slug"] = $_POST["slug"];

        $dataOK = checkNameFormat($catData["name"]);
        $dataOK = (checkSlugFormat($catData["slug"]) && $dataOK);

        // check slug unikeness
        $strQuery = "SELECT id FROM categories WHERE slug=:slug";
        $params = ["slug" => $catData["slug"]];

        if ($isEdit) {
            $strQuery .= " AND id <> :own_id";
            $params["own_id"] = $catData["id"];
        }

        $cat = queryDB($strQuery, $params)->fetch();
        if (is_array($cat)) {
            addError("The category with id ".$cat["id"]." and name '".$cat["name"]."' already has the slug '".$catData["slug"]."' .");
            $dataOK = false;
        }

        if ($dataOK) {
            $strQuery = "INSERT INTO categories(name, slug) VALUES(:name, :slug)";

            if ($isEdit) {
                $strQuery = "UPDATE categories SET name=:name, slug=:slug WHERE id=:id";
            }
            else {
                unset($catData["id"]);
            }

            $success = queryDB($strQuery, $catData, true);

            if ($success) {
                $redirectionId = null;
                if ($isEdit) {
                    addSuccess("Category edited with success.");
                    $redirectionId = $catData["id"];
                }
                else {
                    addSuccess("Category added with success.");
                    $redirectionId = $db->lastInsertId();
                }

                redirect($folder, "categories", "edit", $redirectionId);
            }
            else {
                $_action = $isEdit ? "editing" : "adding";
                addError("There was an error $_action the page");
            }
        }
    }
    elseif ($isEdit) {
        $cat = queryDB("SELECT * FROM categories WHERE id = ?", $resourceId)->fetch();

        if (is_array($cat)) {
            $catData = $cat;
        }
        else {
            addError("unknown category with id $resourceId");
            redirect($folder, "categories");
        }
    }

    $formTarget = buildLink($folder, "categories", $action, $resourceId);
?>

<?php if ($isEdit): ?>
    <h2>Edit category with id <?php echo $resourceId; ?></h2>
<?php else: ?>
    <h2>Add a new category</h2>
<?php endif; ?>

<?php require_once "../app/messages.php"; ?>

<form action="<?php echo $formTarget; ?>" method="post">

    <label>Name : <input type="text" name="name" required value="<?php echo $catData["name"]; ?>"></label> <br>
    <br>

    <label>Slug : <input type="text" name="slug" required value="<?php echo $catData["slug"]; ?>"></label> <br>
    <br>

    <input type="submit" value="<?php echo $isEdit ? "Edit" : "Add"; ?>">
</form>

<?php
} // end if action = add or edit

// --------------------------------------------------

elseif ($action === "delete") {
    $cat = queryDB('SELECT id, user_id FROM pages WHERE id = ?', $resourceId)->fetch();

    if (is_array($cat)) {
        if (! $isUserAdmin && $cat["user_id"] !== $userId) {
            addError("Must be admin");
        }
        else {
            $success = queryDB('DELETE FROM pages WHERE id = ?', $resourceId, true);

            if ($success) {
                // unparent all pages that are a child of the one deleted
                queryDB('UPDATE pages SET parent_page_id = NULL WHERE parent_page_id = ?', $resourceId);

                queryDB('DELETE FROM comments WHERE parent_page_id = ?', $resourceId);

                addSuccess("page deleted with success");
            }
            else {
                addError("There was an error deleting the page");
            }
        }
    }
    else {
        addError("Unknow page with id $resourceId");
    }

    redirect($folder, "pages");
}

// --------------------------------------------------
// if action == "show" or other actions are fobidden for that page

else {
?>

<h2>List of all categories</h2>

<?php require_once "../app/messages.php"; ?>

<div>
    <a href="<?php echo buildLink($folder, "categories", "add"); ?>">Add a category</a>
</div>

<br>

<table>
    <tr>
        <th>id <?php echo printTableSortButtons("categories", "id"); ?></th>
        <th>name <?php echo printTableSortButtons("categories", "name"); ?></th>
        <th>Slug <?php echo printTableSortButtons("categories", "slug"); ?></th>
        <th>Number of posts <?php echo printTableSortButtons("categories", "post_count"); ?></th>
    </tr>

<?php
    if ($orderByTable !== "categories") {
        $orderByTable = "categories";
    }

    $fields = ["id", "name", "slug", "post_count"];
    if (! in_array($orderByField, $fields)) {
        $orderByField = "id";
    }

    $cats = queryDB(
        "SELECT categories.*
        FROM categories
        ORDER BY $orderByTable.$orderByField $orderDir
        LIMIT ".$adminMaxTableRows * ($pageNumber - 1).", $adminMaxTableRows"
    );

    while ($cat = $cats->fetch()) {
        $cat["post_count"] = queryDB("SELECT COUNT(id) FROM pages WHERE category_id=?", $cat["id"])
        ->fetch()["COUNT(id)"];
?>
    <tr>
        <td><?php echo $cat["id"]; ?></td>
        <td><?php echo $cat["name"]; ?></td>
        <td><?php echo $cat["slug"]; ?></td>
        <td><?php echo $cat["post_count"]; ?></td>

        <td><a href="<?php echo buildLink($folder, "categories", "edit", $cat["id"]); ?>">Edit</a></td>

        <?php if($isUserAdmin): ?>
        <td><a href="<?php echo buildLink($folder, "categories", "delete", $cat["id"]); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>
<?php
    } // end while categories from DB
?>
</table>


<?php
    $table = "categories";
    require_once "pagination.php";
} // end if action = show
