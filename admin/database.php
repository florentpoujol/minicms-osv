<?php
require_once "../dbconfig.php";

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false
];

$bd = null;

try {
    $db = new PDO("mysql:host=".$dbConfig["host"].";dbname=".$dbConfig["name"].";charset=utf8", $dbConfig["user"], $dbConfig["password"], $options);
}
catch (Exception $e) {
    echo "error connecting to the database <br>";
    echo $e->getMessage();
    exit;
}


function queryDB($strQuery, $data = [], $getSuccess = false)
{
    global $db;
    $query = $db->prepare($strQuery);

    if (! is_array($data)) $data = [$data];
    $success = $query->execute($data);

    if ($getSuccess) {
        return $success;
    }

    return $query;
}


$rawConfig = queryDB("SELECT * FROM config");
$config = [];
while ($entry = $rawConfig->fetch()) {
    $config[$entry["name"]] = $entry["value"];
}
