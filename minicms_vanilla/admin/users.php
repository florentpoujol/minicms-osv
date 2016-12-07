<?php if (isset($db) === false) exit(); ?>

<h1>Users</h1>

<?php
// ===========================================================================
// ADD

if ($action === "add") {
  if ($isUserAdmin === false)
    redirect(["action" => "show", "error" => "mustbeadmin"]);

  $name = "";
  $email = "";

  if (isset($_POST["add_user"])) {
    // the form has been submitted, check the format of the fields
    $name = $_POST["name"];
    // $email = $_POST["email"];
    $role = $_POST["role"];

    // check that the name doesn't already exist
    $query = $db->prepare('SELECT id FROM users WHERE name = :name');
    $query->execute(["name" => $name]);
    $user = $query->fetch();

    if ($user !== false)
      $errorMsg .= "A user with the name '".htmlspecialchars($name)."' already exists <br>";

    // check that the password is OK (correct format + equal to confirmation)
    $password = $_POST["password"];
    $passwordConfirm = $_POST["password_confirm"];

    if ($password !== $passwordConfirm)
      $errorMsg .= "The password are not the same ! <br>";

    $patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
    for ($i=0; $i<count($patterns); $i++) {
      if (!preg_match($patterns[$i], $password)) {
        $errorMsg .= "The password must have at least one lowercase letter, one uppercase letter and one number ! <br>";
        break;
      }
    }

    if (strlen($password) < 3)
      $errorMsg .= "The password must be at least 3 characters long ! <br>";

    if ($errorMsg === "") {
      // OK no error, let's add the user
      $query = $db->prepare('INSERT INTO users(name, password_hash, role, creation_date) VALUES(:name, :password_hash, :role, :creation_date)');
      $success = $query->execute([
        "name" => $name,
        "password_hash" => password_hash($password, PASSWORD_DEFAULT),
        "role" => $role,
        "creation_date" => date("Y-m-d")
      ]);

      if ($success) // redirect("users", "show", $db->lastInsertId(), "&infomsg=useradded");
        redirect(["action" => "show", "id" => $db->lastInsertId(), "info" => "useradded"]);
      else
        $errorMsg .= "There was an error regsitering the user";
    }
    // else if there is error, we just fallback through the form
    // with the error being displayed
    // and the name field being prefilled
  }

?>

<h2>Add a new user</h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=users&action=add" method="post">
  <label>Name : <input type="text" name="name" required value ="<?php echo htmlspecialchars($name); ?>"></label> <br>
  <!-- <label>Email : <input type="email" name="email" required></label> <br> -->
  <label>Password : <input type="password" name="password" required pattern=".{3,}" title="Min 2 chars"></label> <br>
  <label>Confirm password : <input type="password" name="password_confirm" required pattern=".{3,}" title="Min 2 chars"></label> <br>
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

  if (isset($_POST["edit_user_id"])) {
    $editedUserId = (int)$_POST["edit_user_id"];

    if ($isUserAdmin === false && $editedUserId !== $currentUserId) { // a writer is trying to edit another user
      // redirect("users", "show", null, "&errormsg=mustbeadmin");
      redirect(["action" => "show", "error" => "mustbeadmin"]);
    }

    // OK let's proceed
    $name = $_POST["name"];
    // $email = $_POST["email"];
    $role = "writer";
    if ($isUserAdmin) // prevent writers to change their role as admin by manipulating the HTML of the form
      $role = $_POST["role"];

    $password = $_POST["password"];
    $passwordConfirm = $_POST["password_confirm"];

    // when the password field is filled, the password must be changed otherwise, the password fields can be empty
    $passwordLength = strlen($password);
    if ($passwordLength > 0) {
      // check that the password is OK (correct format + equal to confirmation)
      $password = $_POST["password"];
      $passwordConfirm = $_POST["password_confirm"];

      if ($password != $passwordConfirm)
        $errorMsg .= "The password are not the same ! <br>";

      /*$patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
      for ($i=0; $i<count($patterns); $i++) {
        if (!preg_match($patterns[$i], $password)) {
          $errorMsg .= "The password has a wrong format ! <br>";
          break;
        }
      }*/

      if ($passwordLength < 2)
        $errorMsg .= "The password is less than X character long ! <br>";
    }

    // used to fill the fields in the form
    $editedUser = ["id" => $editedUserId, "name" => $name, "email" => "", "role" => $role];

    if ($errorMsg === "") {
      // OK no error, let's edit the user

      $strQuery = "UPDATE users SET name=:name, role=:role";
      if ($passwordLength > 0) $strQuery .= ", password_hash=:password_hash";
      $strQuery .= " WHERE id=:id";
      $query = $db->prepare($strQuery);

      $data = [
        "id" => $editedUserId,
        "name" => $name,
        "role" => $role
      ];
      if ($passwordLength > 0)
        $data["password_hash"] = password_hash($password, PASSWORD_DEFAULT);

      $success = $query->execute($data);

      if ($success)
        $infoMsg = "User editted with success";
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
        $editedUser = ["id" => "0", "name" => "", "email" => "", "role" => ""];
      }
    }
  }
?>

<h2>Edit user with id <?php echo $editedUser["id"]; ?></h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=users&action=edit" method="post">
  <label>Name : <input type="text" name="name" required value ="<?php echo htmlspecialchars($editedUser["name"]); ?>"></label> <br>
  <!-- <label>Email : <input type="email" name="email" required value="<?php echo htmlspecialchars($editedUser["email"]); ?>"></label> <br> -->
  <label>Password : <input type="password" name="password" pattern=".{2,}" title="Min 2 chars"></label> <br>
  <label>Confirm password : <input type="password" name="password_confirm" pattern=".{2,}" title="Min 2 chars"></label> <br>
  <label>Role :
  <?php if($isUserAdmin): ?>
  <select name="role">
    <option value="writer" <?php echo ($editedUser["role"] === "writer")? "selected" : NULL; ?>>Writer</option>
    <option value="admin" <?php echo ($editedUser["role"] === "admin")? "selected" : NULL; ?>>Admin</option>
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

  $info = "";
  $error = "";

  if ($resourceId === $currentUserId)
    $error = "cannot-delete-own-user";

  else {
    $query = $db->prepare("DELETE FROM users WHERE id=:id");
    $success = $query->execute(["id" => $resourceId]);

    if ($success) {
      // update the user_id column of all pages created by that deleted user to the current user
      $query = $db->prepare('UPDATE pages SET user_id=:new_id WHERE user_id=:old_id');
      $query->execute(["new_id" => $currentUserId, "old_id" => $resourceId]);

      $info = "user-deleted";
    }
    else
      $error = "error-user-delete";
  }

  redirect(["action" => "show", "id" => $resourceId, "info" => $info, "error" => $error]);
}



// ===========================================================================
// ALL ELSE

// if action === "show" or other actions are fobidden for that user
else {

  switch($errorMsg) {
    case "mustbeadmin":
      $errorMsg = "You must be an admin to do that !";
      break;
    case "cannot-delete-own-user":
      $errorMsg = "You cannot delete your own user !";
      break;
    case "error-user-delete":
      $errorMsg = "There has been an error while deleting user with id $resourceId";
      break;
    // default:
    //   $errorMsg = "";
  }

  switch($infoMsg) {
    case "user-deleted":
      $infoMsg = "User with id $resourceId has been deleted.";
      break;
    case "useradded":
      $infoMsg = "User with id $resourceId has been successfully added.";
      break;
    // default:
    //   $infoMsg = "";
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
