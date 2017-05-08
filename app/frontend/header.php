<!DOCTYPE html>
<html>
<head>
<?php
$title = $currentPage["title"];
if ($config["site_title"] !== "") {
    $title .= " | ".$config["site_title"];
}
?>
    <title><?php echo $title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php
$robots = "noindex,nofollow";
if (isset($currentPage["published"]) && $currentPage["published"] === 1) {
    $robots = "index,follow";
}
?>
    <meta name="robots" content="<?php echo $robots; ?>">

    <link rel="stylesheet" type="text/css" href="<?php echo $siteDirectory; ?>common.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $siteDirectory; ?>frontend.css">
</head>
<body>

<?php
require_once "menu.php";
?>

    <div id="site-container">
