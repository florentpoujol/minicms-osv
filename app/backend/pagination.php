
<br>

<div class="pagination">
    Pages:
<?php
    $nbRows = queryDB("SELECT COUNT(*) FROM $table")->fetch();
    $nbRows = $nbRows["COUNT(*)"];
    $nbPages = ceil($nbRows / $adminMaxTableRows);

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
?>
</div>
