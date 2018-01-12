<?php

$action = $query["action"];
$queryId = $query["id"] === "" ? null : $query["id"];

if ($user["role"] === "commenter") {
    setHTTPResponseCode(403);
    redirect("admin:users", "update", $user["id"]);
    return;
}

if ($action === "update" && $queryId === null) {
    addError("You must select a category to update.");
    redirect("admin:categories", "read");
    return;
}

if ($action === "delete" && ! $user['isAdmin']) {
    addError("Must be admin.");
    setHTTPResponseCode(403);
    redirect("admin:categories", "read");
    return;
}

$title = "Categories";
require_once __dir__ . "/header.php";
?>

<h1>Categories</h1>

<?php

if ($action === "create" || $action === "update") {
    $catData = [
        "id" => $queryId,
        "title" => "",
        "slug" => ""
    ];

    $isUpdate = ($action === "update");

    if (isset($_POST["title"])) {
        $catData["title"] = $_POST["title"];
        $catData["slug"] = $_POST["slug"];

        if (verifyCSRFToken($_POST["csrf_token"], "category$action")) {
            $dataOK = checkPageTitleFormat($catData["title"]);
            $dataOK = checkSlugFormat($catData["slug"]) && $dataOK;

            // check slug uniqueness
            $strQuery = "SELECT * FROM categories WHERE slug = :slug";
            $params = ["slug" => $catData["slug"]];

            if ($isUpdate) {
                $strQuery .= " AND id <> :own_id";
                $params["own_id"] = $catData["id"];
            }

            $cat = queryDB($strQuery, $params)->fetch();
            if (is_array($cat)) {
                addError("The category with id $cat[id] and title '$cat[title]' already has the slug '$catData[slug]'.");
                $dataOK = false;
            }

            if ($dataOK) {
                $strQuery = "INSERT INTO categories(title, slug) VALUES(:title, :slug)";

                if ($isUpdate) {
                    $strQuery = "UPDATE categories SET title = :title, slug = :slug WHERE id = :id";
                } else {
                    unset($catData["id"]);
                }

                $success = queryDB($strQuery, $catData, true);

                if ($success) {
                    $redirectionId = null;
                    if ($isUpdate) {
                        addSuccess("Category edited with success.");
                        $redirectionId = $catData["id"];
                    } else {
                        addSuccess("Category added with success.");
                        $redirectionId = $db->lastInsertId();
                    }

                    redirect("admin:categories", "update", $redirectionId);
                    return;
                } else {
                    $_action = $isUpdate ? "editing" : "adding";
                    addError("There was an error $_action the category.");
                }
            }
        }
    }
    // no POST data
    elseif ($isUpdate) {
        $cat = queryDB("SELECT * FROM categories WHERE id = ?", $queryId)->fetch();

        if (is_array($cat)) {
            $catData = $cat;
        } else {
            addError("Unknown category with id $queryId");
            redirect("admin:categories", "read");
            return;
        }
    }

    $formTarget = buildUrl("admin:categories", $action, $queryId);
?>

<?php if ($isUpdate): ?>
    <h2>Edit category with id <?= $queryId; ?></h2>
<?php else: ?>
    <h2>Add a new category</h2>
<?php endif; ?>

<?php require_once __dir__ . "/../messages.php"; ?>

<form action="<?= $formTarget; ?>" method="post">

    <label>Title: <input type="text" name="title" required value="<?php safeEcho($catData["title"]); ?>"></label> <br>
    <br>

    <label>Slug: <input type="text" name="slug" required value="<?php safeEcho($catData["slug"]); ?>"></label> <br>
    <br>

    <?php addCSRFFormField("category$action"); ?>

    <input type="submit" value="<?= $isUpdate ? "Edit" : "Add"; ?>">
</form>

<?php
} // end if action = add or edit

// --------------------------------------------------

elseif ($action === "delete") {
    if (verifyCSRFToken($query['csrftoken'], "categorydelete")) {
        $cat = queryDB('SELECT id FROM categories WHERE id = ?', $queryId)->fetch();

        if (is_array($cat)) {
            $success = queryDB('DELETE FROM categories WHERE id = ?', $queryId, true);

            if ($success) {
                // let posts have a non existent categories
                addSuccess("Category deleted with success");
            } else {
                addError("There was an error deleting the category");
            }
        } else {
            addError("Unknown category with id $queryId");
        }
    }

    redirect("admin:categories");
    return;
}

// --------------------------------------------------
// if action == "show" or other actions are fobidden for that page

else {
?>

<h2>List of all categories</h2>

<?php require_once __dir__ . "/../messages.php"; ?>

<div>
    <a href="<?= buildUrl("admin:categories", "create"); ?>">Add a category</a>
</div>

<br>

<table>
    <tr>
        <th>id <?= getTableSortButtons("categories", "id"); ?></th>
        <th>title <?= getTableSortButtons("categories", "title"); ?></th>
        <th>Slug <?= getTableSortButtons("categories", "slug"); ?></th>
        <th>Number of posts <?= getTableSortButtons("categories", "post_count"); ?></th>
    </tr>

<?php
    $query['orderbytable'] = "categories";

    $fields = ["id", "title", "slug", "post_count"];
    if (! in_array($query['orderbyfield'], $fields)) {
        $query['orderbyfield'] = "id";
    }

    $cats = queryDB(
        "SELECT categories.*
        FROM categories
        ORDER BY $query[orderbytable].$query[orderbyfield] $query[orderdir]
        LIMIT " . $adminMaxTableRows * ($query['page'] - 1) . ", $adminMaxTableRows"
    );

    if ($user['isAdmin']) {
        $deleteToken = setCSRFToken("categorydelete");
    }

    while ($cat = $cats->fetch()):
        $postsCount = queryDB("SELECT COUNT(id) FROM pages WHERE category_id = ?", $cat["id"])
            ->fetch()["COUNT(id)"];
?>
    <tr>
        <td><?= $cat["id"]; ?></td>
        <td><?php safeEcho($cat["title"]); ?></td>
        <td><?php safeEcho($cat["slug"]); ?></td>
        <td><?= $postsCount; ?></td>

        <td><a href="<?= buildUrl("admin:categories", "update", $cat["id"]); ?>">Edit</a></td>

        <?php if($user['isAdmin']): ?>
            <td><a href="<?= buildUrl("admin:categories", "delete", $cat["id"], $deleteToken); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>
<?php
    endwhile;
?>
</table>

<?php
    $table = "categories";
    require_once __dir__ . "/pagination.php";
} // end if action = show
