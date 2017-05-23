<?php
require_once "../app/frontend/header.php";
?>
<h1><?php safeEcho($pageContent["title"]); ?></h1>

<?php if (isset($pageContent["category_id"])):
$cat = [
    "id" => $pageContent["category_id"],
    "slug" => $pageContent["category_slug"]
];
?>
<div id="post-date">
    Posted on <?php safeEcho($pageContent["creation_date"]." by ".$pageContent["user_name"]); ?>
    |
    Category: <a href="<?php echo buildLink("category", idOrSlug($cat)); ?>"><?php safeEcho($pageContent["category_title"]); ?></a>
</div>
<?php endif; ?>

<div id="content">
    <?php echo processContent($pageContent["content"]); ?>
</div>

<?php
if ($pageContent["id"] > 0) {
    require_once "../app/frontend/comments.php";
}

require_once "../app/frontend/footer.php";
