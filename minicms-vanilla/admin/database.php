<?php
// database settings
$BD_HOST = "localhost";
$BD_NAME = "minicms_vanilla";
$BD_USER_NAME = "root";
$BD_USER_PASSWORD = "";

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

$bd = null;

try {
  $db = new PDO("mysql:host=$BD_HOST;dbname=$BD_NAME;charset=utf8", $BD_USER_NAME, $BD_USER_PASSWORD, $options);
}
catch (Exception $e) {
  echo "error connecting to the database <br>";
  echo $e->getMessage();
  exit();
}


function queryDB($strQuery, $data = [], $getSuccess = false) {
  global $db;
  $query = $db->prepare($strQuery);

  if (is_array($data) === false) $data = [$data];
  $success = $query->execute($data);

  if ($getSuccess)
    return $success;
  else
    return $query;
}


$rawConfig = queryDB("SELECT * FROM config");
$config = [];
while ($entry = $rawConfig->fetch()) {
  $config[$entry["name"]] = $entry["value"];
}
