<!DOCTYPE html>
<html>
<head>
<?php
$title = isset($pageContent["title"]) ? $pageContent["title"] : "";

if ($config["site_title"] !== "") {
    $title .= " | ".$config["site_title"];
}
?>
    <title><?php echo $title; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php
$robots = "index,follow";
if (isset($pageContent["published"]) && $pageContent["published"] === 0) {
    $robots = "noindex,nofollow";
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
    global $config, $resourceName;

?>
        <ul>
            <?php foreach ($items as $i => $item): ?>
<?php
    $type = isset($item["type"]) ? $item["type"] : "folder";
    $target = isset($item["target"]) ? $item["target"] : "";
    $name = isset($item["name"]) ? $item["name"] : "";
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

                $_folder = null;
                if ($type === "category") {
                    $_folder = $type;
                }
                else if ($type === "post") {
                    $_folder = "blog";
                }

                $field = "id";
                if ($config["use_url_rewrite"]) {
                    $field = "slug";
                }

                $target = buildLink($_folder, $dbPage[$field]);

                if ($dbPage["id"] == $resourceName || $dbPage["slug"] == $resourceName) {
                    $selected = "selected";
                }
            }
            else {
                $name = "[page not found]";
                $target = "";
            }
        }
?>
            <li class="<?php echo $selected; ?>">
                <a href="<?php echo $target; ?>" ><?php safeEcho($name); ?></a>
<?php
    }
    else {
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
}

buildMenuStructure($menuStructure);
?>

    </nav>

    <div id="site-container">
