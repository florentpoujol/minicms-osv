<?php
require_once "../app/frontend/header.php";
?>
<h1><?php safeEcho($pageContent["title"]); ?></h1>

<?php if (isset($pageContent["category_id"])):
// if this content is a POST and not a single PAGE
$cat = [
    "id" => $pageContent["category_id"],
    "slug" => $pageContent["category_slug"]
];
?>
<div id="post-date">
    Posted on <?php safeEcho($pageContent["creation_date"] . " by " . $pageContent["user_name"]); ?>
    |
    Category: <a href="<?= buildUrl("category", idOrSlug($cat)); ?>"><?php safeEcho($pageContent["category_title"]); ?></a>
</div>
<?php endif; ?>

<div id="content">
    <?= processContent($pageContent["content"]); ?>
</div>

<?php
if ($pageContent["id"] > 0) {
    require_once "../app/frontend/comments.php";
}

require_once "../app/frontend/footer.php";
