<?php

class LoginController extends Controller {

  function __construct() {
    parent::__construct();
    if ($this->user !== false) {
      redirect();
    }
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
      $this->user = Users::get(["name" => $loginName]);

      if ($this->user === false)
        Messages::addError("No user by that name !");

      else {
        // user has been found by name
        // first check that the user is activated
        if ($this->user->email_token !== "") {
          Messages::addError("This user is not activated yet. You need to click the link in the email that has been sent just after registration. You can send this email again from the register page.");
        }
        else {
          if (password_verify($password, $this->user->password_hash)) {
            // OK correct password
            $_SESSION["minicms_mvc_auth"] = $this->user->id;
            redirect(); // to index
          }
          else
            Messages::addError("Wrong password !");
        }
      }
    }

    loadView("login", lang("login_title"));
  }

  function getLostPassword() {
    loadView("lostpassword", lang("lostpassword"));
  }

  function postLostPassword() {
    $email = $_POST["forgot_password_email"];
    $emailFormatOK = checkEmailFormat($email);

    if ($emailFormatOK === true) {
      $user = Users::get(["email" => $email]);

      if ($user !== false) {
        $token = md5(microtime(true)+mt_rand());
        $success = Users::updatePasswordToken($user->id, $token);

        if ($success) {
          // sendChangePasswordEmail($email, $token);
          Messages::addSuccess("An email has been sent to this address. Click the link within 48 hours.");
        }
      }
      else
        Messages::addError("No users has that email.");
    }

    loadView("lostpassword", lang("lostpassword"));
  }
}