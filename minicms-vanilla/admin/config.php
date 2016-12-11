<?php
if (isset($db) === false) exit();

$title = "Config";
require_once "header.php";
?>

<h1>Config</h1>

<?php
$configData = $config; // $config is set in database.php

if (isset($_POST["edit_config"])) {
  $configData["site_title"] = $_POST["site_title"]; // no check, can be empty

  /*$configData["site_directory"] = $_POST["site_directory"]; 
  // can be empty, but must have a trailing slash (and no leading slash) if not empty
  if (strlen($configData["site_directory"]) > 0)
    $configData["site_directory"] = trim($configData["site_directory"], "/")."/";
  if ($configData["site_directory"] === "/")
    $configData["site_directory"] = "";*/

  $configData["use_url_rewrite"] = $_POST["use_url_rewrite"];

  if ($configData["use_url_rewrite"] === "on")
    $configData["use_url_rewrite"] = "1";
  else
    $configData["use_url_rewrite"] = "0";

  // update
  foreach ($configData as $name => $value) {
    queryDB("UPDATE config SET value = ? WHERE name = ?", [$value, $name]);
  }
}
?>

<form action="?section=config" method="post">

  <label>Website title :
    <input type="text" name="site_title" value="<?php echo $configData["site_title"]; ?>">
  </label> <br>
  <br>

  <!--<label>Site directory :
    <input type="text" name="site_directory" value="<?php echo $configData["site_directory"]; ?>">
  </label>
  <?php createTooltip("Set the directory in which the website is installed if it is not at the root of your domain. If not empty, must have a trailing slash."); ?> <br>
  <br> -->

  <label>Use URL rewrite :
    <input type="checkbox" name="use_url_rewrite" <?php echo ($configData["use_url_rewrite"] === "0" ? null : "checked"); ?>>
  </label>
  <?php createTooltip("Use the 'url name' of each pages as their URL instead of 'index.php?q=[the page id]'"); ?> <br>
  <br>

  <input type="submit" name="edit_config" value="Update">
</form>