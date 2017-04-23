<?php

class AdminController extends Controller {

  function __construct() {
    echo "admin controller";
  }

  function getLogin() {

    Views::load("login", "Login")$title
    include login;
  }
}