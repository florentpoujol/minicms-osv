<?php

require_once __dir__ . "/header.php";
?>
<h1>Blog</h1>

<section id="categories">
    <h2>Categories</h2>

    <?php if ($pageContent["categoriesCount"] > 0): ?>
        <ul>
            <?php while ($cat = $pageContent["categories"]->fetch()): ?>
                <li><a href="<?= buildUrl("category", null, idOrSlug($cat)); ?>"><?php safeEcho($cat["title"]); ?></a></li>
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
                    <h2><a href="<?= buildUrl("post", null, idOrSlug($post)); ?>"><?php safeEcho($post["title"]); ?></a></h2>
                    <p>
                        Posted on <?php safeEcho($post["creation_date"]." by ".$post["user_name"]); ?>
                        |
                        Category: <a href="<?= buildUrl("category", null, idOrSlug($cat)); ?>"><?php safeEcho($post["category_title"]); ?></a>
                    </p>
                </header>

                <?= processContent($post["content"]); ?>
            </article>

            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No post yet</p>
    <?php endif; ?>
</div>

<?php
$nbRows = $pageContent["postsCount"];
require_once __dir__ . "/../backend/pagination.php";

require_once __dir__ . "/footer.php";
