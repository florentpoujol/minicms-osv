<!DOCTYPE html>
<html>
<head>
  <title><?php if (isset($title)) echo $title; ?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" type="text/css" href="../common.css">
  <link rel="stylesheet" type="text/css" href="backend.css">
</head>
<body>
<?php
  if (isset($currentUser) && $currentUser !== false)
    require_once "menu.php";
?>