
<br>

<div class="pagination">
<?php
    $maxItemsPerPage = $maxPostPerPage;

    if ($query['section'] === $config['admin_section_name']) {
        // only when in the admin section $table is defined
        // by the script including this one
        $nbRows = queryDB("SELECT COUNT(*) FROM $table")->fetch()["COUNT(*)"];
        $maxItemsPerPage = $adminMaxTableRows;
    }

    $nbPages = ceil($nbRows / $maxItemsPerPage);
    if ($nbPages > 1) {
        echo "Pages:";

        for ($i=1; $i <= $nbPages; $i++) {
            $url = str_replace("&page=$pageNumber", "", $pageURL);
            $url .= "&page=$i";

            $class = "";
            if ($pageNumber === $i) {
                $class = "page-selected";
            }
?>
    <a href="<?= $url; ?>" class="<?= $class; ?>"><?= $i; ?></a>

<?php
        }
    }
?>
</div>
