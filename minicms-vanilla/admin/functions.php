<?php

function logout() {
  unset($_SESSION["minicms_handmade_auth"]);
  header("Location: index.php");
  exit();
}

function redirect($dest = []) {
  if (isset($dest["page"]) == false)
    $dest["page"] = "index.php";

  global $section, $action, $resourceId;

  if (isset($dest["section"]) == false)
    $dest["section"] = $section;
  if (isset($dest["action"]) == false)
    $dest["action"] = $action;
  if (isset($dest["id"]) == false)
    $dest["id"] = $resourceId;

  if (isset($dest["error"]) == false)
    $dest["error"] = "";
  if (isset($dest["info"]) == false)
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

function getMediaExtension($path) {
  return pathinfo($path, PATHINFO_EXTENSION);
}

function isImage($path) {
  $ext = getMediaExtension($path);
  return ($ext == "jpg" || $ext == "png");
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
