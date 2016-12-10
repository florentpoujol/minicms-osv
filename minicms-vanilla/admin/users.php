<?php
if (isset($db) === false) exit();

$title = "Users";
require_once "header.php";
?>

<h1>Users</h1>

<?php
$namePattern = "[a-zA-Z0-9_-]{4,}";
$emailPattern = "^[a-zA-Z0-9_\.-]{1,}@[a-zA-Z0-9-_\.]{4,}$";
$minPasswordLength = 10;

// ===========================================================================
// ADD

if ($action === "add") {
  if ($isUserAdmin === false)
    redirect(["action" => "show", "error" => "mustbeadmin"]);

  $addedUser = [
    "name" => "",
    "email" => "",
    "role" => "",
  ];

  if (isset($_POST["add_user"])) {
    // the form has been submitted
    $addedUser["name"] = $_POST["name"];
    $addedUser["email"] = $_POST["email"];
    $addedUser["role"] = $_POST["role"];


    // check for name format
    if (checkPatterns("/$namePattern/", $addedUser["name"]) === false)
      $errorMsg .= "The user name has the wrong format. Minimum four letters, numbers, hyphens or underscores. <br>";

    // check that the name doesn't already exist
    $query = $db->prepare('SELECT id FROM users WHERE name = :name');
    $query->execute(["name" => $addedUser["name"]]);
    $user = $query->fetch();

    if ($user !== false)
      $errorMsg .= "A user with the name '".htmlspecialchars($addedUser["name"])."' already exists <br>";


    // check for email format
    if (checkPatterns("/$emailPattern/", $addedUser["email"]) === false)
      $errorMsg .= "The email has the wrong format. <br>";


    // check for password format (+ equal to confirmation)
    $password = $_POST["password"];

    $patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
    if (checkPatterns($patterns, $password) === false || strlen($password) < $minPasswordLength)
      $errorMsg .= "The password must have at least one lowercase letter, one uppercase letter and one number. <br>";

    if ($password !== $_POST["password_confirm"])
      $errorMsg .= "The password confirmation does not match the password. <br>";


    // check for role
    if ($addedUser["role"] !== "writer" && $addedUser["role"] !== "admin")
      $errorMsg .= "Role must be 'writer' or 'admin'. <br>";


    if ($errorMsg === "") {
      // OK no error, let's add the user
      $query = $db->prepare('INSERT INTO users(name, email, password_hash, role, creation_date) VALUES(:name, :password_hash, :role, :creation_date)');
      $success = $query->execute([
        "name" => $addedUser["name"],
        "email" => $addedUser["email"],
        "password_hash" => password_hash($password, PASSWORD_DEFAULT),
        "role" => $addedUser["role"],
        "creation_date" => date("Y-m-d")
      ]);

      if ($success)
        redirect(["action" => "show", "id" => $db->lastInsertId(), "info" => "useradded"]);
      else
        $errorMsg .= "There was an error regsitering the user. <br>";
    }
    // else if there is error, we just fallback through the form
    // with the error being displayed and the fields being prefilled
  }

?>

<h2>Add a new user</h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=users&action=add" method="post">
  <label>Name : <input type="text" name="name" required pattern="<?php echo $namePattern; ?>" placeholder="Name" value="<?php echo htmlspecialchars($addedUser["name"]); ?>"></label> <?php createTooltip("Minimum four letters, numbers, hyphens or underscores"); ?> <br>
  <label>Email : <input type="email" name="email" required pattern="<?php echo $emailPattern; ?>" placeholder="Email adress" value="<?php echo htmlspecialchars($addedUser["email"]); ?>"></label> <br>

  <label>Password : <input type="password" name="password" required pattern=".{<?php echo $minPasswordLength; ?>,}" placeholder="Password"></label> <?php createTooltip("Minimum $minPasswordLength of any characters but minimum one lowercase and uppercase letter and one number."); ?> <br>
  <label>Confirm password : <input type="password" name="password_confirm" required pattern=".{<?php echo $minPasswordLength; ?>,}" placeholder="Password confirmation"></label> <br>

  <label>Role :
  <select name="role">
    <option value="writer">Writer</option>
    <option value="admin">Admin</option>
  </select>
  </label> <br>

  <input type="submit" name="add_user" value="Add">
</form>

<?php
} // end if action = add


// ===========================================================================
// EDIT

elseif ($action === "edit") {
  if ($resourceId === 0)
    $resourceId = $currentUserId;

  if($isUserAdmin === false && $currentUserId !== $resourceId) {
    $resourceId = $currentUserId; // writers can only edit themselve
    $errorMsg = "You can only edit yourself !";
  }

  $editedUser = ["id" => "0", "name" => "", "email" => "", "role" => ""];

  if (isset($_POST["edit_user_id"])) {
    $editedUser["id"] = (int)$_POST["edit_user_id"];

    if ($isUserAdmin === false && $editedUser["id"] !== $currentUserId) // a writer is trying to edit another user
      redirect(["action" => "show", "error" => "mustbeadmin"]);


    // check that the user exists
    $query = $db->prepare('SELECT id FROM users WHERE id = :id');
    $query->execute(["id" => $editedUser["id"]]);
    $user = $query->fetch();

    if ($user === false)
      redirect([ "action" => "show", "id" => $resourceId, "error" => "unknowuser"]);


    // OK let's proceed
    $editedUser["name"] = $_POST["name"];
    $editedUser["email"] = $_POST["email"];
    $editedUser["role"] = "writer";
    if ($isUserAdmin) // prevent writers to change their role as admin by manipulating the HTML of the form
      $editedUser["role"] = $_POST["role"];


    // check for name format
    if (checkPatterns("/$namePattern/", $editedUser["name"]) === false)
      $errorMsg .= "The user name has the wrong format. Minimum four letters, numbers, hyphens or underscores. <br>";

    // check that the name doesn't already exist
    $query = $db->prepare('SELECT id FROM users WHERE name = :name AND id <> :own_id');
    $query->execute(["name" => $editedUser["name"], "own_id" => $editedUser["id"]]);
    $user = $query->fetch();

    if ($user !== false)
      $errorMsg .= "A user with the name '".htmlspecialchars($editedUser["name"])."' already exists. <br>";


    // check for email format
    if (checkPatterns("/$emailPattern/", $editedUser["email"]) === false)
      $errorMsg .= "The email has the wrong format. <br>";


    // check for password format (+ equal to confirmation)
    // but only if it needs to be changed
    $password = $_POST["password"];
    $passwordLength = strlen($password);

    if ($passwordLength > 0) {
      $patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
      if (checkPatterns($patterns, $password) === false || $passwordLength < $minPasswordLength)
        $errorMsg .= "The password must have at least one lowercase letter, one uppercase letter and one number. <br>";

      if ($password !== $_POST["password_confirm"])
        $errorMsg .= "The password confirmation does not match the password. <br>";
    }


    // check for role
    if ($editedUser["role"] !== "writer" && $editedUser["role"] !== "admin")
      $errorMsg .= "Role must be 'writer' or 'admin'. <br>";


    if ($errorMsg === "") {
      // OK no error, let's edit the user

      $strQuery = "UPDATE users SET name=:name, email=:email, role=:role";
      if ($passwordLength > 0) $strQuery .= ", password_hash=:password_hash";
      $strQuery .= " WHERE id=:id";
      $query = $db->prepare($strQuery);

      $dbData = $editedUser;
      if ($passwordLength > 0)
        $dbData["password_hash"] = password_hash($password, PASSWORD_DEFAULT);

      $success = $query->execute($dbData);

      if ($success)
        redirect(["action" => "show", "id" => $editedUser["id"], "info" => "useredited"]);
      else
        $errorMsg = "There was an error editting the user";
    }
  }

  else {
    // no POST request
    // just fill the form with the logged in user's data or the specified user's $resourceId
    $editedUser = $currentUser;

    if ($resourceId != 0 && $currentUserId != $resourceId) {
      // when user is admin and edit another user, fetch it from DB

      $query = $db->prepare('SELECT * FROM users WHERE id = :id');
      $query->execute(['id' => $resourceId]);
      $user = $query->fetch();

      if ($user !== false)
        $editedUser = $user;
      else {
        $errorMsg = "No user with id $resourceId was found !";
      }
    }
  }
?>

<h2>Edit user with id <?php echo $editedUser["id"]; ?></h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=users&action=edit" method="post">
  <label>Name : <input type="text" name="name" required pattern="<?php echo $namePattern; ?>" placeholder="Name" value="<?php echo htmlspecialchars($editedUser["name"]); ?>"></label> <?php createTooltip("Minimum four letters, numbers, hyphens or underscores"); ?> <br>
  <label>Email : <input type="email" name="email" required pattern="<?php echo $emailPattern; ?>" placeholder="Email adress" value="<?php echo htmlspecialchars($editedUser["email"]); ?>"></label> <br>

  <label>Password : <input type="password" name="password" pattern=".{<?php echo $minPasswordLength; ?>,}" placeholder="Password" ></label> <?php createTooltip("Minimum $minPasswordLength of any characters but minimum one lowercase and uppercase letter and one number."); ?> <br>
  <label>Confirm password : <input type="password" name="password_confirm" pattern=".{<?php echo $minPasswordLength; ?>,}" placeholder="Password confirmation" ></label> <br>

  <label>Role :
  <?php if($isUserAdmin): ?>
  <select name="role">
    <option value="writer" <?php echo ($editedUser["role"] === "writer")? "selected" : null; ?>>Writer</option>
    <option value="admin" <?php echo ($editedUser["role"] === "admin")? "selected" : null; ?>>Admin</option>
  </select>
  <?php else: ?>
  Writer
  <?php endif; ?>
  </label> <br>

  <input type="hidden" name="edit_user_id" value="<?php echo $editedUser["id"]; ?>">
  <input type="submit" value="Edit">
</form>

<?php
}



// ===========================================================================
// DELETE

elseif ($action === "delete") {
  if ($isUserAdmin === false)
    redirect(["action" => "show", "error" => "mustbeadmin"]);

  if ($resourceId === $currentUserId)
    redirect(["action" => "show", "error" => "cannotdeleteownuser"]);


  $query = $db->prepare("DELETE FROM users WHERE id=:id");
  $success = $query->execute(["id" => $resourceId]);

  if ($success) {
    // update the user_id column of all pages created by that deleted user to the current user
    $query = $db->prepare('UPDATE pages SET user_id=:new_id WHERE user_id=:old_id');
    $query->execute(["new_id" => $currentUserId, "old_id" => $resourceId]);

    // update the user_id column of all medias created by that deleted user to the current user
    $query = $db->prepare('UPDATE medias SET user_id=:new_id WHERE user_id=:old_id');
    $query->execute(["new_id" => $currentUserId, "old_id" => $resourceId]);

    redirect(["action" => "show", "id" => $resourceId, "info" => "userdeleted"]);
  }
  else
    redirect(["action" => "show", "id" => $resourceId, "error" => "deleteuser"]);
}



// ===========================================================================
// ALL ELSE

// if action === "show" or other actions are fobidden for that user
else {
  switch($errorMsg) {
    case "mustbeadmin":
      $errorMsg = "You must be an admin to do that.";
      break;
    case "cannotdeleteownuser":
      $errorMsg = "You cannot delete your own user.";
      break;
    case "deleteuser":
      $errorMsg = "There has been an error while deleting user with id $resourceId"; // same error when we try to delete a user that is unknow
      break;
  }

  switch($infoMsg) {
    case "useradded":
      $infoMsg = "User with id $resourceId has been added.";
      break;
    case "userdeleted":
      $infoMsg = "User with id $resourceId has been deleted.";
      break;
  }
?>

<h2>List of all users</h2>

<?php require_once "messages-template.php"; ?>

<?php if ($isUserAdmin): ?>
<div>
  <a href="?section=users&action=add">Add a user</a> <br>
</div>
<?php endif; ?>

<table>
  <tr>
    <th>id</th>
    <th>name</th>
    <th>email</th>
    <th>role</th>
    <th>creation date</th>
  </tr>

<?php
  $query = $db->query('SELECT * FROM users ORDER BY id');

  while($user = $query->fetch()) {
?>
  <tr>
    <td><?php echo $user["id"]; ?></td>
    <td><?php echo $user["name"]; ?></td>
    <td><?php echo $user["email"]; ?></td>
    <td><?php echo $user["role"]; ?></td>
    <td><?php echo $user["creation_date"]; ?></td>

    <?php if($isUserAdmin || $user["id"] === $currentUserId): ?>
    <td><a href="?section=users&action=edit&id=<?php echo $user["id"]; ?>">Edit</a></td>
    <?php endif; ?>

    <?php if($isUserAdmin && $user["id"] !== $currentUserId): /* even admins can't delete their own user */ ?>
    <td><a href="?section=users&action=delete&id=<?php echo $user["id"]; ?>">Delete</a></td>
    <?php endif; ?>
  </tr>
<?php
  } // end while users from DB
?>
</table>
<?php
} // end if action = show
?>
