
<nav>
    <ul>
        <?php if ($isUserAdmin): ?>
        <li><a href="?section=config">Config</a></li>
        <?php endif; ?>
        <?php if ($isUserAdmin || $user["role"] === "writer"): ?>
        <li><a href="?section=pages">Pages</a></li>
        <li><a href="?section=medias">Medias</a></li>
        <?php endif; ?>
        <li><a href="?section=users">Users</a></li>
        <li><a href="?section=comments">Comments</a></li>
        <li><a href="?section=login&action=logout">Comments</a></li>
    </ul>
</nav>
