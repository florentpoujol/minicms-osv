<?php

function logout() {
  unset($_SESSION["minicms_handmade_auth"]);
  header("Location: index.php");
  exit();
}


function redirect($dest = []) {
  if (isset($dest["page"]) === false)
    $dest["page"] = "index.php";

  global $section, $action, $resourceId;

  if (isset($dest["section"]) === false)
    $dest["section"] = $section;
  if (isset($dest["action"]) === false)
    $dest["action"] = $action;
  if (isset($dest["id"]) === false)
    $dest["id"] = $resourceId;

  if (isset($dest["error"]) === false)
    $dest["error"] = "";
  if (isset($dest["info"]) === false)
    $dest["info"] = "";

  $str = $dest["page"]."?";
  $str .= "&section=".$dest["section"];
  $str .= "&action=".$dest["action"];
  $str .= "&id=".$dest["id"];
  $str .= "&errormsg=".$dest["error"];
  $str .= "&infomsg=".$dest["info"];

  header("Location: $str");
  exit();
}


function getExtension($path) {
  return pathinfo($path, PATHINFO_EXTENSION);
}


function isImage($path) {
  $ext = getExtension($path);
  return ($ext == "jpg" || $ext == "jpeg" || $ext == "png");
}


function createTooltip($text) {
  echo '<span class="tooltip"><span class="icon">?</span><span class="text">'.$text.'</span></span>';
}


function checkPatterns($patterns, $subject) {
  if (is_array($patterns) === false)
    $patterns = [$patterns];

  for ($i=0; $i<count($patterns); $i++) {
    if (preg_match($patterns[$i], $subject) == false) {
      // keep loose comparison !
      // preg_match() returns 0 if pattern isn't found, or false on error
      return false;
    }
  }

  return true;
}


function buildMenuHierarchy() {
  global $db;
  if (isset($db)) {
    $menu = $db->query('SELECT * FROM pages WHERE parent_page_id IS NULL AND published = 1 ORDER BY menu_priority ASC')->fetchAll();

    foreach ($menu as $i => $parentPage)
      $menu[$i]["children"] = $db->query('SELECT * FROM pages WHERE parent_page_id = '.$parentPage["id"].' AND published = 1 ORDER BY menu_priority ASC')->fetchAll();

    return $menu;
  }
  else
    return ["error: no database connexion."];
}


function processPageContent($text) {
  $text = processImageShortcodes($text);
  $text = processManifestoShortcodes($text);
  $text = processCarouselShortcodes($text);
  return $text;
}


function processImageShortcodes($text) {
  $pattern = "/\[img\s+([\w\-]+)\s?([^\]]+)?\]/i";
  $matches = [];
  preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

  foreach ($matches as $i => $match) {
    $replacement = "";
    $mediaName = $match[1];
    $media = queryDB("SELECT * FROM medias WHERE name = ?", $mediaName)->fetch();

    if ($media === false)
      $replacement = "[Img error: there is no media with name '$mediaName']";
    else {
      $replacement = '<img src="uploads/'.$media["filename"].'"';

      if (isset($match[2])) {
        $data = $match[2];

        if (is_numeric($data))
          $replacement .= ' width="'.$data.'px"';
        elseif (strpos($data, "=") === false)
          $replacement .= 'title="'.$data.'" alt=""';
        else
          $replacement .= $data;
      }

      $replacement .= ">";
    }

    $text = str_replace($match[0], $replacement, $text);
  }

  return $text;
}


function processManifestoShortcodes($text) {
  $pattern = "/\[manifesto\s+([\w\-]+)\s+([^\]]+)\]/i";
  $matches = [];
  preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

  foreach ($matches as $i => $match) {
    $replacement = "";
    $mediaName = $match[1];
    $media = queryDB("SELECT name, filename FROM medias WHERE name = ?", $mediaName)->fetch();

    if ($media === false)
      $replacement = "[Manifesto error: there is no media with name '$mediaName']";
    else {
      $replacement = '<section class="manifesto">';
      $replacement .= '<img src="uploads/'.$media["filename"].'" class="manifesto-img" alt="'.htmlspecialchars($media["name"]).'" title="'.htmlspecialchars($media["name"]).'">';
      $replacement .= '<p class="manifesto-text">'.$match[2].'</p>';
      $replacement .= '</section>';
    }

    $text = str_replace($match[0], $replacement, $text);
  }

  return $text;
}


function processCarouselShortcodes($text) {
  $pattern = "/\[carousel\s+([^\]]+)\]/i";
  //  (\d+)\s+((\d+)\s+)?
  $matches = [];
  preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

  foreach ($matches as $i => $match) {
    $originalStr = $match[0];
    $mediasStr = $match[1];
    $mediaNames = explode(" ", $mediasStr);

    $replacement = '<section class="carousel"> 
    <div class="carousel-imgs">
    ';

    foreach ($mediaNames as $j => $mediaName) {
      $media = queryDB("SELECT * FROM medias WHERE name = ?", $mediaName)->fetch();

      if ($media === false) {
        $error = "Carousel error: there is no media with name '$mediaName'";
        $media = ["filename" => "http://placehold.it/500x400?text=".str_replace(" ", "+", $error), "name" => $error];
      }
      else
        $media["filename"] = "uploads/".$media["filename"];

      $replacement .= 
'      <img class="carousel-img" src="'.$media["filename"].'" alt="'.$media["name"].'" title="'.$media["name"].'" data-slide="'.$j.'">
    ';
    }

    $replacement .= '
    </div> 
    <div class="carousel-controls">
        <div class="carousel-arrows left">&lt;</div>
        <div class="carousel-indicators">
    ';

    foreach ($mediaNames as $j => $mediaName) {
      $replacement .= '
            <div class="carousel-indicator" data-slide-to="'.$j.'"></div>';
    }

    $replacement .= '
        </div>
        <div class="carousel-arrows right">&gt;</div> 
    </div>
</section>
    ';

    $text = str_replace($originalStr, $replacement, $text);
  }

  return $text;
}


function printTableSortButtons($table, $field = "id") {
  global $section, $orderByTable, $orderByField, $orderDir;
  $ASC = "";
  $DESC = "";
  if ($table === $orderByTable && $field === $orderByField)
    ${$orderDir} = "selected-sort-option";

  return
  "<div class='table-sort-arrows'>
      <a class='$ASC' href='?section=$section&orderbytable=$table&orderbyfield=$field&orderdir=ASC'>&#9650</a>
      <a class='$DESC' href='?section=$section&orderbytable=$table&orderbyfield=$field&orderdir=DESC'>&#9660</a>
  </div>";
}

