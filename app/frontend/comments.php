<?php
if (! isset($db)) {
    exit();
}

if ($page["allow_comments"] === 1) {
?>

<!-- begin comments widget -->
<hr>
<div class="page-comments">
    <h3>Comment section</h3>

<?php
    // display form when user is logged in
    if (isset($user)) {
        $contentText = "";
        if (isset($_POST["comment_text"])) {
            $contentText = $_POST["comment_text"];

            $success = queryDB(
                "INSERT INTO comments(page_id, user_id, text, creation_time) VALUES(:page_id, :user_id, :text, :time)",
                [
                    "page_id" => $page["id"],
                    "user_id" => $user["id"],
                    "text" => $contentText,
                    "time" => time(),
                ],
                true
            );

            if ($success) {
                $infoMsg = "Comment addedd successfully";
            } else {
                $errorMsg = "There was an error adding the comment.";
            }
        }

        require_once "admin/messages-template.php";
?>

    <form action="" method="POST">
        <label>Leave a comment : <br>
            <textarea name="comment_text" placeholder="Leave a comment"><?php echo $contentText; ?></textarea>
        </label> <br>
        <input type="submit" value="Publish comment"> <br>
        <br>
    </form>

<?php
    }

    // display comments
    $comments = queryDB(
        "SELECT comments.*, users.name as user_name
        FROM comments
        LEFT JOIN users ON users.id=comments.user_id
        WHERE page_id=?",
        $page["id"]
    );

    while ($comment = $comments->fetch()) {
?>

    <article class="comment">
        <header>Posted on <?php echo date("Y-m-d H:i", $comment["creation_time"]); ?> by <?php echo $comment["user_name"]; ?>.</header>

        <p><?php echo htmlspecialchars($comment["text"]); ?></p>
    </article>

<?php
    }
?>

    </div>
    <!-- /end comments widget -->

<?php
}