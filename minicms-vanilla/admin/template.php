<!DOCTYPE html>
<html>
<head>
  <title>
<?php
  if (!isset($title))
    $title = "default admin title";

  echo $title;
?></title>
  <meta charset="utf-8">
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" type="text/css" href="../common.css">
  <link rel="stylesheet" type="text/css" href="backend.css">
</head>
<body>
<?php
  require_once "menu.php";

  echo "<p>Welcome ".$currentUser["name"].", you are a ".$currentUser["role"]." </p>";

  if ($section != "")
    require_once $section.".php";
?>
</body>
</html>