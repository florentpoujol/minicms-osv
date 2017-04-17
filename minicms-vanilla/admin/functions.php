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


  $str = $dest["page"]."?";
  $str .= "&section=".$dest["section"];
  $str .= "&action=".$dest["action"];
  $str .= "&id=".$dest["id"];

  /*if (isset($dest["error"]) === false)
    $dest["error"] = "";
  if (isset($dest["info"]) === false)
    $dest["info"] = "";

  $str .= "&errormsg=".$dest["error"];
  $str .= "&infomsg=".$dest["info"];*/

  foreach ($dest as $name => $value) {
    if ($name === "section" || $name === "action" || $name === "id")
      continue;

    $str .= "&$name=$value";
  }


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
  // it used to be more things here
  $text = processImageShortcodes($text);
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


function checkNewUserData($addedUser) {
  global $db;
  $errorMsg = checkNameFormat($addedUser["name"]);
  $errorMsg .= checkEmailFormat($addedUser["email"]);
  $errorMsg .= checkPasswordFormat($addedUser["password"], $addedUser["password_confirm"]);

  // check that the name doesn't already exist
  $query = $db->prepare('SELECT id FROM users WHERE name=? OR email=?');
  $query->execute([$addedUser["name"], $addedUser["email"]]);
  $user = $query->fetch();

  if ($user !== false)
    $errorMsg .= "A user with the name '".htmlspecialchars($addedUser["name"])."' already exists \n";

  return $errorMsg;
}


function checkNameFormat($name) {
  $namePattern = "[a-zA-Z0-9_-]{4,}";
  if (checkPatterns("/$namePattern/", $name) === false)
    return "The user name has the wrong format. Minimum four letters, numbers, hyphens or underscores. \n";
  return "";
}


function checkEmailFormat($email) {
  $emailPattern = "^[a-zA-Z0-9_\.-]{1,}@[a-zA-Z0-9-_\.]{4,}$";
  if (checkPatterns("/$emailPattern/", $email) === false)
    return "The email has the wrong format. \n";
  return "";
}


function checkPasswordFormat($password, $passwordConfirm) {
  $errorMsg = "";
  $patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
  $minPasswordLength = 3;

  if (checkPatterns($patterns, $password) === false || strlen($password) < $minPasswordLength)
    $errorMsg .= "The password must be at least $minPasswordLength characters long and have at least one lowercase letter, one uppercase letter and one number. \n";

  if ($password !== $passwordConfirm)
    $errorMsg .= "The password confirmation does not match the password. \n";

  return $errorMsg;
}