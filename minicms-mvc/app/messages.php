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
        $query = self::$db->prepare("INSERT INTO messages(type, text, session_id) VALUES(:type, :text, :session_id)");
        $params = [
            "type" => "success",
            "session_id" => session_id()
        ];

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
        $query = self::$db->prepare("SELECT * FROM messages WHERE session_id=?");
        $sessionId = session_id();
        $success = $query->execute([$sessionId]);

        if ($success === true) {
            while ($msg = $query->fetch()) {
                if ($msg->type === "success") {
                    self::$successes[] = $msg->text;
                }
                else if ($msg->type === "error") {
                    self::$errors[] = $msg->text;
                }
            }

            $query = self::$db->prepare("DELETE FROM messages WHERE session_id=?");
            $query->execute([$sessionId]);
        }
    }
}
