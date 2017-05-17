<!DOCTYPE html>
<html>
<head>
<?php
$title = $currentPage["title"];
if ($config["site_title"] !== "") {
    $title .= " | ".$config["site_title"];
}
?>
    <title><?php echo $title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php
$robots = "noindex,nofollow";
if (isset($currentPage["published"]) && $currentPage["published"] === 1) {
    $robots = "index,follow";
}
?>
    <meta name="robots" content="<?php echo $robots; ?>">

    <link rel="stylesheet" type="text/css" href="<?php echo $siteDirectory; ?>common.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $siteDirectory; ?>frontend.css">
</head>
<body>

    <nav id="main-menu">

<?php
function buildMenuStructure($items)
{
    global $config;

?>
        <ul>
            <?php foreach ($items as $i => $item): ?>
            <li class="">
<?php
    $type = isset($item["type"]) ? $item["type"] : "folder";
    $target = isset($item["target"]) ? $item["target"] : "";
    $name = isset($item["name"]) ? $item["name"] : "";

    if ($type !== "folder") { // page, post, category, homepage or external
        if ($type !== "external") { // page, post, category or homepage
            $table = "pages";
            if ($type === "category") {
                $table = "categories";
            }

            $dbPage = queryDB("SELECT id, title, slug FROM $table WHERE id = ? OR slug = ?", [$target, $target])->fetch();

            if (is_array($dbPage)) {
                if ($name === "") {
                    $name = $dbPage["title"];
                }

                $field = "id";
                if ($config["use_url_rewrite"]) {
                    $field = "slug";
                }

                $target = buildLink(null, $dbPage[$field]);
                if ($type === "category") {
                    $target = buildLink("category", $dbPage[$field]);
                }
            }
            else {
                $name = "[page not found]";
                $target = "";
            }
        }
?>
                <a href="<?php echo $target; ?>"><?php echo $name; ?></a>
<?php
    }
    else {
        echo $name;
    }

    if (isset($item["children"]) && count($item["children"]) > 0) {
        buildMenuStructure($item["children"]);
    }
?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php
}

buildMenuStructure($menuStructure);
?>

    </nav>

    <div id="site-container">
