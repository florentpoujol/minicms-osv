<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html>
<head>
    <title><?php if (isset($title)) echo $title; ?></title>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" type="text/css" href="<?= $site['directory']; ?>common.css">
    <link rel="stylesheet" type="text/css" href="<?= $site['directory']; ?>backend.css">
</head>
<body>

    <nav>
        <ul>
            <?php if ($user['isAdmin']): ?>
                <?php $goToConfigCSRFToken = setCSRFTokens("gotoconfig"); ?>
                <li><a href="<?= buildUrl("admin:config", null, null, $goToConfigCSRFToken); ?>">Config</a></li>
            <?php endif; ?>
            <?php if ($user['isAdmin'] || $user["role"] === "writer"): ?>
                <li><a href="<?= buildUrl('admin:categories'); ?>">Categories</a></li>
                <li><a href="<?= buildUrl('admin:posts'); ?>">Posts</a></li>
                <li><a href="<?= buildUrl('admin:pages'); ?>">Pages</a></li>
                <li><a href="<?= buildUrl('admin:medias'); ?>">Medias</a></li>
                <li><a href="<?= buildUrl('admin:menus'); ?>">Menus</a></li>
            <?php endif; ?>
            <li><a href="<?= buildUrl('admin:users'); ?>">Users</a></li>
            <li><a href="<?= buildUrl('admin:comments'); ?>">Comments</a></li>
            <li><a href="<?= buildUrl('logout'); ?>">Logout</a></li>
        </ul>
    </nav>
