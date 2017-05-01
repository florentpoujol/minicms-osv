
<nav>
    <ul>
        <?php if ($isUserAdmin): ?>
        <li><a href="?p=config">Config</a></li>
        <?php endif; ?>
        <?php if ($isUserAdmin || $user["role"] === "writer"): ?>
        <li><a href="?p=pages">Pages</a></li>
        <li><a href="?p=medias">Medias</a></li>
        <?php endif; ?>
        <li><a href="?p=users">Users</a></li>
        <li><a href="?p=comments">Comments</a></li>
        <li><a href="?p=login&a=logout">Logout</a></li>
    </ul>
</nav>
