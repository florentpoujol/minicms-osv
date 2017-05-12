
<nav>
    <ul>
        <?php if ($isUserAdmin): ?>
        <li><a href="<?php echo buildLink($folder, "config"); ?>">Config</a></li>
        <?php endif; ?>
        <?php if ($isUserAdmin || $user["role"] === "writer"): ?>
        <li><a href="<?php echo buildLink($folder, "articles"); ?>">Articles</a></li>
        <li><a href="<?php echo buildLink($folder, "pages"); ?>">Pages</a></li>
        <li><a href="<?php echo buildLink($folder, "medias"); ?>">Medias</a></li>
        <?php endif; ?>
        <li><a href="<?php echo buildLink($folder, "users"); ?>">Users</a></li>
        <li><a href="<?php echo buildLink($folder, "comments"); ?>">Comments</a></li>
        <li><a href="<?php echo buildLink(null, "logout"); ?>">Logout</a></li>
    </ul>
</nav>
