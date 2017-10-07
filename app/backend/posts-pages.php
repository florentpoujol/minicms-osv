<?php
if ($user["role"] === "commenter") {
    redirect($folder);
}

// this page is used for the pages as well as for posts

$terms = [
    "plural" => "pages",
    "singular" => "page",
    "ucplural" => "Pages",
    "ucsingular" => "Page",
];

if ($resourceName === "posts") {
    $terms = [
        "plural" => "posts",
        "singular" => "post",
        "ucplural" => "Posts",
        "ucsingular" => "Post",
    ];
}

$title = $terms["ucplural"];
require_once "header.php";
?>

<h1><?php echo $terms["ucplural"] ?></h1>

<?php
if ($action === "add" || $action === "edit") {

    $pageData = [
        "id" => $resourceId,
        "title" => "",
        "slug" => "",
        "content" => "",
        "parent_page_id" => 0,
        "category_id" => 0,
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
                    if ($key === "allow_comments") {
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

        $dataOK = (checkSlugFormat($pageData["slug"]) && $dataOK);

        // check that the slug doesn't already exist in other pages
        $strQuery = "SELECT id, title FROM pages WHERE slug=:slug";
        $params = ["slug" => $pageData["slug"]];

        $strQuery .= " AND category_id IS ".($resourceName === "pages" ? "": "NOT")." NULL";

        if ($isEdit) {
            $strQuery .= ' AND id <> :own_id';
            $params["own_id"] = $pageData["id"];
        }

        $page = queryDB($strQuery, $params)->fetch();
        if (is_array($page)) {
            addError("The ".$terms["singular"]." with id ".$page["id"]." and title '".$page["title"]."' already has the slug '".$pageData["slug"]."' .");
            $dataOK = false;
        }

        if ($resourceName === "pages" && $pageData["parent_page_id"] !== 0) {
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

        // check that the category exists
        if ($resourceName === "posts") {
            $cat = queryDB("SELECT id FROM categories WHERE id = ?", $pageData["category_id"])->fetch();

            if ($cat === false) {
                addError("The category with id '".$pageData["parent_page_id"]."' does not exist .");
                $pageData["category_id"] = null;
                $dataOK = false;
            }
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
                $strQuery = "UPDATE pages SET title=:title, slug=:slug, content=:content, published=:published, allow_comments=:allow_comments";

                if ($resourceName === "pages") {
                    $strQuery .= ", parent_page_id=:parent_page_id";
                }
                else {
                    $strQuery .= ", category_id=:category_id";
                }

                if ($isUserAdmin) {
                    $strQuery .= ", user_id=:user_id";
                }
                else {
                    // prevent writers to change the owner of the page
                    unset($pageData["user_id"]);
                }

                $strQuery .= " WHERE id=:id";
            }
            else {

                $strQuery = "INSERT INTO pages(title, slug, content, published, user_id, creation_date, allow_comments";

                if ($resourceName === "pages") {
                    $strQuery .= ", parent_page_id)";
                }
                else {
                    $strQuery .= ", category_id)";
                }

                $strQuery .= "VALUES(:title, :slug, :content, :published, :user_id, :creation_date, :allow_comments";

                if ($resourceName === "pages") {
                    $strQuery .= ", :parent_page_id)";
                }
                else {
                    $strQuery .= ", :category_id)";
                }

                if (! $isUserAdmin) {
                    $pageData["user_id"] = $userId;
                }
            }

            var_dump($strQuery);
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

            if ($resourceName === "pages") {
                unset($params["category_id"]);
            }
            else {
                unset($params["parent_page_id"]);
            }

            $success = $query->execute($params);

            if ($success) {
                $redirectionId = null;
                if ($isEdit) {
                    addSuccess($terms["ucsingular"]." edited with success.");
                    // reload the page to make to fetch the last data from the db
                    // can help spot field that aren't actually updated
                    $redirectionId = $pageData["id"];
                }
                else {
                    addSuccess($terms["ucsingular"]." added with success.");
                    $redirectionId = $db->lastInsertId();
                }

                redirect($folder, $resourceName, "edit", $redirectionId);
            }
            else {
                $_action = "adding";
                if ($isEdit) {
                    $_action = "editting";
                }
                addError("There was an error $_action the ".$terms["singular"]);
            }
        }
    }
    elseif ($isEdit) {
        $strQuery = "SELECT * FROM pages WHERE id = ?";
        if ($resourceName === "pages") {
            $strQuery .= " AND category_id IS NULL";
        }
        else {
            $strQuery .= " AND category_id IS NOT NULL";
        }

        $page = queryDB($strQuery, $resourceId)->fetch();

        if (is_array($page)) {
            $pageData = $page;
        }
        else {
            addError("unknown ".$terms["singular"]." with id $resourceId");
            redirect($folder, $resourceName);
        }
    }

    $formTarget = buildLink($folder, $resourceName, $action, $resourceId);

    $_folder = null;
    if ($resourceName === "posts") {
        $_folder = "blog";
    }

    $previewLink = buildLink($_folder, $resourceId);
    if ($config["use_url_rewrite"]) {
        $previewLink = buildLink($_folder, $pageData["slug"]);
    }
?>

<?php if ($isEdit): ?>
    <h2>Edit <?php echo $terms["singular"] ?> with id <?php echo $resourceId; ?></h2>

    <p>
        <a href="<?php echo $previewLink; ?>">View <?php echo $terms["singular"] ?></a>
    </p>
<?php else: ?>
    <h2>Add a new <?php echo $terms["singular"] ?></h2>
<?php endif; ?>

<?php require_once "../app/messages.php"; ?>

<form action="<?php echo $formTarget; ?>" method="post">

    <label>Title : <input type="text" name="title" required value="<?php safeEcho($pageData["title"]); ?>"></label> <br>
    <br>

    <label>Slug : <input type="text" name="slug" required value="<?php safeEcho($pageData["slug"]); ?>"></label> The 'beautiful' URL of the page. Can only contains letters, numbers, hyphens and underscores. <br>
    <br>

    <label>Content : <br>
    <textarea name="content" cols="60" rows="15"><?php safeEcho($pageData["content"]); ?></textarea></label><br>
    <br>

    <?php if ($resourceName === "pages"): ?>
    <label>Parent page :
        <select name="parent_page_id">
            <option value="0">None</option>
            <?php
            $id = $pageData["id"];
            if ($action === "add") {
                $id = -1;
            }
            // $topLevelPages = queryDB("SELECT id, title FROM pages WHERE parent_page_id IS NULL AND id <> ? ORDER BY title ASC", $pageData["id"]);
            $topLevelPages = queryDB("SELECT id, title FROM pages WHERE parent_page_id IS NULL AND id <> ? ORDER BY title ASC", $id);
            ?>
            <?php while($page = $topLevelPages->fetch()): ?>
                <option value="<?php echo $page["id"]; ?>" <?php echo ($pageData["parent_page_id"] === $page["id"]) ? "selected" : null; ?>><?php safeEcho($page["title"]); ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>
    <br>
    <?php else: ?>
    <label>Category :
        <select name="category_id">
            <?php $cats = queryDB("SELECT id, title FROM categories ORDER BY title ASC"); ?>
            <?php while($cat = $cats->fetch()): ?>
            <option value="<?php echo $cat["id"]; ?>" <?php echo ($pageData["category_id"] === $cat["id"]) ? "selected" : null; ?>><?php safeEcho($cat["title"]); ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>
    <br>
    <?php endif; ?>

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
            <?php $users = queryDB("SELECT id, name FROM users ORDER BY name ASC"); ?>
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
    if (verifyCSRFToken("deletepage")) {
        $page = queryDB("SELECT id, user_id FROM pages WHERE id = ?", $resourceId)->fetch();

        if (is_array($page)) {
            if (! $isUserAdmin && $page["user_id"] !== $userId) {
                addError("Must be admin");
            }
            else {
                $success = queryDB("DELETE FROM pages WHERE id = ?", $resourceId, true);

                if ($success) {
                    // unparent all pages that are a child of the one deleted
                    if ($resourceName === "pages") {
                        queryDB("UPDATE pages SET parent_page_id = NULL WHERE parent_page_id = ?", $resourceId);
                    }

                    queryDB("DELETE FROM comments WHERE page_id = ?", $resourceId);

                    addSuccess($terms["singular"]." deleted with success");
                }
                else {
                    addError("There was an error deleting the ".$terms["singular"]);
                }
            }
        }
        else {
            addError("Unknow ".$terms["singular"]." with id $resourceId");
        }
    }

    redirect($folder, $resourceName);
}

// --------------------------------------------------
// if action == "show" or other actions are fobidden for that page

else {
?>

<h2>List of all <?php echo $terms["plural"]; ?></h2>

<?php require_once "../app/messages.php"; ?>

<div>
    <a href="<?php echo buildLink($folder, $resourceName, "add"); ?>">Add a <?php echo $terms["singular"]; ?></a>
</div>

<br>

<table>
    <tr>
        <th>id <?php echo printTableSortButtons("pages", "id"); ?></th>
        <th>title <?php echo printTableSortButtons("pages", "title"); ?></th>
        <th>Slug <?php echo printTableSortButtons("pages", "slug"); ?></th>
        <?php if ($resourceName === "pages"): ?>
        <th>Parent page <?php echo printTableSortButtons("parent_pages", "title"); ?></th>
        <?php else: ?>
        <th>Category <?php echo printTableSortButtons("categories", "title"); ?></th>
        <?php endif; ?>
        <th>creator <?php echo printTableSortButtons("users", "name"); ?></th>
        <th>creation date <?php echo printTableSortButtons("pages", "creation_date"); ?></th>
        <th>Status <?php echo printTableSortButtons("pages", "published"); ?></th>
        <th>Allow Comments <?php echo printTableSortButtons("pages", "allow_comments"); ?></th>
    </tr>

<?php
    $tables = ["pages", "parent_pages", "users", "categories"];
    if (! in_array($orderByTable, $tables)) {
        $orderByTable = "pages";
    }

    $fields = ["id", "title", "slug", "creation_date", "published", "allow_comments", "name"];
    if (! in_array($orderByField, $fields)) {
        $orderByField = "id";
    }


    $strQuery = "SELECT pages.*, users.name as user_name";

    if ($resourceName === "pages") {
        $strQuery .= ", parent_pages.slug as parent_page_slug";
    }
    else {
        $strQuery .= ", categories.slug as category_slug";
    }

    $strQuery .= "\n FROM pages \n LEFT JOIN users ON pages.user_id=users.id";

    if ($resourceName === "pages") {
        $strQuery .= "\n LEFT JOIN pages as parent_pages ON pages.parent_page_id=parent_pages.id";
        $strQuery .= "\n WHERE pages.category_id IS NULL";
    }
    else {
        $strQuery .= "\n LEFT JOIN categories ON pages.category_id=categories.id";
        $strQuery .= "\n WHERE category_id IS NOT NULL";
    }

    $strQuery .= "\n ORDER BY $orderByTable.$orderByField $orderDir
        LIMIT ".$adminMaxTableRows * ($pageNumber - 1).", $adminMaxTableRows";

    $query = queryDB($strQuery);


    while ($page = $query->fetch()) {
?>
    <tr>
        <td><?php echo $page["id"]; ?></td>
        <td><?php safeEcho($page["title"]); ?></td>
        <td><?php safeEcho($page["slug"]); ?></td>

        <?php if ($resourceName === "pages"): ?>
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
        <td><?php echo $page["creation_date"]; ?></td>
        <td><?php echo $page["published"] ? "Published" : "Draft"; ?></td>
        <td><?php echo $page["allow_comments"]; ?></td>

        <td><a href="<?php echo buildLink($folder, $resourceName, "edit", $page["id"]); ?>">Edit</a></td>

        <?php if($isUserAdmin || $page["user_id"] === $userId):
        $deleteToken = setCSRFTokens("deletepage");
        ?>
        <td><a href="<?php echo buildLink($folder, $resourceName, "delete", $page["id"], $deleteToken); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>
<?php
    } // end while pages from DB
?>
</table>


<?php
    $table = "pages";
    require_once "pagination.php";
} // end if action = show
