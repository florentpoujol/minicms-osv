<?php

if ($user["role"] === "commenter") {
    setHTTPResponseCode(403);
    redirect("admin:users", "update", $user["id"]);
    return;
}

$section = $query['section'];
$action = $query['action'];
$queryId = $query['id'] === '' ? null : $query['id'];

$userId = $user['id'];
$isUserAdmin = $user['isAdmin'];

// this page is used for the pages as well as for posts

$terms = [
    "plural" => "pages",
    "singular" => "page",
    "ucplural" => "Pages",
    "ucsingular" => "Page",
];

if ($section === "posts") {
    $terms = [
        "plural" => "posts",
        "singular" => "post",
        "ucplural" => "Posts",
        "ucsingular" => "Post",
    ];
}

if ($action === "update" && $queryId === null) {
    addError("You must select a $terms[singular] to update.");
    redirect("admin:$section", "read");
    return;
}

$title = $terms["ucplural"];
require_once __dir__ . "/header.php";
?>

<h1><?= $terms["ucplural"] ?></h1>

<?php
if ($action === "create" || $action === "update") {

    $pageData = [
        "id" => $queryId,
        "title" => "",
        "slug" => "",
        "content" => "",
        "parent_page_id" => 0,
        "category_id" => 0,
        "published" => 0,
        "user_id" => 0,
        "allow_comments" => (int)$config["allow_comments"],
    ];

    $isUpdate = ($action === "update");
    if ($isUpdate) {
        $strQuery = "SELECT pages.*, users.name as user_name 
        FROM pages
        JOIN users ON pages.user_id = users.id 
        WHERE pages.id = ?";

        if ($section === "pages") {
            $strQuery .= " AND category_id IS NULL";
        } else {
            $strQuery .= " AND category_id IS NOT NULL";
        }

        $page = queryDB($strQuery, $queryId)->fetch();

        if (is_array($page)) {
            $pageData = $page;
        } else {
            addError("Unknown $terms[singular] with id $queryId.");
            redirect("admin:$section", "read");
            return;
        }
    }

    if (isset($_POST["title"])) {
        // fill $pageData with content from the form
        foreach($pageData as $key => $value) {
            if (isset($_POST[$key])) {
                if (is_int($value)) {
                    $pageData[$key] = (int)$_POST[$key];
                    if ($_POST[$key] === "on") {
                        $pageData[$key] = 1;
                    }
                } else {
                    $pageData[$key] = $_POST[$key];
                }
            }
        }
        if (!isset($_POST["allow_comments"])) {
            $pageData["allow_comments"] = 0;
        }

        if (verifyCSRFToken($_POST["csrf_token"], "$section$action")) {
            $dataOK = checkPageTitleFormat($pageData["title"]);
            $dataOK = checkSlugFormat($pageData["slug"]) && $dataOK;

            // check that the slug doesn't already exists in other pages
            $strQuery = "SELECT id, title FROM pages WHERE slug = :slug";
            $params = ["slug" => $pageData["slug"]];

            if ($section === "pages") {
                $strQuery .= " AND category_id IS NULL";
            } else {
                $strQuery .= " AND category_id IS NOT NULL";
            }

            if ($isUpdate) {
                $strQuery .= " AND id <> :own_id";
                $params["own_id"] = $pageData["id"];
            }

            $page = queryDB($strQuery, $params)->fetch();
            if (is_array($page)) {
                addError("The $terms[singular] with id $page[id] and title '$page[title]' already has the slug '$pageData[slug]'.");
                $dataOK = false;
            }

            if ($section === "pages" && $pageData["parent_page_id"] !== 0) {
                // check the id of the parent page, that it's indeed a parent page (a page that isn't a child of another page)

                if ($pageData["parent_page_id"] === $pageData["id"]) {
                    addError("The page can not be parented to itself.");
                } else {
                    $parentPage = queryDB("SELECT id, parent_page_id FROM pages WHERE id = ?",
                        $pageData["parent_page_id"])->fetch();

                    if ($parentPage === false) {
                        addError("The parent page with id '$pageData[parent_page_id]' does not exists.");
                        $pageData["parent_page_id"] = 0;
                        $dataOK = false;
                    } elseif ($parentPage["parent_page_id"] !== null) {
                        addError("The selected parent page (with id '$parentPage[id]') is actually a children of another page (with id '$parentPage[parent_page_id]'), so it can't be a parent page itself.");
                        $pageData["parent_page_id"] = 0;
                        $dataOK = false;
                    }
                }
            }

            // check that the category exists
            if ($section === "posts") {
                $category = queryDB("SELECT id FROM categories WHERE id = ?", $pageData["category_id"])->fetch();

                if ($category === false) {
                    addError("The category with id $pageData[category_id] does not exists.");
                    // $pageData["category_id"] = null;
                    $dataOK = false;
                }
            }

            // check that user actually exists and is not a commenter
            if ($action === "create" && !$isUserAdmin) {
                $pageData["user_id"] = $userId;
            } else {
                $_user = queryDB("SELECT id FROM users WHERE id = ? AND role <> 'commenter'", $pageData["user_id"])->fetch();

                if ($_user === false) {
                    addError("User with id $pageData[user_id] doesn't exists or is not a writer or admin.");

                    $ownerId = $userId;
                    if ($isUpdate) {
                        // get the current owner of the page
                        $ownerId = queryDB("SELECT user_id FROM pages WHERE id = ?", $queryId)->fetch()["user_id"];
                    }
                    $pageData["user_id"] = $ownerId;
                    $dataOK = false;
                }
            }

            // no check on format of numerical fields since they are already converted to int. If the posted value wasn't numerical, it is now 0
            // no check on content

            if ($dataOK) {
                $strQuery = "";

                if ($isUpdate) {
                    $strQuery = "UPDATE pages SET title = :title, slug = :slug, content = :content, published = :published, allow_comments = :allow_comments";

                    if ($section === "pages") {
                        $strQuery .= ", parent_page_id = :parent_page_id";
                    } else {
                        $strQuery .= ", category_id = :category_id";
                    }

                    if ($isUserAdmin) {
                        $strQuery .= ", user_id = :user_id";
                    } else {
                        // prevent writers to change the owner of the page
                        unset($pageData["user_id"]);
                    }

                    $strQuery .= " WHERE id = :id";
                }
                else { // is create
                    $strQuery = "INSERT INTO";
                    $strQuery .= " pages(title, slug, content, published, user_id, creation_date, allow_comments"; // I do that in two lines so that PhpStorm stop complaining about the query string not being complete

                    if ($section === "pages") {
                        $strQuery .= ", parent_page_id)";
                    } else {
                        $strQuery .= ", category_id)";
                    }

                    $strQuery .= "VALUES(:title, :slug, :content, :published, :user_id, :creation_date, :allow_comments";

                    if ($section === "pages") {
                        $strQuery .= ", :parent_page_id)";
                    } else {
                        $strQuery .= ", :category_id)";
                    }

                    if (! $isUserAdmin) {
                        $pageData["user_id"] = $userId;
                    }
                }

                $params = $pageData;
                unset($params["user_name"]);
                if ($params["parent_page_id"] === 0) {
                    $params["parent_page_id"] = null;
                    // do not use unset() because the number of entries in the data will not match the number of parameters in the request (plus you actually wants the value to be updated to NULL)
                }

                if ($isUpdate) {
                    unset($params["creation_date"]);
                } else {
                    unset($params["id"]);
                    $params["creation_date"] = date("Y-m-d");
                }

                if ($section === "pages") {
                    unset($params["category_id"]);
                } else {
                    unset($params["parent_page_id"]);
                }

                $success = queryDB($strQuery, $params, true);

                if ($success) {
                    $redirectId = null;
                    if ($isUpdate) {
                        addSuccess("$terms[ucsingular] edited with success.");
                        // reload the page to make to fetch the last data from the db
                        // can help spot field that aren't actually updated
                        $redirectId = $pageData["id"];
                    } else {
                        addSuccess("$terms[ucsingular] added with success.");
                        $redirectId = $db->lastInsertId();
                    }

                    redirect("admin:$section", "update", $redirectId);
                    return;
                } else {
                    $_action = "adding";
                    if ($isUpdate) {
                        $_action = "editting";
                    }
                    addError("There was an error $_action the $terms[singular].");
                }
            }
        }
    }
    // no post data


    $formTarget = buildUrl("admin:$section", $action, $queryId);

    $frontSection = trim($section, "s");

    $previewLink = buildUrl($frontSection, null, $queryId);
    if ($config["use_url_rewrite"]) {
        $previewLink = buildUrl($frontSection, null, $pageData["slug"]);
    }
?>

<?php if ($isUpdate): ?>
    <h2>Edit <?= $terms["singular"] ?> with id <?= $queryId; ?></h2>

    <p>
        <a href="<?= $previewLink; ?>">View <?= $terms["singular"] ?></a>
    </p>
<?php else: ?>
    <h2>Add a new <?= $terms["singular"] ?></h2>
<?php endif; ?>

<?php require_once __dir__ . "/../messages.php"; ?>

<form action="<?= $formTarget; ?>" method="post">

    <label>Title: <input type="text" name="title" required value="<?php safeEcho($pageData["title"]); ?>"></label> <br>
    <br>

    <label>Slug: <input type="text" name="slug" required value="<?php safeEcho($pageData["slug"]); ?>"></label> The 'beautiful' URL of the page. Can only contains letters, numbers, hyphens and underscores. <br>
    <br>

    <label>Content : <br>
    <textarea name="content" cols="60" rows="15"><?php safeEcho($pageData["content"]); ?></textarea></label><br>
    <br>

    <?php if ($section === "pages"): ?>
        <label>Parent page:
            <select name="parent_page_id">
                <option value="0">None</option>
                <?php
                $id = $pageData["id"]; // null when action = create
                if ($action === "create") {
                    $id = -1; // if id is null below it causes a General error: 2031
                }
                $topLevelPages = queryDB("SELECT id, title FROM pages WHERE parent_page_id IS NULL AND id <> ? ORDER BY title ASC", $id);
                ?>
                <?php while($page = $topLevelPages->fetch()): ?>
                    <option value="<?= $page["id"]; ?>" <?= ($pageData["parent_page_id"] === $page["id"]) ? "selected" : null; ?>><?php safeEcho($page["title"]); ?></option>
                <?php endwhile; ?>
            </select>
        </label> <br>
        <br>
    <?php else: // posts ?>
        <label>Category:
            <select name="category_id">
                <?php $categories = queryDB("SELECT id, title FROM categories ORDER BY title ASC"); ?>
                <?php while($category = $categories->fetch()): ?>
                <option value="<?= $category["id"]; ?>" <?= ($pageData["category_id"] === $category["id"]) ? "selected" : null; ?>><?php safeEcho($category["title"]); ?></option>
                <?php endwhile; ?>
            </select>
        </label> <br>
        <br>
    <?php endif; ?>

    <label>Publication status:
        <select name="published">
            <option value="0" <?= ($pageData["published"] === 0) ? "selected" : null; ?>>Draft</option>
            <option value="1" <?= ($pageData["published"] === 1) ? "selected" : null; ?>>Published</option>
        </select>
    </label> <br>
    <br>

    <?php if ($isUserAdmin): ?>
    <label>Owner:
        <select name="user_id">
            <?php $users = queryDB("SELECT id, name FROM users WHERE role <> 'commenter' ORDER BY name ASC"); ?>
            <?php while($user = $users->fetch()): ?>
                <option value="<?= $user["id"]; ?>" <?= ($pageData["user_id"] === $user["id"]) ? "selected" : null; ?>><?= $user["name"]; ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>
    <br>
    <?php elseif ($isUpdate): ?>
    Written by: <?= $pageData["user_name"]; ?>
    <?php endif; ?>

    <label>Allow comments: <input type="checkbox" name="allow_comments" <?= ($pageData["allow_comments"] === 1) ? "checked" : null; ?>></label> <br>
    <br>

    <?php addCSRFFormField("$section$action"); ?>

    <?php if ($isUpdate): ?>
        <input type="submit" value="Edit">
    <?php else: ?>
        <input type="submit" value="Add">
    <?php endif; ?>
</form>

<?php
} // end if action = add or edit

// --------------------------------------------------

elseif ($action === "delete") {
    if (verifyCSRFToken($query['csrftoken'], "delete$section")) {
        $page = queryDB("SELECT id, user_id FROM pages WHERE id = ?", $queryId)->fetch();

        if (is_array($page)) {
            if (! $isUserAdmin && $page["user_id"] !== $queryId) {
                addError("As a writer, you can only delete your own $terms[plural].");
            } else {
                $success = queryDB("DELETE FROM pages WHERE id = ?", $queryId, true);

                if ($success) {
                    // unparent all pages that are a child of the one deleted
                    if ($section === "pages") {
                        queryDB("UPDATE pages SET parent_page_id = NULL WHERE parent_page_id = ?", $queryId);
                    }

                    queryDB("DELETE FROM comments WHERE page_id = ?", $queryId);

                    addSuccess("$terms[singular] deleted with success.");
                } else {
                    addError("There was an error deleting the $terms[singular]");
                }
            }
        } else {
            addError("Unknown $terms[singular] with id $queryId.");
        }
    }

    redirect("admin:$section", "read");
    return;
}

// --------------------------------------------------
// if action == "show" or other actions are fobidden for that page

else {
?>

<h2>List of all <?= $terms["plural"]; ?></h2>

<?php require_once __dir__ . "/../messages.php"; ?>

<div>
    <a href="<?= buildUrl("admin:$section", "create"); ?>">Add a <?= $terms["singular"]; ?></a>
</div>

<br>

<table>
    <tr>
        <th>id <?= getTableSortButtons("pages", "id"); ?></th>
        <th>title <?= getTableSortButtons("pages", "title"); ?></th>
        <th>Slug <?= getTableSortButtons("pages", "slug"); ?></th>
        <?php if ($section === "pages"): ?>
        <th>Parent page <?= getTableSortButtons("parent_pages", "title"); ?></th>
        <?php else: ?>
        <th>Category <?= getTableSortButtons("categories", "title"); ?></th>
        <?php endif; ?>
        <th>creator <?= getTableSortButtons("users", "name"); ?></th>
        <th>creation date <?= getTableSortButtons("pages", "creation_date"); ?></th>
        <th>Status <?= getTableSortButtons("pages", "published"); ?></th>
        <th>Allow Comments <?= getTableSortButtons("pages", "allow_comments"); ?></th>
    </tr>

<?php
    $tables = ["pages", "parent_pages", "users", "categories"];
    if (! in_array($query['orderbytable'], $tables)) {
        $query['orderbytable'] = "pages";
    }

    $fields = ["id", "title", "slug", "creation_date", "published", "allow_comments", "name"];
    if (! in_array($query['orderbyfield'], $fields)) {
        $query['orderbyfield'] = "id";
    }

    $strQuery = "SELECT pages.*, users.name as user_name";

    if ($section === "pages") {
        $strQuery .= ", parent_pages.slug as parent_page_slug";
    } else {
        $strQuery .= ", categories.slug as category_slug";
    }

    $strQuery .= "\n FROM pages \n LEFT JOIN users ON pages.user_id = users.id";

    if ($section === "pages") {
        $strQuery .= "\n LEFT JOIN pages as parent_pages ON pages.parent_page_id = parent_pages.id";
        $strQuery .= "\n WHERE pages.category_id IS NULL";
    } else {
        $strQuery .= "\n LEFT JOIN categories ON pages.category_id = categories.id";
        $strQuery .= "\n WHERE category_id IS NOT NULL";
    }

    $strQuery .= "\n ORDER BY $query[orderbytable].$query[orderbyfield] $query[orderdir]
        LIMIT " . $adminMaxTableRows * ($query['page'] - 1) . ", $adminMaxTableRows";

    $query = queryDB($strQuery);

    $deleteToken = setCSRFToken("delete$section");

    while ($page = $query->fetch()) {
?>
    <tr>
        <td><?= $page["id"]; ?></td>
        <td><?php safeEcho($page["title"]); ?></td>
        <td><?php safeEcho($page["slug"]); ?></td>

        <?php if ($section === "pages"): ?>
            <td>
                <?php
                if ($page["parent_page_id"] != null)
                    safeEcho($page["parent_page_slug"]);
                ?>
            </td>
        <?php else: ?>
            <td>
                <?php
                if ($page["category_id"] != null)
                    safeEcho($page["category_slug"]);
                ?>
            </td>
        <?php endif; ?>

        <td><?php safeEcho($page["user_name"]); ?></td>
        <td><?= $page["creation_date"]; ?></td>
        <td><?= $page["published"] ? "Published" : "Draft"; ?></td>
        <td><?= $page["allow_comments"]; ?></td>

        <td><a href="<?= buildUrl("admin:$section", "update", $page["id"]); ?>">Edit</a></td>

        <?php if($isUserAdmin || $page["user_id"] === $userId): ?>
            <td><a href="<?= buildUrl("admin:$section", "delete", $page["id"], $deleteToken); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>
<?php
    } // end while pages from DB
?>
</table>


<?php
    $table = "pages";
    require_once __dir__ . "/pagination.php";
}
