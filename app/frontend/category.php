<?php
require_once "../app/frontend/header.php";
?>
<h1>Category: <?php safeEcho($pageContent["title"]); ?></h1>

<div id="content">
    <?php if ($pageContent["postCount"] > 0): ?>
        <ul>
        <?php while ($post = $pageContent["posts"]->fetch()): ?>
            <li><a href="<?php echo buildLink(null, idOrSlug($post["id"])); ?>"><?php safeEcho($post["title"]); ?></a></li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No posts in this category</p>
    <?php endif; ?>
</div>

<?php
$nbRows = $pageContent["postCount"];
require_once "../app/backend/pagination.php";

require_once "../app/frontend/footer.php";
