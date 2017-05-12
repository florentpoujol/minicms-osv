<?php
require_once "../app/frontend/header.php";
?>
<h1><?php echo $currentPage["title"] ?></h1>

<div id="page-content">
    <?php echo Michelf\Markdown::defaultTransform($currentPage["content"]); ?>
</div> <!-- end #page-content -->

<?php
if ($currentPage["id"] > 0) {
    require_once "../app/frontend/comments.php";
}

require_once "../app/frontend/footer.php";
