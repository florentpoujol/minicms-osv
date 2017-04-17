<?php

/*echo "<pre>";
print_r($_SERVER);
echo "</pre>";*/

$siteProtocol = $_SERVER["REQUEST_SCHEME"];
$siteDomain = $_SERVER["HTTP_HOST"];
$siteDirectory = str_replace("index.php", "", $_SERVER["SCRIPT_NAME"]);
// $siteDirectory = ltrim($siteDirectory, "/");

$currentSiteURL = $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"].$siteDirectory;

// var_dump($currentSiteURL);
