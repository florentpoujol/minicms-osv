<?php
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

$db = new PDO(
    "mysql:host=".$config["db_host"].";dbname=".$config["db_name"].";charset=utf8",
    $config["db_user"],
    $config["db_password"],
    $options
);

function queryDB($strQuery, $data = null, $getSuccess = false)
{
    global $db;
    $query = $db->prepare($strQuery);

    if (isset($data) && ! is_array($data)) {
        $data = [$data];
    }

    $success = $query->execute($data);

    if ($getSuccess) {
        return $success;
    }

    return $query;
}
