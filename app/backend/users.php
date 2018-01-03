<?php

$action = $query['action'];
$userId = $user['id'];
$queryUserId = $query['id'] === '' ? $userId : $query['id'];
$isUserAdmin = $user['isAdmin'];

if (
    ($user["role"] === "commenter" && $action !== "update") ||
    ($user["role"] === "writer" && ($action === "create" || $action === "delete")) ||
    ($action === "update" && $query['id'] === '') ||
    (! $isUserAdmin && $queryUserId !== $userId)
) {
    // commenter trying to do something else
    // edit action with id in the url
    // or non-admin trying to edit someone else
    setHTTPResponseCode(403);
    redirect("admin:users", "update", $userId);
    return;
}

$title = "Users";
require_once "header.php";
?>

<h1>Users</h1>

<?php
if ($action === "create" || $action === "update") {
    $userData = [
        "id" => "",
        "name" => "",
        "email" => "",
        "password" => "",
        "password_confirm" => "",
        "role" => ""
    ];

    if (isset($_POST["user_name"])) {
        $userData["id"] = $queryUserId;
        $userData["name"] = $_POST["user_name"];
        $userData["email"] = $_POST["user_email"];
        $userData["password"] = $_POST["user_password"];
        $userData["password_confirm"] = $_POST["user_password_confirm"];
        $userData["role"] = "commenter";
        if ($isUserAdmin) {
            $userData["role"] = $_POST["user_role"];
        }

        if (verifyCSRFToken($_POST["csrf_token"], "user$action")) {

            if ($action === "create" && checkNewUserData($userData)) {
                $success = queryDB(
                    "INSERT INTO users(name, email, password_hash, role, creation_date) VALUES(:name, :email, :password_hash, :role, :creation_date)",
                    [
                        "name"          => $userData["name"],
                        "email"         => $userData["email"],
                        "password_hash" => password_hash($userData["password"], PASSWORD_DEFAULT),
                        "role"          => $userData["role"],
                        "creation_date" => date("Y-m-d")
                    ]
                );

                if ($success) {
                    addSuccess('User added successfully');
                    redirect('admin:users', 'edit', $db->lastInsertId());
                    return;
                } else {
                    addError("There was an error regsitering the user.");
                }
            } elseif ($action === "update" && checkUserData($userData)) {
                $strQuery = "UPDATE users SET name = :name, email = :email, role = :role";

                if ($userData["password"] !== "") {
                    $strQuery .= ", password_hash = :hash";
                    $userData["hash"] = password_hash($userData["password"], PASSWORD_DEFAULT);
                }

                if ($isUserAdmin) {
                    $strQuery .= ", is_banned = :is_banned";
                    $userData["is_banned"] = (int)isset($_POST["is_banned"]);
                }

                unset($userData["password"]);
                unset($userData["password_confirm"]);
                $success = queryDB("$strQuery WHERE id = :id", $userData);

                if ($success) {
                    addSuccess("Modification saved");
                } else {
                    addError("There was an error editing the user");
                }
            }
        }
    }

    // no POST data
    elseif ($action === "update") {
        $user = queryDB("SELECT * FROM users where id = ?", $queryUserId)->fetch();

        if (is_array($user)) {
            $userData = $user;
        } else {
            addError("Unknown user");
            redirect("admin:users");
            return;
        }
    }

    $formTarget = buildUrl("admin:users", $action);
    if ($action === "update") {
        $formTarget = buildUrl("admin:users", $action, $queryUserId);
    }
?>

<?php if ($action === "create"): ?>
    <h2>Add a new user</h2>
<?php else: ?>
    <h2>Edit user with id <?= $queryUserId; ?></h2>
<?php endif; ?>

<?php require_once "../app/messages.php"; ?>

<form action="<?= $formTarget; ?>" method="post">
    <label>Name : <input type="text" name="user_name" required placeholder="Name" value="<?php safeEcho($userData["name"]); ?>"></label> <?php createTooltip("Minimum four letters, numbers, hyphens or underscores"); ?> <br>
    <br>

    <label>Email : <input type="email" name="user_email" required placeholder="Email adress" value="<?php safeEcho($userData["email"]); ?>"></label> <br>
    <br>

    <label>Password : <input type="password" name="user_password" placeholder="Password" ></label> Minimum 3 of any characters but minimum one lowercase and uppercase letter and one number. <br>
    <label>Confirm password : <input type="password" name="user_password_confirm" placeholder="Password confirmation" ></label> <br>
    <br>

    <label>Role:
        <?php if($isUserAdmin): ?>
            <select name="user_role">
                <option value="commenter" <?= ($userData["role"] === "commenter")? "selected" : null; ?>>Commenter</option>
                <option value="writer" <?= ($userData["role"] === "writer")? "selected" : null; ?>>Writer</option>
                <option value="admin" <?= ($userData["role"] === "admin")? "selected" : null; ?>>Admin</option>
            </select>
        <?php else: ?>
            <?php safeEcho($user["role"]); ?>
        <?php endif; ?>
    </label> <br>
    <br>

    <?php if($isUserAdmin && $action === "update"): ?>
        <label>Block user: <input type="checkbox" name="is_banned" value="<?= $userData["is_banned"] ? "checked" : null; ?>"></label> <br>
        <br>
    <?php endif; ?>

    <?php addCSRFFormField("user$action"); ?>

    <input type="submit" value="Edit">
</form>

<?php
}

// --------------------------------------------------

elseif ($action === "delete") {
    if (verifyCSRFToken($query['csrftoken'], "deleteuser")) {
        if ($queryUserId === $userId) {
            addError("Can't delete your own user");
        } else {
            $success = queryDB("DELETE FROM users WHERE id = ?", $queryUserId, true);
            // note that if the user id doesn't exist, it returns success too

            if ($success) {
                // update the user_id column of all pages created by that deleted user to the current user
                queryDB(
                    "UPDATE pages SET user_id = :new_id WHERE user_id = :old_id",
                    ["new_id" => $userId, "old_id" => $queryUserId]
                );

                // update the user_id column of all medias created by that deleted user to the current user
                queryDB(
                    "UPDATE medias SET user_id = :new_id WHERE user_id = :old_id",
                    ["new_id" => $userId, "old_id" => $queryUserId]
                );

                queryDB("DELETE FROM comments WHERE user_id = ?", $queryUserId);

                addSuccess("User with id $queryUserId has been successfully deleted.");
            } else {
                addError("There was an error deleting the user with id $queryUserId");
            }
        }
    }

    redirect("admin:users");
    return;
}

// --------------------------------------------------
// if action === "show" or other actions are fobidden for that user

else {
?>

<h2>List of all users</h2>

<?php require_once "../app/messages.php"; ?>

<?php if ($isUserAdmin): ?>
<div>
    <a href="<?= buildUrl("admin:users", "create") ?>">Add a user</a>
</div>

<br>
<?php endif; ?>

<tr>
    <th>id <?= getTableSortButtons("users", "id"); ?></th>
    <th>name <?= getTableSortButtons("users", "name"); ?></th>
    <th>email <?= getTableSortButtons("users", "email"); ?></th>
    <th>role <?= getTableSortButtons("users", "role"); ?></th>
    <th>creation date <?= getTableSortButtons("users", "creation_date"); ?></th>
    <th>banned <?= getTableSortButtons("users", "is_banned"); ?></th>
</tr>

<table>

<?php
    $fields = ["id", "name", "email", "role", "creation_date", "is_banned"];
    if (! in_array($query['orderbyfield'], $fields)) {
        $orderByField = "id";
    }

    $users = queryDB(
        "SELECT * FROM users
        ORDER BY $query[orderbyfield] $query[orderdir]
        LIMIT " . $adminMaxTableRows * ($query['page'] - 1) . ", $adminMaxTableRows"
    );

    if ($isUserAdmin) {
        $deleteToken = setCSRFToken("deleteuser");
    }

    while ($_user = $users->fetch()):
?>

    <tr>
        <td><?= $_user["id"]; ?></td>
        <td><?php safeEcho($_user["name"]); ?></td>
        <td><?php safeEcho($_user["email"]); ?></td>
        <td><?php safeEcho($_user["role"]); ?></td>
        <td><?= $_user["creation_date"]; ?></td>
        <td><?= $_user["is_banned"] === 1 ? 1 : 0; ?></td>

        <?php if($isUserAdmin || $_user["id"] == $userId): ?>
            <td><a href="<?= buildUrl("admin:users", "update", $_user["id"]); ?>">Edit</a></td>
        <?php endif; ?>

        <?php if($isUserAdmin && $_user["id"] !== $userId): /* even admins can't delete their own user */ ?>
            <td><a href="<?= buildUrl("admin:users", "delete", $_user["id"], $deleteToken); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>

<?php
    endwhile; // end while users from DB
?>
</table>
<?php
    $table = "users";
    require_once "pagination.php";
} // end if action = show
