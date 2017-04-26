<?php

function logout()
{
  unset($_SESSION["minicms_mvc_auth"]);
  header("Location: index.php");
  exit();
}


function redirect()
{
  header("Location: index.php");
  exit();
}


function loadView($bodyView, $pageTitle, $headView = "")
{
  global $user;
  require_once "views/layout.php";
}


function isLoggedIn()
{
  return ($user !== false);
}


function checkPatterns($patterns, $subject)
{
  if (is_array($patterns) === false) {
    $patterns = [$patterns];
  }

  foreach ($patterns as $pattern) {
    if (preg_match($pattern, $subject) == false) {
      // keep loose comparison !
      // preg_match() returns 0 if pattern isn't found, or false on error
      return false;
    }
  }

  return true;
}

function checkNameFormat($name)
{
  $namePattern = "[a-zA-Z0-9_-]{4,}";
  if (checkPatterns("/$namePattern/", $name) === false) {
    return "The user name has the wrong format. Minimum four letters, numbers, hyphens or underscores.";
  }
  return "";
}


function checkEmailFormat($email)
{
  $emailPattern = "^[a-zA-Z0-9_\.-]{1,}@[a-zA-Z0-9-_\.]{4,}$";
  if (checkPatterns("/$emailPattern/", $email) === false) {
    Message::addError("The email has the wrong format");
    // return "The email has the wrong format. \n";
    return false;
  }
  return true;
}


function checkPasswordFormat($password, $passwordConfirm)
{
  $errorMsg = "";
  $patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
  $minPasswordLength = 3;

  if (checkPatterns($patterns, $password) === false || strlen($password) < $minPasswordLength) {
    $errorMsg .= "The password must be at least $minPasswordLength characters long and have at least one lowercase letter, one uppercase letter and one number. \n";
  }

  if ($password !== $passwordConfirm) {
    $errorMsg .= "The password confirmation does not match the password. \n";
  }

  return $errorMsg;
}
