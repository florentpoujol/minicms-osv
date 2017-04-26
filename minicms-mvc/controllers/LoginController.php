<?php

class LoginController extends Controller
{

    function __construct()
    {
        parent::__construct();
        if ($this->user !== false) {
            redirect();
        }
    }

    // --------------------------------------------------

    function getIndex()
    {
        loadView("login", lang("login_title"));
    }

    function postIndex()
    {
        $loginName = $_POST["login_name"];
        $password = $_POST["login_password"];
        // $recaptcha_response = $_POST["g-recaptcha-response"];

        if (strlen($loginName) === 0 || strlen($password) === 0) {
            Messages::addError("The name or password is empty !");
        }

        // elseif (verifyRecaptcha($recaptcha_response) === true) {
        else {
            $this->user = Users::get(["name" => $loginName]);

            if ($this->user === false) {
                Messages::addError("No user by that name !");
            }
            else {
                if ($this->user->email_token !== "") {
                    Messages::addError("This user is not activated yet. You need to click the link in the email that has been sent just after registration. You can send this email again from the register page.");
                }
                else {
                    if (password_verify($password, $this->user->password_hash) === true) {
                        $_SESSION["minicms_mvc_auth"] = $this->user->id;
                        redirect(); // to index
                    }
                    else {
                        Messages::addError("Wrong password !");
                    }
                }
            }
        }

        loadView("login", lang("login_title"));
    }

    // --------------------------------------------------

    function getLostPassword()
    {
        loadView("lostpassword", lang("lostpassword"));
    }

    function postLostPassword()
    {
        $email = $_POST["forgot_password_email"];
        $emailFormatOK = checkEmailFormat($email);

        if ($emailFormatOK === true) {
            $user = Users::get(["email" => $email]);

            if ($user !== false) {
                $token = md5(microtime(true)+mt_rand());
                $success = Users::updatePasswordToken($user->id, $token);

                if ($success === true) {
                    Emails::sendChangePassword($user);
                    Messages::addSuccess("An email has been sent to this address. Click the link within 48 hours.");
                }
            }
            else {
                Messages::addError("No users has that email.");
            }
        }
        else {
            Messages::addError("Wrong email format.");
        }

        loadView("lostpassword", lang("lostpassword"));
    }

    // --------------------------------------------------

    function getResetPassword()
    {
        $token = trim($_GET["token"]);
        $user = Users::get([
            "id" => $_GET["id"],
            "password_token" => $token
        ]);

        if ($token !== "" && $user !== false &&
            time() < $user->password_change_time + (3600 * 48)) {
            loadView("resetpassword", "Reset your password");
        }
        else {
            Messages::addError("Can't accces that page.");
            redirect();
        }
    }

    function postResetPassword()
    {
        $token = trim($_GET["token"]);
        $user = Users::get([
            "id" => $_GET["id"],
            "password_token" => $token
        ]);

        if ($token !== "" && $user !== false &&
            time() < $user->password_change_time + (3600 * 48)) {
            $password = $_POST["reset_password"];
            $formatOK = checkPasswordFormat($password, $_POST["reset_password_confirm"]);

            if ($formatOK === true) {
                $succcess = Users::updatePassword($user->id, $password);

                if ($success === true) {
                    Messages::addSuccess("password changed successfully");
                    redirect("login");
                }
                else {
                    Messages::addError("error changing password");
                }
            }

            loadView("resetpassword", "Reset your password");
        }
        else {
            Messages::addError("Can't accces that page.");
            redirect();
        }
    }
}
