<?php
declare(strict_types=1);
require_once "../app/frontend/header.php";
?>
<h1>Blog</h1>

<section id="categories">
    <h2>Categories</h2>

    <?php if ($pageContent["categoriesCount"] > 0): ?>
        <ul>
            <?php while ($cat = $pageContent["categories"]->fetch()): ?>
                <li><a href="<?php echo buildUrl("category", idOrSlug($cat)); ?>"><?php safeEcho($cat["title"]); ?></a></li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No category</p>
    <?php endif; ?>
</section>

<div id="content">
    <?php if ($pageContent["postsCount"] > 0): ?>
        <?php while ($post = $pageContent["posts"]->fetch()):
            $cat = [
                "id" => $post["category_id"],
                "slug" => $post["category_slug"]
            ];
        ?>
            <article>
                <header>
                    <h2><a href="<?= buildUrl("blog", idOrSlug($post)); ?>"><?php safeEcho($post["title"]); ?></a></h2>
                    <p>
                        Posted on <?php safeEcho($post["creation_date"]." by ".$post["user_name"]); ?>
                        |
                        Category: <a href="<?= buildUrl("category", idOrSlug($cat)); ?>"><?php safeEcho($post["category_title"]); ?></a>
                    </p>
                </header>

                <?= processContent($post["content"]); ?>
            </article>

            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No posts yet</p>
    <?php endif; ?>
</div>

<?php
$nbRows = $pageContent["postsCount"];
require_once "../app/backend/pagination.php";

require_once "../app/frontend/footer.php";
