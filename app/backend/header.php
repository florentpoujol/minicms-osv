<!DOCTYPE html>
<html>
<head>
    <title><?php if (isset($title)) echo $title; ?></title>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" type="text/css" href="<?php echo $siteDirectory; ?>common.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $siteDirectory; ?>backend.css">
</head>
<body>

    <nav>
        <ul>
            <?php if ($isUserAdmin): ?>
            <li><a href="<?php echo buildLink($folder, "config"); ?>">Config</a></li>
            <?php endif; ?>
            <?php if ($isUserAdmin || $user["role"] === "writer"): ?>
            <li><a href="<?php echo buildLink($folder, "categories"); ?>">Categories</a></li>
            <li><a href="<?php echo buildLink($folder, "posts"); ?>">Posts</a></li>
            <li><a href="<?php echo buildLink($folder, "pages"); ?>">Pages</a></li>
            <li><a href="<?php echo buildLink($folder, "medias"); ?>">Medias</a></li>
            <?php endif; ?>
            <li><a href="<?php echo buildLink($folder, "users"); ?>">Users</a></li>
            <li><a href="<?php echo buildLink($folder, "comments"); ?>">Comments</a></li>
            <li><a href="<?php echo buildLink(null, "logout"); ?>">Logout</a></li>
        </ul>
    </nav>
