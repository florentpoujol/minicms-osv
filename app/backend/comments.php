<?php

$action = $query['action'];
$userId = $user['id'];
$queryId = $query['id'] === '' ? null : $query['id'];
$isUserAdmin = $user['isAdmin'];

$title = "Comments";
require_once __dir__ . "/header.php";
?>

<h1>Comments</h1>

<?php
if ($action === "update") {
    if ($queryId === null) {
        addError("You must select a comment to update.");
        redirect("admin:comments", "read");
        return;
    }

    $commentData = [
        "id" => $queryId,
        "page_id" => 0,
        "user_id" => 0,
        "text" => "",
        "creation_time" => "",
    ];

    $dbComment = queryDB(
        "SELECT comments.*, pages.user_id as page_user_id
        FROM comments
        LEFT JOIN pages ON pages.id = comments.page_id
        WHERE comments.id = ?",
        $queryId
    )->fetch();

    if ($dbComment === false) {
        addError("Unknown comment with id $queryId.");
        redirect("admin:comments", "read");
        return;
    }

    if (
        ($user["role"] === "commenter" && $dbComment["user_id"] !== $userId) ||
        ($user["role"] === "writer" && $dbComment["user_id"] !== $userId && $dbComment["page_user_id"] !== $userId)
    ) {
        addError("You are not authorized to edit this comment.");
        redirect("admin:comments", "read");
        return;
    }

    if (isset($_POST["comment_text"])) {
        $commentData["text"] = $_POST["comment_text"];
        $commentData["creation_time"] = $dbComment["creation_time"];

        if ($user["role"] === "commenter") {
            $commentData["page_id"] = $dbComment["page_id"];
            $commentData["user_id"] = $dbComment["user_id"];
        } else {
            $commentData["page_id"] = (int)$_POST["comment_page_id"];
            $commentData["user_id"] = (int)$_POST["comment_user_id"];
        }

        if (verifyCSRFToken($_POST["csrf_token"], "commentupdate")) {
            $dataOK = true;

            $page = queryDB("SELECT id FROM pages WHERE id = ?", $commentData["page_id"])->fetch();
            if ($page === false) {
                addError("The page with id '$commentData[page_id]' does not exist.");
                $commentData["page_id"] = $dbComment["page_id"];
                $dataOK = false;
            }

            $_user = queryDB("SELECT id FROM users WHERE id = ?", $commentData["user_id"])->fetch();
            if ($_user === false) {
                addError("The user with id '$commentData[user_id]' does not exist.");
                $commentData["user_id"] = $dbComment["user_id"];
                $dataOK = false;
            }

            if ($dataOK) {
                $success = queryDB(
                    "UPDATE comments 
                    SET page_id = :page_id, user_id = :user_id, text = :text 
                    WHERE id = :id",
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
                } else {
                    addError("There was an error editing the comment.");
                }
            }
        } else {
            $commentData = $dbComment;
        }
    }
    // no post request
    else {
        $commentData = $dbComment;
    }
?>

<h2>Edit comment with id <?= $commentData["id"]; ?></h2>

<?php require_once __dir__ . "/../messages.php"; ?>

<form action="<?= buildUrl("admin:comments", "update", $commentData["id"]); ?>" method="post">
    <label>Content : <br>
        <textarea name="comment_text" cols="40" rows="5"><?php safeEcho($commentData["text"]); ?></textarea>
    </label> <br>

    <label>Parent page:
        <?php if ($user["role"] === "commenter"):
            $page = queryDB("SELECT id, title FROM pages WHERE id = ?", $commentData["page_id"])->fetch();
            echo $page["title"];
        else: ?>
            <select name="comment_page_id">
                <?php $pages = queryDB('SELECT id, title FROM pages ORDER BY title ASC'); ?>
                <?php while($page = $pages->fetch()): ?>
                    <option value="<?= $page["id"]; ?>" <?= ($commentData["page_id"] === $page["id"]) ? "selected" : null; ?>><?php safeEcho($page["title"]); ?></option>
                <?php endwhile; ?>
            </select>
        <?php endif; ?>
    </label> <br>

    <?php if ($user["role"] !== "commenter"): ?>
        <label>User:
            <select name="comment_user_id">
                <?php $users = queryDB('SELECT id, name FROM users ORDER BY name ASC'); ?>
                <?php while($user = $users->fetch()): ?>
                    <option value="<?= $user["id"]; ?>" <?= ($commentData["user_id"] === $user["id"]) ? "selected" : null; ?>><?php safeEcho($user["name"]); ?></option>
                <?php endwhile; ?>
            </select>
        </label> <br>
    <?php endif; ?>

    <?= date("Y-m-d H:i:s", $commentData["creation_time"]); ?>
    <br>

    <?php addCSRFFormField("commentupdate"); ?>

    <input type="submit" name="Edit comment">
</form>

<?php
}

// --------------------------------------------------

elseif ($action === "delete") {
    if (
            ($isUserAdmin || $user["role"] === "writer") &&
            verifyCSRFToken($query['csrftoken'], "commentdelete")
    ) {
        $comment = queryDB(
            "SELECT pages.user_id as page_user_id
            FROM comments
            LEFT JOIN pages on pages.id = comments.page_id
            WHERE comments.id = ?",
            $queryId
        )->fetch();

        if ($comment === false) {
            addError("Unknown comment with id $queryId.");
        } elseif (! $isUserAdmin && $comment["page_user_id"] !== $userId) {
            addError("You can only delete your own comment or the ones posted on the pages you created.");
        } else {
            $success = queryDB("DELETE FROM comments WHERE id = ?", $queryId, true);

            if ($success) {
                addSuccess("Comment deleted with success.");
            } else {
                addError("Error deleting comment.");
            }
        }
    } else {
        addError('Forbidden.');
    }

    redirect("admin:comments", "read");
    return;
}

// --------------------------------------------------
// if action === "show" or other actions are forbidden for that user

else {
?>

<h2>List of all comments</h2>

<?php require_once __dir__ . "/../messages.php"; ?>

<table>
    <tr>
        <th>id <?= getTableSortButtons("comments", "id"); ?></th>
        <th>Parent page <?= getTableSortButtons("pages", "title"); ?></th>
        <th>User <?= getTableSortButtons("users", "name"); ?></th>
        <th>Creation date <?= getTableSortButtons("comments", "creation_time"); ?></th>
        <th>Text (Excerpt) <?= getTableSortButtons("comments", "text"); ?></th>
    </tr>

<?php
    $where = "";
    $params = null;
    if ($user["role"] === "commenter") {
        $where = "WHERE comments.user_id = :id";
        $params = ["id" => $userId];
    } elseif ($user["role"] === "writer") {
        $where = "WHERE comments.user_id = ? OR pages.user_id = ?";
        $params = [$userId, $userId];
    }

    $tables = ["comments", "pages", "users"];
    if (! in_array($query['orderbytable'], $tables)) {
        $query['orderbytable'] = "comments";
    }

$fields = ["id", "title", "name", "creation_time", "text"];
if (! in_array($query['orderbyfield'], $fields)) {
        $query['orderbyfield'] = "id";
    }

    $comments = queryDB(
        "SELECT comments.*,
        users.name as user_name,
        pages.title as page_title,
        pages.user_id as writer_id
        FROM comments
        LEFT JOIN users ON comments.user_id = users.id
        LEFT JOIN pages ON comments.page_id = pages.id
        $where
        ORDER BY $query[orderbytable].$query[orderbyfield] $query[orderdir]
        LIMIT " . $adminMaxTableRows * ($query['page'] - 1) . ", $adminMaxTableRows",
        $params
    );

    if ($isUserAdmin || $user['role'] === 'writer') {
        $deleteToken = setCSRFToken("commentdelete");
    }

    while($comment = $comments->fetch()):
?>

    <tr>
        <td><?= $comment["id"]; ?></td>
        <td><?php safeEcho("$comment[page_title] ($comment[page_id])"); ?></td>
        <td><?php safeEcho("$comment[user_name] ($comment[user_id])"); ?></td>
        <td><?= date("Y-m-d H:i:s", $comment["creation_time"]); ?></td>
        <td><?php safeEcho(substr($comment["text"], 0, 200)); ?></td>

        <?php if($isUserAdmin || ($user["role"] === "writer" && $comment['writer_id'] === $userId) || $comment["user_id"] === $userId): ?>
            <td><a href="<?= buildUrl("admin:comments", "update", $comment["id"]); ?>">Edit</a></td>
        <?php else: ?>
            <td></td>
        <?php endif; ?>

        <?php if($isUserAdmin || ($user["role"] === "writer" && $comment['writer_id'] === $userId)): ?>
            <td><a href="<?= buildUrl("admin:comments", "delete", $comment["id"], $deleteToken); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>

<?php
    endwhile;
?>

</table>

<?php
    $table = "comments";
    require_once __dir__ . "/pagination.php";
} // end if action = show
