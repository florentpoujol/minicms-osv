<?php
require_once "../app/frontend/header.php";
?>
<h1><?php echo $currentPage["title"] ?></h1>

<div id="post-date">
    Posted on <?php echo $currentPage["creation_date"]." by ".$currentPage["user_name"]; ?>
    |
    Cetegory: <?php echo $currentPage["category_name"]; ?>
</div>

<div id="post-content">
    <?php echo processContent($currentPage["content"]); ?>
</div> <!-- end #post-content -->

<?php
if ($currentPage["id"] > 0) {
    require_once "../app/frontend/comments.php";
}

require_once "../app/frontend/footer.php";
