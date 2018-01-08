<?php

if ($config["allow_comments"] &&
    $pageContent["allow_comments"] === 1) {
?>

<!-- begin comments widget -->
<hr>
<div class="page-comments">
    <h3>Comment section</h3>

<?php
    if ($user['isLoggedIn']) {
        $commentText = "";

        if (isset($_POST["comment_text"]) && verifyCSRFToken($_POST["csrf_token"], "commentcreate")) {
            $commentText = $_POST["comment_text"];

            $recaptchaOK = true;
            if ($config['useRecaptcha'] && $user['role'] === 'commenter') {
                // do not show recaptcha for writers and admins
                $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
            }

            if ($recaptchaOK && strlen($commentText) > 10) {
                $success = queryDB(
                    "INSERT INTO comments(page_id, user_id, text, creation_time) VALUES(:page_id, :user_id, :text, :time)",
                    [
                        "page_id" => $pageContent["id"],
                        "user_id" => $user['id'],
                        "text" => $commentText,
                        "time" => time(),
                    ],
                    true
                );

                if ($success) {
                    $commentText = "";
                    addSuccess("Comment added successfully.");
                    redirect($section, null, $pageContent["id"]);
                    return;
                } else {
                    addError("There was an error adding the comment.");
                }
            } elseif (! $recaptchaOK) {
                addError("Please fill the captcha before submitting the form.");
            } else {
                addError("Comments must have at least 10 characters");
            }
        }

        require_once __dir__ . "/../messages.php";
?>

    <form action="" method="POST">
        <label>Leave a comment: <br>
            <textarea name="comment_text" placeholder="Leave a comment" required><?php safeEcho($commentText); ?></textarea>
        </label> <br>
<?php
if ($config['useRecaptcha'] && $user['role'] === 'commenter') {
    // do not show recaptcha for writers and admins
    require __dir__ . "/../recaptchaWidget.php";
}
?>
        <br>
        <?php addCSRFFormField("commentcreate"); ?>
        <input type="submit" value="Publish comment"> <br>
        <br>
    </form>

<?php
    }
    else {
?>
    <p>
        <a href="<?= buildUrl('login'); ?>">Login to post a new comment</a>
    </p>
<?php
    }

    // display comments
    $comments = queryDB(
        "SELECT comments.*, users.name as user_name, users.is_banned as user_banned
        FROM comments
        LEFT JOIN users ON users.id = comments.user_id 
        WHERE page_id = ? AND users.is_banned <> 1
        ORDER BY creation_time DESC",
        $pageContent["id"]
    );

    while ($comment = $comments->fetch()) {
?>

    <article class="comment">
        <header>Posted on <?= date("Y-m-d H:i", $comment["creation_time"]); ?> by <?= $comment["user_name"]; ?>.</header>

        <p><?php safeEcho($comment["text"]); ?></p>
    </article>

<?php
    }
?>
    </div>
    <!-- /end comments widget -->
<?php
}