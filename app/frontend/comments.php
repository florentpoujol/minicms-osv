<?php
if ($config["allow_comments"] &&
    $pageContent["allow_comments"] === 1) {
?>

<!-- begin comments widget -->
<hr>
<div class="page-comments">
    <h3>Comment section</h3>

<?php
    if ($isLoggedIn) {
        $commentText = "";

        if (isset($_POST["comment_text"])) {
            $commentText = $_POST["comment_text"];

            $recaptchaOK = true;
            if ($useRecaptcha) {
                $recaptchaOK = verifyRecaptcha($_POST["g-recaptcha-response"]);
            }

            if ($recaptchaOK && strlen($commentText) > 10) {
                $success = queryDB(
                    "INSERT INTO comments(page_id, user_id, text, creation_time) VALUES(:page_id, :user_id, :text, :time)",
                    [
                        "page_id" => $pageContent["id"],
                        "user_id" => $userId,
                        "text" => $commentText,
                        "time" => time(),
                    ],
                    true
                );

                if ($success) {
                    $commentText = "";
                    addSuccess("Comment added successfully");
                }
                else {
                    addError("There was an error adding the comment.");
                }
            }
            elseif (! $recaptchaOK) {
                addError("Please fill the captcha before submitting the form.");
            }
            else {
                addError("Comments must have at least 10 characters");
            }
        }

        require_once "../app/messages.php";
?>

    <form action="" method="POST">
        <label>Leave a comment : <br>
            <textarea name="comment_text" placeholder="Leave a comment" required><?php echo $commentText; ?></textarea>
        </label> <br>
<?php
if ($useRecaptcha && $user["role"] === "commenter") {
    require "../app/recaptchaWidget.php";
}
?>
        <br>
        <input type="submit" value="Publish comment"> <br>
        <br>
    </form>

<?php
    }
    else {
?>
    <p>
        <a href="<?php echo buildLink(null, "login"); ?>">Login to post new comments</a>
    </p>
<?php
    }

    // display comments
    $comments = queryDB(
        "SELECT comments.*, users.name as user_name, users.is_banned as user_banned
        FROM comments
        LEFT JOIN users ON users.id=comments.user_id
        WHERE page_id=?",
        $pageContent["id"]
    );

    while ($comment = $comments->fetch()) {
        if ($comment["user_banned"] === 1) {
            continue;
        }
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