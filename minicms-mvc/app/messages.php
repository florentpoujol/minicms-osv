<?php

class Messages
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
}
