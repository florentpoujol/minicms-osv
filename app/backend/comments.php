<?php
declare(strict_types=1);

$action = $query['action'];
$userId = $user['id'];
$queryId = $query['id'] === '' ? null : $query['id'];
$isUserAdmin = $user['isAdmin'];

$title = "Comments";
require_once "header.php";
?>

<h1>Comments</h1>

<?php
if ($action === "update") {
    $commentData = [
        "id" => $queryId,
        "page_id" => 0,
        "user_id" => 0,
        "text" => ""
    ];

    $dbComment = queryDB(
        "SELECT comments.*, pages.user_id as pages_user_id
        FROM comments
        LEFT JOIN pages ON pages.id = comments.page_id
        WHERE comments.id = ?",
        $queryId
    )->fetch();

    if ($dbComment === false) {
        addError("Unknow comment");
        redirect("admin:comments");
    }

    if (
        ($user["role"] === "commenter" && $dbComment["user_id"] !== $userId) ||
        ($user["role"] === "writer" && $dbComment["page_user_id"] !== $userId)
    ) {
        addError("You are not authorized to edit this comment.");
        setHTTPHeader(403);
        redirect("admin:comments");
    }

    if (isset($_POST["comment_text"])) {
        $commentData["page_id"] = (int)$_POST["comment_page_id"];
        $commentData["user_id"] = (int)$_POST["comment_user_id"];
        $commentData["text"] = $_POST["comment_text"];

        if (verifyCSRFToken($_POST["csrf_token"], "commentupdate")) {
            $dataOK = true;

            $page = queryDB("SELECT id FROM pages WHERE id = ?", $commentData["page_id"])->fetch();
            if ($page === false) {
                addError("The page with id '$commentData[page_id]' does not exist.");
                $commentData["page_id"] = -1;
                $dataOK = false;
            }

            $_user = queryDB("SELECT id FROM users WHERE id = ?", $commentData["user_id"])->fetch();
            if ($_user === false) {
                addError("The user with id '$commentData[user_id]' does not exist.");
                $commentData["user_id"] = $userId; // why ?
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
                    addError("There was an error editing the comment");
                }
            }
        }
    }
    // no post request
    else {
        $commentData = $dbComment;
    }
?>

<h2>Edit comment with id <?= $commentData["id"]; ?></h2>

<?php require_once "../app/messages.php"; ?>

<form action="<?= buildUrl("admin:comment", "update", $commentData["id"]); ?>" method="post">
    <label>Content : <br>
        <textarea name="comment_text" cols="40" rows="5"><?php safeEcho($commentData["text"]); ?></textarea>
    </label> <br>

    <label>Parent page :
        <select name="comment_page_id">
            <?php $pages = queryDB('SELECT id, title FROM pages ORDER BY title ASC'); ?>
            <?php while($page = $pages->fetch()): ?>
                <option value="<?= $page["id"]; ?>" <?= ($commentData["page_id"] === $page["id"]) ? "selected" : null; ?>><?php safeEcho($page["title"]); ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>

    <label>User :
        <select name="comment_user_id">
            <?php $users = queryDB('SELECT id, name FROM users ORDER BY name ASC'); ?>
            <?php while($user = $users->fetch()): ?>
                <option value="<?= $user["id"]; ?>" <?= ($commentData["user_id"] === $user["id"]) ? "selected" : null; ?>><?php safeEcho($user["name"]); ?></option>
            <?php endwhile; ?>
        </select>
    </label> <br>

    <?= date("Y-m-d H:i:s", $commentData["creation_time"]); ?>
    <br>

    <?php addCSRFFormField("commentupdate"); ?>

    <input type="submit" name="Edit comment">
</form>

<?php
}

// --------------------------------------------------

elseif ($action === "delete") {
    if (($isUserAdmin || $user["role"] === "writer") && verifyCSRFToken($query['csrftoken'], "commentdelete")) {
        $comment = queryDB(
            "SELECT pages.user_id as page_user_id
            FROM comments
            LEFT JOIN pages on pages.id = comments.page_id
            WHERE comments.id = ?",
            $queryId
        )->fetch();

        if (! $isUserAdmin && $comment["page_user_id"] !== $userId) {
            addError("Can only delete your own comment or the ones posted on the pages you created");
        } else {
            $success = queryDB("DELETE FROM comments WHERE id = ?", $queryId, true);

            if ($success) {
                addSuccess("Comment deleted");
            } else {
                addError("Error deleting comment");
            }
        }
    } else {
        setHTTPHeader(401);
        addError('Forbidden');
    }

    redirect("admin:comments");
}

// --------------------------------------------------
// if action === "show" or other actions are forbidden for that user

else {
?>

<h2>List of all comments</h2>

<?php require_once "../app/messages.php"; ?>

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
    if ($user["role"] === "commenter") {
        $where = "WHERE comments.user_id = :id";
    } elseif ($user["role"] === "writer") {
        $where = "WHERE comments.user_id = ? OR pages.user_id = ?";
    }

    $tables = ["comments", "pages", "users"];
    if (! in_array($query['orderByTable'], $tables)) {
        $query['orderByTable'] = "comments";
    }

    $fields = ["id", "title", "name", "creation_time", "text"];
    if (! in_array($query['orderByField'], $fields)) {
        $query['orderByField'] = "id";
    }

    $params = null;
    if ($where !== "") {
        $params = [$userId, $userId];
        // when role == commenter, there is no need for 2 values, but it is accepted by PDO
    }

    $comments = queryDB(
        "SELECT comments.*,
        users.name as user_name,
        pages.title as page_title
        pages.user_id as writer_id
        FROM comments
        LEFT JOIN users ON comments.user_id = users.id
        LEFT JOIN pages ON comments.page_id = pages.id
        $where
        ORDER BY $query[orderByTable].$query[orderByField] $query[orderDir]
        LIMIT " . $adminMaxTableRows * ($query['page'] - 1) . ", $adminMaxTableRows",
        $params
    );

    if ($isUserAdmin || $user['role'] === 'writer') {
        $deleteToken = setCSRFTokens("commentdelete");
    }

    while($comment = $comments->fetch()):
?>

    <tr>
        <td><?= $comment["id"]; ?></td>
        <td><?php safeEcho("$comment[page_title] ($comment[page_id])"); ?></td>
        <td><?php safeEcho("$comment[user_name] ($comment[user_id])"); ?></td>
        <td><?= date("Y-m-d H:i:s", $comment["creation_time"]); ?></td>
        <td><?php safeEcho(substr($comment["text"], 0, 200)); ?></td>

        <?php if($isUserAdmin || $comment["user_id"] === $userId): ?>
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
    require_once "pagination.php";
} // end if action = show
