
<nav>
    <ul>
        <?php if ($isUserAdmin): ?>
        <li><a href="<?php echo buildLinkF("admin", "config"); ?>">Config</a></li>
        <?php endif; ?>
        <?php if ($isUserAdmin || $user["role"] === "writer"): ?>
        <li><a href="<?php echo buildLinkF("admin", "articles"); ?>">articles</a></li>
        <li><a href="<?php echo buildLinkF("admin", "pages"); ?>">Pages</a></li>
        <li><a href="<?php echo buildLinkF("admin", "medias"); ?>">Medias</a></li>
        <?php endif; ?>
        <li><a href="<?php echo buildLinkF("admin", "users"); ?>">Users</a></li>
        <li><a href="<?php echo buildLinkF("admin", "comments"); ?>">Comments</a></li>
        <li><a href="<?php echo buildLink("logout"); ?>">Logout</a></li>
    </ul>
</nav>
