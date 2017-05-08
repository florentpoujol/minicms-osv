
<nav>
    <ul>
        <?php if ($isUserAdmin): ?>
        <li><a href="<?php echo buildLink($folder, "config", "show"); ?>">Config</a></li>
        <?php endif; ?>
        <?php if ($isUserAdmin || $user["role"] === "writer"): ?>
        <li><a href="<?php echo buildLink($folder, "articles", "show"); ?>">Articles</a></li>
        <li><a href="<?php echo buildLink($folder, "pages", "show"); ?>">Pages</a></li>
        <li><a href="<?php echo buildLink($folder, "medias", "show"); ?>">Medias</a></li>
        <?php endif; ?>
        <li><a href="<?php echo buildLink($folder, "users", "show"); ?>">Users</a></li>
        <li><a href="<?php echo buildLink($folder, "comments", "show"); ?>">Comments</a></li>
        <li><a href="<?php echo buildLink(null, "logout"); ?>">Logout</a></li>
    </ul>
</nav>
