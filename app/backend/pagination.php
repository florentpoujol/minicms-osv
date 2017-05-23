
<br>

<div class="pagination">
<?php
    $maxItemsPerPage = $maxPostPerPage;

    if ($folder == $adminSectionName) {
        $nbRows = queryDB("SELECT COUNT(*) FROM $table")->fetch();
        $nbRows = $nbRows["COUNT(*)"];
        $maxItemsPerPage = $adminMaxTableRows;
    }

    $nbPages = ceil($nbRows / $maxItemsPerPage);
    if ($nbPages > 1) {
        echo "Pages:";

        for ($i=1; $i <= $nbPages; $i++) {
            $url = str_replace("&page=".$pageNumber, "", $pageURL);
            $url .= "&page=$i";

            $class = "";
            if ($pageNumber === $i) {
                $class = "page-selected";
            }
?>

    <a href="<?php echo "$url" ?>" class="<?php echo $class; ?>"><?php echo $i; ?></a>

<?php
        }
    }
?>
</div>
