<?php

$user = queryTestDB("SELECT * FROM users WHERE name='commenter'")->fetch();
$_SESSION["user_id"] = $user["id"]; // login the user

$queryString = "section=admin:users";

// --------------------------------------------------
$currentTestName = "Commenter can only access the Update action on the admin users page";

$content = loadSite($queryString);

assertRedirect(buildUrl("admin:users", "update", $user["id"]));

// --------------------------------------------------
$currentTestName = "The update action can only be used with a specified user id";

loadSite($queryString . "&action=update");

assertRedirect(buildUrl("admin:users", "update", $user["id"]));

// --------------------------------------------------
$currentTestName = "Commenter can only update their own user";

$content = loadSite($queryString . "&action=update&id=$user[id]");

assertStringContains($content, "Edit user with id $user[id]");

// let's try specifying another id in the URL
loadSite($queryString . "&action=update&id=2");

assertRedirect(buildUrl("admin:users", "update", $user["id"]));

