<?php
if ($user["role"] === "commenter") {
    redirect($folder);
}

$title = "Pages";
require_once "header.php";
?>

<h1>Pages</h1>

<?php
if ($action === "add" || $action === "edit") {

    $pageData = [
        "id" => $resourceId,
        "title" => "",
        "slug" => "",
        "content" => "",
        "menu_priority" => 0,
        "parent_page_id" => 0,
        "editable_by_all" => 0,
        "published" => 0,
        "user_id" => 0,
        "allow_comments" => 0,
    ];

    $isEdit = ($action === "edit");

    if (isset($_POST["title"])) {
        // fill $pageData with content from the form
        foreach($pageData as $key => $value) {
            if (isset($_POST[$key])) {
                if ($value === 0) {
                    if ($key === "editable_by_all" || $key === "allow_comments") {
                        $_POST[$key] === "on" ? $pageData[$key] = 1 : null;
                    }
                    else {
                        $pageData[$key] = (int)$_POST[$key];
                    }
                }
                else {
                    $pageData[$key] = $_POST[$key];
                }
            }
        }

        $dataOK = checkPageTitleFormat($pageData["title"]);

        $dataOK = (checkURLNameFormat($pageData["slug"]) && $dataOK);

        // check that the url name doesn't already exist in other pages
        $strQuery = "SELECT id, title FROM pages WHERE slug=:slug";
        $params = ["slug" => $pageData["slug"]];

        if ($isEdit) {
            $strQuery .= ' AND id <> :own_id';
            $params["own_id"] = $pageData["id"];
        }

        $page = queryDB($strQuery, $params)->fetch();
        if (is_array($page)) {
            addError("The page with id ".$page["id"]." and title '".$page["title"]."' already has the URL name '".$pageData["slug"]."' .");
            $dataOK = false;
        }

        if ($pageData["parent_page_id"] !== 0) {
            // check the id of the parent page, that it's indeed a parent page (a page that isn't a child of another page)

            if ($pageData["parent_page_id"] === $pageData["id"]) {
                addError("The page can not be parented to itself.");
            }
            else {
                $parentPage= queryDB("SELECT id, parent_page_id FROM pages WHERE id = ?", $pageData["parent_page_id"])->fetch();

                if ($parentPage === false) {
                    addError("The parent page with id '".$pageData["parent_page_id"]."' does not exist .");
                    $pageData["parent_page_id"] = 0;
                    $dataOK = false;
                }
                elseif ($parentPage["parent_page_id"] !== null) {
                    addError("The selected parent page (with id '".$parentPage["id"]."') is actually a children of another page (with id '".$parentPage["parent_page_id"]."'), so it can't be a parent page itself.");
                    $pageData["parent_page_id"] = 0;
                    $dataOK = false;
                }
            }
        }

        if ($pageData["menu_priority"] < 0) {
            addError("The menu priority must be a positiv number");
            $pageData["menu_priority"] = 0;
            $dataOK = false;
        }

        // check that user actually exists
        if ($pageData["user_id"] > 0) {
            $user = queryDB("SELECT id FROM users WHERE id = ?", $pageData["user_id"])->fetch();

            if ($user === false) {
                addError("User with id '".$pageData["user_id"]."' doesn't exists.");
                $pageData["user_id"] = $userId; // for security, maybe should get the first admin's id ?
                $dataOK = false;
            }
        }

        // no check on format of numerical fields since they are already converted to int. If the posted value wasn't numerical, it is now 0
        // no check on content

        if ($dataOK) {
            $strQuery = "";

            if ($isEdit) {
                $strQuery = "UPDATE pages SET title=:title, slug=:slug, content=:content, menu_priority=:menu_priority, parent_page_id=:parent_page_id, editable_by_all=:editable_by_all, published=:published, allow_comments=:allow_comments";

                // prevent writers to change the owner of the page
                if ($isUserAdmin) {
                    $strQuery .= ", user_id=:user_id";
                }
                else {
                    unset($pageData["user_id"]);
                }

                $strQuery .= " WHERE id=:id";
            }
            else {
                $strQuery = "INSERT INTO pages(title, slug, content, menu_priority, parent_page_id, editable_by_all, published, user_id, creation_date, allow_comments)
                VALUES(:title, :slug, :content, :menu_priority, :parent_page_id, :editable_by_all, :published, :user_id, :creation_date, :allow_comments)";

                if (! $isUserAdmin) {
                    $pageData["user_id"] = $userId;
                }
            }

            $query = $db->prepare($strQuery);

            $params = $pageData;
            if ($params["parent_page_id"] === 0) {
                $params["parent_page_id"] = null;
                // do not use unset() because the number of entries in the data will not match the number of parameters in the request (plus you actually wants the value to be updated to NULL)
            }

            if (! $isEdit) {
                unset($params["id"]);
                $params["user_id"] = $userId;
                $params["creation_date"] = date("Y-m-d");
            }

            $success = $query->execute($params);

            if ($success) {
                $redirectionId = null;
                if ($isEdit) {
                    addSuccess("Page edited with success.");
                    // reload the page to make to fetch the last data from the db
                    // can help spot field that aren't actually updated
                    $redirectionId = $pageData["id"];
                }
                else {
                    addSuccess("Page added with success.");
                    $redirectionId = $db->lastInsertId();
                }

                redirect($folder, "pages", "edit", $redirectionId);
            }
            elseif ($isEdit) {
                addError("There was an error editing the page");
            }
            else {
                addError("There was an error adding the page");
            }
        }
    }
    elseif ($isEdit) {
        $page = queryDB("SELECT * FROM pages WHERE id = ?", $resourceId)->fetch();

        if (is_array($page)) {
            if (! $isUserAdmin && $page["user_id"] !== $userId && $page["editable_by_all"] === 0) {
                addError("This page is only editable by admins and its owner");
                redirect($folder, "pages");
            }
            else {
                $pageData = $page;
            }
        }
        else {
            addError("unknown page with id $resourceId");
            redirect($folder, "pages");
        }
    }

    $formTarget = buildLink($folder, "pages", $action, $resourceId);
    /*if ($isEdit) {
        $formTarget = buildLink($folder, "pages", $action, $resourceId);
    }*/
?>

<?php if ($isEdit): ?>
    <h2>Edit page with id <?php echo $resourceId; ?></h2>

    <p>
        <a href="<?php echo buildLink(null, $resourceId); ?>">View page</a>
    </p>
<?php else: ?>
    <h2>Add a new page</h2>
<?php endif; ?>

<?php require_once "../app/messages.php"; ?>

<form action="<?php echo $formTarget; ?>" method="post">

    <label>Title : <input type="text" name="title" required value="<?php echo $pageData["title"]; ?>"></label> <br>
    <br>

    <label>Slug : <input type="text" name="slug" required value="<?php echo $pageData["slug"]; ?>"></label> <?php createTooltip("The 'beautiful' URL of the page. Can only contains letters, numbers, hyphens and underscores."); ?> <br>
    <br>

    <label>Content : <br>
    <textarea name="content" cols="60" rows="15"><?php echo $pageData["content"]; ?></textarea></label><br>
    <br>

    <label>Menu Priority : <input type="number" name="menu_priority" required pattern="[0-9]{1,}" value="<?php echo $pageData["menu_priority"]; ?>"></label> <?php createTooltip("Determines the order in which the pages are shown in the menu. Lower priority = first. Only positiv number."); ?> <br>
    <br>

    <label>Parent page :
        <select name="parent_page_id">
            <option value="0">None</option>
            <?php
            $topLevelPages = queryDB('SELECT id, title FROM pages WHERE parent_page_id IS NULL AND id <> ? ORDER BY title ASC', $pageData["id"]);
            ?>
            <?php while($page = $topLevelPages->fetch()): ?>
                <option value="<?php echo $page["id"]; ?>" <?php echo ($pageData["parent_page_id"] === $page["id"]) ? "selected" : null; ?>><?php echo $page["title"]; ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>
    <br>

    <label>Can be edited by any user : <input type="checkbox" name="editable_by_all" <?php echo ($pageData["editable_by_all"] === 1) ? "checked" : null; ?>> </label><br>
    <br>

    <label>Publication status :
        <select name="published">
            <option value="0" <?php echo ($pageData["published"] === 0) ? "selected" : null; ?>>Draft</option>
            <option value="1" <?php echo ($pageData["published"] === 1) ? "selected" : null; ?>>Published</option>
        </select>
    </label> <br>
    <br>

    <?php if ($isUserAdmin): ?>
    <label>Owner :
        <select name="user_id">
            <?php $users = queryDB('SELECT id, name FROM users ORDER BY name ASC'); ?>
            <?php while($user = $users->fetch()): ?>
            <option value="<?php echo $user["id"]; ?>" <?php echo ($pageData["user_id"] === $user["id"]) ? "selected" : null; ?>><?php echo $user["name"]; ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>
    <br>
    <?php endif; ?>

    <label>Allow comments : <input type="checkbox" name="allow_comments" <?php echo ($pageData["allow_comments"] === 1) ? "checked" : null; ?>></label> <br>
    <br>

    <?php if ($isEdit): ?>
    <input type="submit" value="Edit">
    <?php else: ?>
    <input type="submit" value="Add">
    <?php endif; ?>
</form>

<?php
} // end if action = add or edit

// --------------------------------------------------

elseif ($action === "delete") {
    $page = queryDB('SELECT id, user_id FROM pages WHERE id = ?', $resourceId)->fetch();

    if (is_array($page)) {
        if (! $isUserAdmin && $page["user_id"] !== $userId) {
            addError("Must be admin");
        }
        else {
            $success = queryDB('DELETE FROM pages WHERE id = ?', $resourceId, true);

            if ($success) {
                // unparent all pages that are a child of the one deleted
                queryDB('UPDATE pages SET parent_page_id = NULL WHERE parent_page_id = ?', $resourceId);
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

<h2>List of all pages</h2>

<?php require_once "../app/messages.php"; ?>

<div>
    <a href="<?php echo buildLink($folder, "pages", "add"); ?>">Add a page</a>
</div>

<br>

<table>
    <tr>
        <th>id <?php echo printTableSortButtons("pages", "id"); ?></th>
        <th>title <?php echo printTableSortButtons("pages", "title"); ?></th>
        <th>Slug <?php echo printTableSortButtons("pages", "slug"); ?></th>
        <th>Parent page <?php echo printTableSortButtons("parent_pages", "title"); ?></th>
        <th>Menu priority <?php echo printTableSortButtons("pages", "menu_priority"); ?></th>
        <th>creator <?php echo printTableSortButtons("users", "name"); ?></th>
        <th>creation date <?php echo printTableSortButtons("pages", "creation_date"); ?></th>
        <th>editable by all <?php echo printTableSortButtons("pages", "editable_by_all"); ?></th>
        <th>Status <?php echo printTableSortButtons("pages", "published"); ?></th>
        <th>Allow Comments <?php echo printTableSortButtons("pages", "allow_comments"); ?></th>
    </tr>

<?php
    $tables = ["pages", "parent_pages", "users"];
    if (! in_array($orderByTable, $tables)) {
        $orderByTable = "pages";
    }

    $fields = ["id", "title", "slug", "menu_priority", "creation_date", "editable_by_all", "published", "allow_comments"];
    if (! in_array($orderByField, $fields)) {
        $orderByField = "id";
    }

    $pages = queryDB(
        "SELECT pages.*,
        users.name as user_name,
        parent_pages.title as parent_page_title,
        parent_pages.menu_priority as parent_page_priority
        FROM pages
        LEFT JOIN users ON pages.user_id=users.id
        LEFT JOIN pages as parent_pages ON pages.parent_page_id=parent_pages.id
        ORDER BY $orderByTable.$orderByField $orderDir"
    );

    while ($page = $pages->fetch()) {
?>
    <tr>
        <td><?php echo $page["id"]; ?></td>
        <td><?php echo $page["title"]; ?></td>
        <td><?php echo $page["slug"]; ?></td>
        <td>
            <?php
            if ($page["parent_page_id"] != null)
                echo $page["parent_page_title"]." (".$page["parent_page_id"].")";
            ?>
        </td>

        <?php if ($page["parent_page_id"] !== null): ?>
        <td><?php echo $page["parent_page_priority"].".".$page["menu_priority"]; ?></td>
        <?php else: ?>
        <td><?php echo $page["menu_priority"]; ?></td>
        <?php endif; ?>

        <td><?php echo $page["user_name"]; ?></td>
        <td><?php echo $page["creation_date"]; ?></td>
        <td><?php echo $page["editable_by_all"] == 1 ? "Yes": "No"; ?></td>
        <td><?php echo $page["published"] ? "Published" : "Draft"; ?></td>
        <td><?php echo $page["allow_comments"]; ?></td>

        <?php if($isUserAdmin || $page["user_id"] == $userId || $page["editable_by_all"] == 1): ?>
        <td><a href="<?php echo buildLink($folder, "pages", "edit", $page["id"]); ?>">Edit</a></td>
        <?php endif; ?>

        <?php if($isUserAdmin || $page["user_id"] == $userId): ?>
        <td><a href="<?php echo buildLink($folder, "pages", "delete", $page["id"]); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>
<?php
    } // end while pages from DB
?>
</table>
<?php
} // end if action = show
