<?php
if (! isset($menuHierarchy)) {
    $menuHierarchy = buildMenuHierarchy();
}
?>

<nav id="main-menu">
    <ul>
        <?php foreach ($menuHierarchy as $i => $parentPage): ?>
        <li class="<?php if ($parentPage["id"] === $page["id"]) echo "selected"; ?>">
            <a href="<?php echo $siteDirectory; ?><?php echo ($config["use_url_rewrite"] ? $parentPage["url_name"] : "index.php?q=".$parentPage["id"]); ?>"><?php echo htmlspecialchars($parentPage["title"]); ?></a>

            <?php if (count($parentPage["children"]) > 0): ?>
                <ul>
                    <?php foreach ($parentPage["children"] as $j => $childPage): ?>
                    <li class="<?php if ($childPage["id"] === $page["id"]) echo "selected"; ?>">
                        <a href="<?php echo $siteDirectory; ?><?php echo ($config["use_url_rewrite"] ? $childPage["url_name"] : "index.php?q=".$childPage["id"]); ?>"><?php echo htmlspecialchars($childPage["title"]); ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </li>
        <?php endforeach; ?>

        <li><a href="admin"><?php echo (isset($currentUser) ? "Admin" : "Login"); ?></a></li>
    </ul>
</nav>
