<?php

/*echo "<pre>";
print_r($_SERVER);
echo "</pre>";*/

$siteProtocol = $_SERVER["REQUEST_SCHEME"];
$siteDomain = $_SERVER["HTTP_HOST"];
$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]);
// $siteDirectory = ltrim($siteDirectory, "/");

$currentSiteURL = $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"].$siteDirectory;


// check if user logged in
$currentUserId = -1;
$currentUser = null;

if (isset($_SESSION["minicms_handmade_auth"])) {
  $currentUserId = (int)$_SESSION["minicms_handmade_auth"];
  $currentUser = queryDB("SELECT * FROM users WHERE id=?", $currentUserId)->fetch();

  if ($currentUser === false)
    logout(); // for some reason the logged in user isn't found in the databse... let's log it out, just in case
}