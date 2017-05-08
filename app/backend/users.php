<?php
if ($user["role"] === "commenter" && $action !== "edit") {
    redirect($folder, "users", "edit", $userId);
}

$title = "Users";
require_once "header.php";
?>

<h1>Users</h1>

<?php
if ($action === "add" || $action === "edit") {
    if (($action === "edit" && $resourceId === null) || (! $isUserAdmin && $resourceId !== $userId)) {
        redirect($folder, "users", "edit", $userId);
    }

    $userData = [
        "id" => "",
        "name" => "",
        "email" => "",
        "password" => "",
        "password_confirm" => "",
        "role" => ""
    ];

    if (isset($_POST["user_name"])) {
        $userData["id"] = $resourceId;
        $userData["name"] = $_POST["user_name"];
        $userData["email"] = $_POST["user_email"];
        $userData["password"] = $_POST["user_password"];
        $userData["password_confirm"] = $_POST["user_password_confirm"];
        $userData["role"] = "commenter";
        if ($isUserAdmin) {
            $userData["role"] = $_POST["user_role"];
        }

        if ($action === "add" && checkNewUserData($userData)) {
            $success = queryDB(
                "INSERT INTO users(name, email, password_hash, role, creation_date) VALUES(:name, :email, :password_hash, :role, :creation_date)",
                [
                    "name" => $userData["name"],
                    "email" => $userData["email"],
                    "password_hash" => password_hash($userData["password"], PASSWORD_DEFAULT),
                    "role" => $userData["role"],
                    "creation_date" => date("Y-m-d")
                ]
            );

            if ($success) {
                addSucccess("User added successfully");
                redirect($folder, "users", $db->lastInsertId());
            }
            else {
                addError("There was an error regsitering the user.");
            }
        }

        elseif ($action === "edit" && checkUserData($userData)) {
            $strQuery = "UPDATE users SET name=:name, email=:email, role=:role";

            if ($userData["password"] !== "") {
                $strQuery .= ", password_hash=:hash";
                $userData["hash"] = password_hash($userData["password"], PASSWORD_DEFAULT);
            }

            unset($userData["password"]);
            unset($userData["password_confirm"]);
            $success = queryDB($strQuery." WHERE id=:id", $userData);

            if ($success) {
                addSuccess("Modification saved");
            }
            else {
                addError("There was an error editting the user");
            }
        }
    }

    // no POST data
    elseif ($action === "edit") {
        $user = queryDB("SELECT * FROM users where id=?", $resourceId)->fetch();

        if (is_array($user)) {
            $userData = $user;
        }
        else {
            addError("Unknow user");
            redirect($folder, "users");
        }
    }

    $formTarget = buildLink($folder, "users", $action);
    if ($action === "edit") {
        $formTarget = buildLink($folder, "users", $action, $resourceId);
    }
?>

<?php if ($action ==="add"): ?>
<h2>Add a new user</h2>
<?php else: ?>
<h2>Edit user with id <?php echo $resourceId; ?></h2>
<?php endif; ?>

<?php require_once "../app/messages.php"; ?>

<form action="<?php echo $formTarget; ?>" method="post">
    <label>Name : <input type="text" name="user_name" required placeholder="Name" value="<?php echo $userData["name"]; ?>"></label> <?php createTooltip("Minimum four letters, numbers, hyphens or underscores"); ?> <br>
    <label>Email : <input type="email" name="user_email" required placeholder="Email adress" value="<?php echo $userData["email"]; ?>"></label> <br>

    <label>Password : <input type="password" name="user_password" placeholder="Password" ></label> <?php createTooltip("Minimum 3 of any characters but minimum one lowercase and uppercase letter and one number."); ?> <br>
    <label>Confirm password : <input type="password" name="user_password_confirm" placeholder="Password confirmation" ></label> <br>

    <label>Role :
        <?php if($isUserAdmin): ?>
        <select name="user_role">
            <option value="commenter" <?php echo ($userData["role"] === "commenter")? "selected" : null; ?>>Commenter</option>
            <option value="writer" <?php echo ($userData["role"] === "writer")? "selected" : null; ?>>Writer</option>
            <option value="admin" <?php echo ($userData["role"] === "admin")? "selected" : null; ?>>Admin</option>
        </select>
        <?php else: ?>
        <?php echo $user["role"]; ?>
        <?php endif; ?>
    </label> <br>

    <input type="submit" value="Edit">
</form>

<?php
}

// --------------------------------------------------

elseif ($action === "delete") {
    if (! $isUserAdmin) {
        addError("No right to do that");
    }
    elseif ($resourceId === $userId) {
        addError("Can't delete your own user");
    }
    else {
        $success = queryDB("DELETE FROM users WHERE id=?", $resourceId, true);
        // note that is the user id doesn't exist, it returns success too

        if ($success) {
            // update the user_id column of all pages created by that deleted user to the current user
            queryDB(
                "UPDATE pages SET user_id=:new_id WHERE user_id=:old_id",
                ["new_id" => $userId, "old_id" => $resourceId]
            );

            // update the user_id column of all medias created by that deleted user to the current user
            queryDB(
                "UPDATE medias SET user_id=:new_id WHERE user_id=:old_id",
                ["new_id" => $userId, "old_id" => $resourceId]
            );

            addSuccess("User with id $resourceId has been successfully deleted.");
        }
        else {
            addError("There was an error deleting the user with id $resourceId");
        }
    }

    redirect($folder, "users");
}

// --------------------------------------------------
// if action === "show" or other actions are fobidden for that user

else {
?>

<h2>List of all users</h2>

<?php require_once "../app/messages.php"; ?>

<?php if ($isUserAdmin): ?>
<div>
    <a href="<?php echo buildLink("admin", "users", "add") ?>">Add a user</a>
</div>

<br>
<?php endif; ?>

<table>
    <tr>
        <th>id <?php echo printTableSortButtons("users", "id"); ?></th>
        <th>name <?php echo printTableSortButtons("users", "name"); ?></th>
        <th>email <?php echo printTableSortButtons("users", "email"); ?></th>
        <th>role <?php echo printTableSortButtons("users", "role"); ?></th>
        <th>creation date <?php echo printTableSortButtons("users", "creation_date"); ?></th>
    </tr>

<?php
    $fields = ["id", "name", "email", "role", "creation_date"];
    if (! in_array($orderByField, $fields)) {
        $orderByField = "id";
    }

    $query = queryDB("SELECT * FROM users ORDER BY $orderByField $orderDir");
    while ($_user = $query->fetch()) {
?>
    <tr>
        <td><?php echo $_user["id"]; ?></td>
        <td><?php echo $_user["name"]; ?></td>
        <td><?php echo $_user["email"]; ?></td>
        <td><?php echo $_user["role"]; ?></td>
        <td><?php echo $_user["creation_date"]; ?></td>

        <?php if($isUserAdmin || $_user["id"] === $userId): ?>
        <td><a href="<?php echo buildLink($folder, "users", "edit", $_user["id"]); ?>">Edit</a></td>
        <?php endif; ?>

        <?php if($isUserAdmin && $_user["id"] !== $userId): /* even admins can't delete their own user */ ?>
        <td><a href="<?php echo buildLink($folder, "users", "delete", $_user["id"]); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>
<?php
    } // end while users from DB
?>
</table>
<?php
} // end if action = show
