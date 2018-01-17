<!DOCTYPE html>
<html>
<head>
<?php
$title = $pageContent["title"] ?? '';

if ($config["site_title"] !== "") {
    $title .= " | $config[site_title]";
}
?>
    <title><?= $title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php
$robots = "noindex,nofollow";
/*if (isset($pageContent["published"]) && $pageContent["published"] === 1) {
    $robots = "index,follow";
}*/
?>
    <meta name="robots" content="<?= $robots; ?>">

    <link rel="stylesheet" type="text/css" href="<?= $siteDirectory; ?>common.css">
    <link rel="stylesheet" type="text/css" href="<?= $siteDirectory; ?>frontend.css">
</head>
<body>

    <nav id="main-menu">

<?php
function buildMenuStructure($items)
{
    global $config, $query;
?>
        <ul>
            <?php foreach ($items as $i => $item): ?>
<?php
    $type = $item["type"] ?? "folder";
    $target = $item["target"] ?? "";
    $name = $item["name"] ?? "";
    $selected = "";

    if ($type !== "folder") { // page, post, category, homepage or external
        if ($type !== "external") { // page, post, category or homepage
            $table = "pages";
            if ($type === "category") {
                $table = "categories";
            }

            $field = "id";
            if (! is_numeric($target)) {
                $field = "slug";
            }

            $dbPage = queryDB("SELECT id, title, slug FROM $table WHERE $field = ?", $target)->fetch();

            if (is_array($dbPage)) {
                if ($name === "") {
                    $name = $dbPage["title"];
                }

                $section = $type;
                if ($section === "homepage") {
                    $section = "page";
                }

                $field = "id";
                if ($config["use_url_rewrite"]) {
                    $field = "slug";
                }

                $target = buildUrl($section, null, $dbPage[$field]);

                if ($dbPage["id"] === $query['id'] || $dbPage["slug"] === $query['id']) {
                    $selected = "selected";
                }
            } else {
                $name = "[page not found]";
                $target = "";
            }
        }
?>
            <li class="<?php echo $selected; ?>">
                <a href="<?php echo $target; ?>" ><?php safeEcho($name); ?></a>
<?php
    } else {
        echo "<li>";
        safeEcho($name);
    }

    if (isset($item["children"]) && count($item["children"]) > 0) {
        buildMenuStructure($item["children"]);
    }
?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php
} // end function buildMenuStructure()

buildMenuStructure($menuStructure);
?>

    </nav>

    <div id="site-container">
