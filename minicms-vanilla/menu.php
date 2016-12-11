<?php
if (isset($menu) === false)
  $menu = buildMenu();
?>

<nav id="main-menu">
  <ul>

    <?php foreach ($menu as $i => $parent): ?>
    <li class="<?php if ($parent["id"] === $page["id"]) echo "selected"; ?>">
    <a href="/<?php echo $siteDirectory; ?><?php echo ($config["use_url_rewrite"] ? $parent["url_name"] : "index.php?q=".$parent["id"]); ?>"><?php echo htmlspecialchars($parent["title"]); ?></a>

      <?php if (count($parent["children"]) > 0): ?>
      <ul>
        <?php foreach ($parent["children"] as $j => $child): ?>
          <li class="<?php if ($child["id"] === $page["id"]) echo "selected"; ?>">
            <a href="/<?php echo $siteDirectory; ?><?php echo ($config["use_url_rewrite"] ? $child["url_name"] : "index.php?q=".$child["id"]); ?>"><?php echo htmlspecialchars($child["title"]); ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>

    </li>
    <?php endforeach; ?>

  </ul>
</nav>
