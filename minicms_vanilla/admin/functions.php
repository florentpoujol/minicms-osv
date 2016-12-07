<?php

function logout() {
  unset($_SESSION["minicms_handmade_auth"]);
  header("Location: index.php");
  exit();
}

/*function redirect($section = "", $action = "", $id = "", $errorMsg = "", $infoMsg = "") {
  if ($section != "") $section = "section=$section";
  if ($action != "") $action = "action=$action";
  if ($id != "") $id = "id=$id";
  if ($errorMsg != "") $errorMsg = "errorMsg=$errorMsg";
  if ($infoMsg != "") $infoMsg = "infoMsg=$infoMsg";
  header("Location: index.php?$section&$action&id&$errorMsg&$infoMsg");
  exit();
}*/

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

