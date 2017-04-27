<?php

class Messages extends Model
{
    // arrays of msg per user id
    private static $successes = [];
    private static $errors = [];

    public static function addSuccess($msg)
    {
        self::$successes[] = $msg;
    }

    public static function addError($msg)
    {
        self::$errors[] = $msg;
    }

    public static function getSuccesses()
    {
        $temp = self::$successes;
        self::$successes = [];
        return $temp;
    }

    public static function getErrors()
    {
        $temp = self::$errors;
        self::$errors = [];
        return $temp;
    }

    // save leftover msg in database for retrival later
    public static function saveForLater() {
        global $user;
        if ($user === false) {
            return;
        }

        $query = self::$db->prepare("INSERT INTO messages(type, text, user_id) VALUES(:type, :text, :user_id)");
        $params = [
            "type" => "success",
            "user_id" => $user->id
        ];;
        foreach (self::$successes as $msg) {
            $params["text"] = $msg;
            $query->execute($params);
        }
        $params["type"] = "error";
        foreach (self::$errors as $msg) {
            $params["text"] = $msg;
            $query->execute($params);
        }
    }

    // retrieve msg from DB, if any
    public static function populate() {
        global $user;
        if ($user === false) {
            return;
        }

        $query = self::$db->prepare("SELECT * FROM messages WHERE user_id=?");
        $success = $query->execute([$user->id]);

        if ($success === true) {
            while ($msg = $query->fetch()) {
                if ($msg->type === "success") {
                    self::$successes[] = $msg->text;
                }
                else if ($msg->type === "error") {
                    self::$errors[] = $msg->text;
                }
            }

            $query = self::$db->prepare("DELETE FROM messages WHERE user_id=?");
            $query->execute([$user->id]);
        }
    }
}
