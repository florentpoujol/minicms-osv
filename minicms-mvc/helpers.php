<?php

function logout() {
  unset($_SESSION["minicms_mvc_auth"]);
  header("Location: index.php");
  exit();
}

function redirect() {
  header("Location: index.php");
  exit();
}

function loadView($bodyView, $pageTitle, $headView = "") {
  require_once "views/layout.php";
}

function isLoggedIn() {
  return ($user !== false);
}