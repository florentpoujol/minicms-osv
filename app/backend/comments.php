<?php
$title = "Comments";
require_once "header.php";
?>

<h1>Comments</h1>

<?php
if ($action === "edit") {
    $commentData = [
        "id" => $resourceId,
        "page_id" => 0,
        "user_id" => 0,
        "text" => ""
    ];

    $commentFromDB = queryDB(
        "SELECT comments.*, pages.user_id as pages_user_id
        FROM comments
        LEFT JOIN pages ON pages.id=comments.page_id
        WHERE comments.id=?",
        $resourceId
    )->fetch();

    if ($commentFromDB === false) {
        addError("Unknow comment");
        redirect(["p" => "comments"]);
    }

    if (($user["role"] === "commenter" && $commentFromDB["user_id"] !== $userId) ||
        ($user["role"] === "writer" && $commentFromDB["page_user_id"] !== $userId)) {
        addError("You are not authorized to edit this comment.");
        redirect(["p" => "comments"]);
    }

    if (isset($_POST["comment_text"])) {
        $commentData["page_id"] = (int)$_POST["comment_page_id"];
        $commentData["user_id"] = (int)$_POST["comment_user_id"];
        $commentData["text"] = $_POST["comment_text"];

        if (verifyCSRFToken($_POST["csrf_token"], "commentedit")) {
            $dataOK = true;

            $page = queryDB("SELECT id FROM pages WHERE id=?", $commentData["page_id"])->fetch();
            if ($page === false) {
                addError("The page with id '".$commentData["page_id"]."' does not exist.");
                $commentData["page_id"] = -1;
                $dataOK = false;
            }

            $user = queryDB("SELECT id FROM users WHERE id=?", $commentData["user_id"])->fetch();
            if ($user === false) {
                adError("The user with id '".$commentData["user_id"]."' does not exist.");
                $commentData["user_id"] = $userId;
                $dataOK = false;
            }

            if ($dataOK) {
                $success = queryDB(
                    "UPDATE comments SET page_id=:page_id, user_id=:user_id, text=:text WHERE id=:id",
                    [
                        "id" => $commentData["id"],
                        "page_id" => $commentData["page_id"],
                        "user_id" => $commentData["user_id"],
                        "text" => $commentData["text"]
                    ],
                    true
                );

                if ($success) {
                    addSuccess("Comment edited successfully.");
                }
                else {
                    addError("There was an error editing the comment");
                }
            }
        }
    }
    else {
        $commentData = $commentFromDB;
    }
?>

<h2>Edit comment with id <?php echo $commentData["id"]; ?></h2>

<?php require_once "../app/messages.php"; ?>

<form action="<?php echo buildLink($folder, "comment", "edit", $commentData["id"]); ?>" method="post">
    <label>Content : <br>
        <textarea name="comment_text" cols="40" rows="5"><?php safeEcho($commentData["text"]); ?></textarea>
    </label> <br>

    <label>Parent page :
        <select name="comment_page_id">
            <?php $pages = queryDB('SELECT id, title FROM pages ORDER BY title ASC'); ?>
            <?php while($page = $pages->fetch()): ?>
                <option value="<?php echo $page["id"]; ?>" <?php echo ($commentData["page_id"] === $page["id"]) ? "selected" : null; ?>><?php safeEcho($page["title"]); ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>

    <label>User :
        <select name="comment_user_id">
            <?php $users = queryDB('SELECT id, name FROM users ORDER BY name ASC'); ?>
            <?php while($user = $users->fetch()): ?>
                <option value="<?php echo $user["id"]; ?>" <?php echo ($commentData["user_id"] === $user["id"]) ? "selected" : null; ?>><?php safeEcho($user["name"]); ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>

    <?php echo date("Y-m-d H:i:s", $commentData["creation_time"]); ?>
    <br>

    <?php addCSRFFormField("commentedit"); ?>

    <input type="submit" name="Edit comment">
</form>

<?php
}

// --------------------------------------------------

elseif ($action === "delete") {
    if (($isUserAdmin || $user["role"] === "writer") && verifyCSRFToken($csrfToken, "commentdelete")) {
        $comment = queryDB(
            "SELECT pages.user_id as page_user_id
            FROM comments
            LEFT JOIN pages on pages.id=comments.page_id
            WHERE comments.id=?",
            $resourceId
        )->fetch();

        if (! $isUserAdmin && $comment["page_user_id"] !== $userId) {
            addError("Can only delete your own comment or the ones posted on the pages you created");
        }
        else {
            $success = queryDB("DELETE FROM comments WHERE id=?", $resourceId, true);

            if ($success) {
                addSuccess("Comment deleted");
            }
            else {
                addError("Error deleting comment");
            }
        }
    }

    redirect($folder, $pageName);
}

// --------------------------------------------------
// if action === "show" or other actions are fobidden for that user

else {
?>

<h2>List of all comments</h2>

<?php require_once "../app/messages.php"; ?>

<table>
    <tr>
        <th>id <?php echo printTableSortButtons("comments", "id"); ?></th>
        <th>Parent page <?php echo printTableSortButtons("pages", "title"); ?></th>
        <th>User <?php echo printTableSortButtons("users", "name"); ?></th>
        <th>Creation date <?php echo printTableSortButtons("comments", "creation_time"); ?></th>
        <th>Text (Excerpt) <?php echo printTableSortButtons("comments", "text"); ?></th>
    </tr>

<?php
    $where = "";
    if ($user["role"] === "commenter") {
        $where = "WHERE comments.user_id=:id";
    }
    elseif ($user["role"] === "writer") {
        $where = "WHERE comments.user_id=? OR pages.user_id=?";
    }

    $tables = ["comments", "pages", "users"];
    if (! in_array($orderByTable, $tables)) {
        $orderByTable = "comments";
    }

    $fields = ["id", "title", "name", "creation_time", "text"];
    if (! in_array($orderByField, $fields)) {
        $orderByField = "id";
    }

    $params = null;
    if ($where !== "") {
        $params = [$userId, $userId];
    }

    $comments = queryDB(
        "SELECT comments.*,
        users.name as user_name,
        pages.title as page_title
        FROM comments
        LEFT JOIN users ON comments.user_id=users.id
        LEFT JOIN pages ON comments.page_id=pages.id
        $where
        ORDER BY $orderByTable.$orderByField $orderDir
        LIMIT ".$adminMaxTableRows * ($pageNumber - 1).", $adminMaxTableRows",
        $params
    );

    $deleteToken = setCSRFTokens("commentdelete");

    while($comment = $comments->fetch()) {
?>

    <tr>
        <td><?php echo $comment["id"]; ?></td>
        <td><?php safeEcho($comment["page_title"])." (".$comment["page_id"].")"; ?></td>
        <td><?php safeEcho($comment["user_name"])." (".$comment["user_id"].")"; ?></td>
        <td><?php echo date("Y-m-d H:i:s", $comment["creation_time"]); ?></td>
        <td><?php safeEcho(substr($comment["text"], 0, 200)); ?></td>

        <?php if($isUserAdmin || $comment["user_id"] === $userId): ?>
        <td><a href="<?php echo buildLink($folder, "comments", "edit", $comment["id"]); ?>">Edit</a></td>
        <?php else: ?>
        <td></td>
        <?php endif; ?>

        <?php if($isUserAdmin || $user["role"] === "writer"): ?>
        <td><a href="<?php echo buildLink($folder, "comments", "delete", $comment["id"], $deleteToken); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>

<?php
    }
?>

</table>

<?php
    $table = "comments";
    require_once "pagination.php";
} // end if action = show
