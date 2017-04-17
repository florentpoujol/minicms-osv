<nav>
  <ul>
<?php if ($isUserAdmin): ?>
    <li><a href="?section=config">Config</a></li>
<?php endif;
if ($isUserAdmin || $currentUser["role"] === "writer"): ?>
    <li><a href="?section=pages">Pages</a></li>
    <li><a href="?section=medias">Medias</a></li>
<?php endif; ?>
    <li><a href="?section=users">Users</a></li>
    <li><a href="?section=comments">Comments</a></li>
  </ul>

  <form action="index.php" method="post">
    <input type="submit" name="logout" value="Logout">
  </form>
</nav>