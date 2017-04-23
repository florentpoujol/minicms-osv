<?php

class LoginController extends Controller {

  function __construct() {
    if ($user !== false)
      redirect();
  }
  
  function getIndex() {
    loadView("login", lang("login_title"));
  }

  function postIndex() {
    // check that the fields are not empty
    $loginName = $_POST["login_name"];
    $password = $_POST["login_password"];
    // $recaptcha_response = $_POST["g-recaptcha-response"];

    if (strlen($loginName) === 0 || strlen($password) === 0)
      Messages::addError("The name or password is empty !");

    // elseif (verifyRecaptcha($recaptcha_response) === true) {
    else {
      $user = Users::get(["name" => $loginName]);

      if ($user === false)
        Messages::addError("No user by that name !");

      else {
        // user has been found by name
        // first check that the user is activated
        if ($user->email_token !== "") {
          Messages::addError("This user is not activated yet. You need to click the link in the email that has been sent just after registration. You can send this email again from the register page.");
        }
        else {
          if (password_verify($password, $user->password_hash)) {
            // OK correct password
            $_SESSION["minicms_mvc_auth"] = $user->id;
            redirect(); // to index
          }
          else
            Messages::addError("Wrong password !");
        }
      }
    }

    loadView("login", lang("login_title"));
  }
}