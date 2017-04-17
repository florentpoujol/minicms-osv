<?php
if (isset($db) === false) exit();

$title = "Comments";
require_once "header.php";
?>

<h1>Comments</h1>



<?php

if ($action === "edit") {
  $isPost = false;

  $commentData = [
    "id" => $resourceId,
    "page_id" => 0,
    "user_id" => 0,
    "text" => "",
    "creation_time" => 0,
  ];

  if (isset($_POST["edited_comment_id"])) {
    $commentData["id"] = (int)$_POST["edited_comment_id"];
    $isPost = true;
  }
  // else GET request

  $commentFromDB = queryDB('SELECT * FROM comments WHERE id=?', $commentData["id"])->fetch();

  if ($commentFromDB === false)
    redirect(["action" => "show", "id" => $commentData["id"], "error" => "unknowcomment"]);

  elseif ($isUserAdmin === false && $commentFromDB["user_id"] !== $currentUserId)
    redirect(["action" => "show", "id" => $commentFromDB["id"], "error" => "editforbidden"]);

  if ($isPost === false)
    $commentData = $commentFromDB;

  else {
    $commentData["page_id"] = (int)$_POST["page_id"];
    $commentData["user_id"] = (int)$_POST["user_id"];
    $commentData["text"] = $_POST["text"];

    $page = queryDB('SELECT id FROM pages WHERE id=?', $commentData["page_id"])->fetch();
    if ($page === false) {
      $errorMsg .= "The page with id '".$commentData["page_id"]."' does not exist . \n";
      $commentData["page_id"] = -1;
    }

    $user = queryDB('SELECT id FROM users WHERE id=?', $commentData["user_id"])->fetch();
    if ($user === false) {
      $errorMsg .= "The user with id '".$commentData["user_id"]."' does not exist . \n";
      $commentData["user_id"] = $currentUserId;
    }

    if ($errorMsg === "") {
      unset($commentData["creation_time"]);
      $success = queryDB("UPDATE comments SET page_id=:page_id, user_id=:user_id, text=:text  WHERE id=:id", $commentData);

      if ($success)
        $infoMsg = "Comment edited successfully.";
      else
        $errorMsg = "There was an error editing the comment";
    }
  }
?>

<h2>Edit comment with id <?php echo $commentData["id"]; ?></h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=comments&action=edit&id=<?php echo $commentData["id"]; ?>" method="post">

  <label>Content : <br>
  <textarea name="text" cols="40" rows="5"><?php echo $commentData["text"]; ?></textarea><br>
  <br>

  <label>Parent page :
    <select name="page_id">
      <?php $pages = queryDB('SELECT id, title FROM pages ORDER BY title ASC'); ?>
      <?php while($page = $pages->fetch()): ?>
      <option value="<?php echo $page["id"]; ?>" <?php echo ($commentData["page_id"] === $page["id"]) ? "selected" : null; ?>><?php echo $page["title"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>

  <label>User :
    <select name="user_id">
      <?php $users = queryDB('SELECT id, name FROM users ORDER BY name ASC'); ?>
      <?php while($user = $users->fetch()): ?>
      <option value="<?php echo $user["id"]; ?>" <?php echo ($commentData["user_id"] === $user["id"]) ? "selected" : null; ?>><?php echo $user["name"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>

  <input type="hidden" name="edited_comment_id" value="<?php echo $commentData["id"]; ?>">

  <input type="submit" name="Edit comment">

</form>


<?php
}
// ===========================================================================
// DELETE

elseif ($action === "delete") {
  $comment = queryDB('SELECT id, user_id FROM comments WHERE id = ?', $resourceId)->fetch();
  $redirect = ["action" => "show", "id" => $resourceId];

  if ($comment === false)
    $redirect["error"] = "unknowncomment";

  elseif ($isUserAdmin === false && $comment["user_id"] !== $currentUserId)
    $redirect["error"] = "mustbeadmin";

  else {
    $success = queryDB('DELETE FROM comments WHERE id = ?', $resourceId, true);

    if ($success)
      $redirect["infomsg"] = "commentdeleted";
    else
      $redirect["errormsg"] = "deletecomment";
  }

  redirect($redirect);
}


// ===========================================================================
// ALL ELSE

// if action === "show" or other actions are fobidden for that user
else {
  switch($errorMsg) {
    case "unknowcomment":
      $errorMsg = "Unknow comment.";
      break;
    case "mustbeadmin":
      $errorMsg = "You must be an admin to do that.";
      break;
    case "deletecomment":
      $errorMsg = "There has been an error while deleting comment with id $resourceId"; // same error when we try to delete a user that is unknow
      break;
  }

  switch($infoMsg) {
    case "commentdeleted":
      $infoMsg = "Comment with id $resourceId has been deleted.";
      break;

  }

  if ($orderByTable === "")
    $orderByTable = "comments";
?>

<h2>List of all comments</h2>

<?php require_once "messages-template.php"; ?>

<table>
  <tr>
    <th>id <?php echo printTableSortButtons("comments", "id"); ?></th>
    <th>Parent page <?php echo printTableSortButtons("pages", "title"); ?></th>
    <th>User <?php echo printTableSortButtons("users", "name"); ?></th>
    <th>Creation date <?php echo printTableSortButtons("comments", "creation_date"); ?></th>
    <th>Text (Excerpt) <?php echo printTableSortButtons("comments", "text"); ?></th>
  </tr>

<?php
  $comments = queryDB(
    "SELECT comments.*, 
    users.id as user_id, 
    users.name as user_name, 
    pages.id as page_id, 
    pages.title as page_title 
    FROM comments 
    LEFT JOIN users ON comments.user_id=users.id 
    LEFT JOIN pages ON comments.page_id=pages.id 
    ORDER BY $orderByTable.$orderByField $orderDir"
  );

  while($comment = $comments->fetch()) {
?>
  <tr>
    <td><?php echo $comment["id"]; ?></td>
    <td><?php echo $comment["page_title"]." (".$comment["page_id"].")"; ?></td>
    <td><?php echo $comment["user_name"]." (".$comment["user_id"].")"; ?></td>
    <td><?php echo date("Y-m-d H:i:s", $comment["creation_time"]); ?></td>
    <td><?php echo htmlspecialchars(substr($comment["text"], 0, 200)); ?></td>

    <?php if($isUserAdmin || $comment["user_id"] === $currentUserId): ?>
    <td><a href="?section=comments&action=edit&id=<?php echo $comment["id"]; ?>">Edit</a></td>
    <?php endif; ?>

    <?php if($isUserAdmin || $comment["user_id"] !== $currentUserId): ?>
    <td><a href="?section=comments&action=delete&id=<?php echo $comment["id"]; ?>">Delete</a></td>
    <?php endif; ?>
  </tr>
<?php
  } // end while users from DB
?>
</table>
<?php
} // end if action = show
?>
