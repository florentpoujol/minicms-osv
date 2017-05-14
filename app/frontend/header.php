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

    <nav id="main-menu">
        <ul>
            <?php foreach ($menuHierarchy as $i => $parentPage): ?>
            <li class="<?php if ($parentPage["id"] === $currentPage["id"]) echo "selected"; ?>">
                <a href="<?php echo $siteDirectory; ?><?php echo ($config["use_url_rewrite"] ? $parentPage["slug"] : "index.php?p=".$parentPage["id"]); ?>"><?php echo $parentPage["title"]; ?></a>

                <?php if (count($parentPage["children"]) > 0): ?>
                    <ul>
                        <?php foreach ($parentPage["children"] as $j => $childPage): ?>
                        <li class="<?php if ($childPage["id"] === $currentPage["id"]) echo "selected"; ?>">
                            <a href="<?php echo $siteDirectory; ?><?php echo ($config["use_url_rewrite"] ? $childPage["slug"] : "index.php?p=".$childPage["id"]); ?>"><?php echo $childPage["title"]; ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </li>
            <?php endforeach; ?>

            <li class="<?php if ($currentPage["id"] === -2) echo "selected"; ?>">
    <?php
    $link = buildLink(null, "login");
    if ($isLoggedIn) {
        $link = buildLink($adminSectionName);
    }
     ?>
                <a href="<?php echo $link; ?>">
                    <?php echo ($isLoggedIn ? "Admin" : "Login/Register"); ?>
                </a>
            </li>
        </ul>
    </nav>

    <div id="site-container">
