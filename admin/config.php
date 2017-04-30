<?php
if (! isset($db)) {
    exit;
}

if (! $isUserAdmin) {
    redirect(["section" => ""]);
}

$title = "Config";
require_once "header.php";
?>

<h1>Config</h1>

<?php
$configData = $config; // $config is set in database.php

if (isset($_POST["edit_config"])) {
    $configData["site_title"] = $_POST["site_title"]; // no check, can be empty
    $configData["use_url_rewrite"] = isset($_POST["use_url_rewrite"]) ? "1": "0";
    $configData["allow_comments"] = isset($_POST["allow_comments"]) ? "1": "0";

    // update
    foreach ($configData as $name => $value) {
        queryDB("UPDATE config SET value = ? WHERE name = ?", [$value, $name]);
    }
}
?>

<form action="?section=config" method="post">
    <label>Website title:
        <input type="text" name="site_title" value="<?php echo $configData["site_title"]; ?>">
    </label> <br>
    <br>

    <label>Use URL rewrite:
        <input type="checkbox" name="use_url_rewrite" <?php echo ($configData["use_url_rewrite"] === "0" ? null : "checked"); ?>>
    </label>
    <?php createTooltip("Use the 'url name' of each pages as their URL instead of 'index.php?q=[the page id]'"); ?> <br>
    <br>

    <label>Allow comments on pages:
        <input type="checkbox" name="allow_comments" <?php echo ($configData["allow_comments"] === "0" ? null : "checked"); ?>>
    </label><br>
    <br>

    <input type="submit" name="edit_config" value="Update">
</form>
