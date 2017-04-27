<?php

class RegisterController extends Controller
{

    function __construct()
    {
        parent::__construct();
        if (isLoggedIn() === true) {
            Messages::addError("You are already logged In");
            redirect();
        }
    }

    // --------------------------------------------------

    public function getIndex()
    {
        loadView("register", lang("register_title"), ["name" => "", "email" => ""]);
    }

    public function postIndex()
    {
        $newUser = [
            "name" => $_POST["register_name"],
            "email" => $_POST["register_name"],
            "password" => $_POST["register_password"],
            "password_confirm" => $_POST["register_password_confirm"]
        ];

        if (Validator::newUser($newUser) === true) {
            unset($newUser["password_confirm"]);
            $lastInsertId = Users::insert($newUser);

            if ($lastInsertId !== false) {
                Messages::addSuccess("new user created.");
                $user = Users::get(["id" => $lastInsertId]);

                if ($user !== false) {
                    if (Emails::sendConfirmEmail($user) === true) {
                        Messages::addSuccess("confirmation email sent");
                        redirect("login");
                    }
                    else {
                        Messages::addError("error sending the confirmation email");
                    }
                }
                else {
                    Messages::addError("error retrieving the new user. no email sent");
                }
            }
            else {
                Messages::addError("error registering new user");
            }
        }

        loadView("register", lang("register_title"), ["name" => $newUser["name"], "email" => $newUser["email"]]);
    }

    // --------------------------------------------------

    public function getConfirmEmail()
    {
        $token = trim($_GET["token"]);
        $user = Users::get([
            "id" => $_GET["id"],
            "email_token" => $token
        ]);

        if ($token !== "" && $user !== false) {
            // update user
        }
        else {
            Messages::addError("Can't accces that page.");
            redirect();
        }
    }

    public function getResendConfirmEmail()
    {

    }

    public function postResendConfirmEmail()
    {

    }
}
