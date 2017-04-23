<?php

$dictionaries = [];
require_once "languages/en.php";

$lang = isset($_GET["lang"]) ? $_GET["lang"] : "en";
if ($lang !== "en")
  require_once "languages/$lang.php";

function lang($key) {
  global $lang, $dictionaries;
  $value = $dictionaries[$lang][$key];
  return $value;
}
