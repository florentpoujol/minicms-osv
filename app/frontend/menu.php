
<nav id="main-menu">
    <ul>
        <?php foreach ($menuHierarchy as $i => $parentPage): ?>
        <li class="<?php if ($parentPage["id"] === $currentPage["id"]) echo "selected"; ?>">
            <a href="<?php echo $siteDirectory; ?><?php echo ($config["use_url_rewrite"] ? $parentPage["slug"] : "index.php?p=".$parentPage["id"]); ?>"><?php echo $parentPage["title"]; ?></a>

            <?php if (count($parentPage["children"]) > 0): ?>
                <ul>
                    <?php foreach ($parentPage["children"] as $j => $childPage): ?>
                    <li class="<?php if ($childPage["id"] === $currentPage["id"]) echo "selected"; ?>">
                        <a href="<?php echo $siteDirectory; ?><?php echo ($config["use_url_rewrite"] ? $childPage["slug"] : "index.php?p=".$childPage["id"]); ?>"><?php echo $childPage["title"]; ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </li>
        <?php endforeach; ?>

        <li class="<?php if ($currentPage["id"] === -2) echo "selected"; ?>">
<?php
$link = buildLink(null, "login");
if ($isLoggedIn) {
    $link = buildLink("admin");
}
 ?>
            <a href="<?php echo $link; ?>">
                <?php echo ($isLoggedIn ? "Admin" : "Login/Register"); ?>
            </a>
        </li>
    </ul>
</nav>
