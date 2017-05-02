<?php

function logout()
{
    unset($_SESSION["minicms_vanilla_auth"]);
    header("Location: index.php");
    exit;
}


function redirect($dest = [])
{
    $url = "";
    if (count($dest) > 0) {
        $url .= "?";
        foreach ($dest as $name => $value) {
            $url .= "$name=$value&";
        }
        $url = rtrim($url, "&");
    }

    saveMsgForLater();
    header("Location: index.php$url");
    exit;
}


function getExtension($path)
{
    return pathinfo($path, PATHINFO_EXTENSION);
}


function isImage($path)
{
    $ext = getExtension($path);
    return ($ext == "jpg" || $ext == "jpeg" || $ext == "png");
}


function createTooltip($text)
{
    echo '<span class="tooltip"><span class="icon">?</span><span class="text">'.$text.'</span></span>';
}


function buildMenuHierarchy()
{
    global $db;
    $menu = $db->query('SELECT * FROM pages WHERE parent_page_id IS NULL AND published = 1 ORDER BY menu_priority ASC')->fetchAll();

    foreach ($menu as $i => $parentPage) {
        $menu[$i]["children"] = $db->query('SELECT * FROM pages WHERE parent_page_id = '.$parentPage["id"].' AND published = 1 ORDER BY menu_priority ASC')->fetchAll();
    }

    return $menu;
}


function processPageContent($text)
{
    // it used to be more things here
    return processImageShortcodes($text);
}


function processImageShortcodes($text)
{
    $pattern = "/\[img\s+([\w\-]+)\s?([^\]]+)?\]/i";
    $matches = [];
    preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

    foreach ($matches as $i => $match) {
        $replacement = "";
        $mediaName = $match[1];
        $media = queryDB("SELECT * FROM medias WHERE name = ?", $mediaName)->fetch();

        if ($media === false) {
            $replacement = "[Img error: there is no media with name '$mediaName']";
        }
        else {
            $replacement = '<img src="uploads/'.$media["filename"].'"';

            if (isset($match[2])) {
                $data = $match[2];

                if (is_numeric($data)) {
                    $replacement .= ' width="'.$data.'px"';
                }
                elseif (strpos($data, "=") === false) {
                    $replacement .= 'title="'.$data.'" alt=""';
                }
                else {
                    $replacement .= $data;
                }
            }

            $replacement .= ">";
        }

        $text = str_replace($match[0], $replacement, $text);
    }

    return $text;
}


function printTableSortButtons($table, $field = "id")
{
    global $page, $orderByTable, $orderByField, $orderDir;
    $ASC = "";
    $DESC = "";
    if ($table === $orderByTable && $field === $orderByField) {
        ${$orderDir} = "selected-sort-option";
    }

    return
    "<div class='table-sort-arrows'>
    <a class='$ASC' href='?p=$page&orderbytable=$table&orderbyfield=$field&orderdir=ASC'>&#9650</a>
    <a class='$DESC' href='?p=$page&orderbytable=$table&orderbyfield=$field&orderdir=DESC'>&#9660</a>
</div>";
}

// --------------------------------------------------

function pregMatches($patterns, $subject)
{
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $subject) !== 1) {
            return 0;
        }
    }

    return 1;
}

function checkPageTitleFormat($title)
{
    $minTitleLength = 4;
    if (strlen($title) < $minTitleLength) {
        addError("The title must be at least $minTitleLength characters long.");
        return false;
    }

    return true;
}

function checkURLNameFormat($name)
{
    $namePattern = "^[a-zA-Z0-9_-]{2,}$";

    if (preg_match("/$namePattern/", $name) !== 1) {
        addError("The URL name has the wrong format. Minimum 2 letters, numbers, hyphens or underscores.");
        return false;
    }

    return true;
}

function checkNameFormat($name)
{
    $namePattern = "^[a-zA-Z0-9_-]{4,}$";

    if (preg_match("/$namePattern/", $name) !== 1) {
        addError("The name has the wrong format. Minimum four letters, numbers, hyphens or underscores. No Spaces.");
        return false;
    }

    return true;
}

function checkEmailFormat($email)
{
    $emailPattern = "^[a-zA-Z0-9_\.+-]{1,}@[a-zA-Z0-9-_\.]{3,}$";

    if (preg_match("/$emailPattern/", $email) !== 1) {
        addError("The email has the wrong format.");
        return false;
    }

    return true;
}

function checkPasswordFormat($password, $passwordConfirm = null)
{
    $patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
    $minPasswordLength = 3;
    $ok = true;

    if (pregMatches($patterns, $password) !== 1 || strlen($password) < $minPasswordLength) {
        addError("The password must be at least $minPasswordLength characters long and have at least one lowercase letter, one uppercase letter and one number.");
        $ok = false;
    }

    if (isset($passwordConfirm) && $password !== $passwordConfirm) {
        addError("The password confirmation does not match the password.");
        $ok = false;
    }

    return $ok;
}

function checkNewUserData($newUser)
{
    $userOK = checkUserData($newUser);

    $user = queryDB(
        "SELECT id FROM users WHERE name=? OR email=?",
        [$newUser["name"], $newUser["email"]]
    )->fetch();

    if (is_array($user)) {
        addError("A user already exists with that name or email.");
        $userOK = false;
    }

    return $userOK;
}

function checkUserData($user)
{
    $userOK = checkNameFormat($user["name"]);
    $userOK = (checkEmailFormat($user["email"]) && $userOK);

    if (isset($user["password"]) && $user["password"] !== "") {
        if (! isset($user["password_confirm"])) {
            $user["password_confirm"] = null;
        }

        $userOK = (checkPasswordFormat($user["password"], $user["password_confirm"]) && $userOK);
    }

    if (isset($user["role"])) {
        $roles = ["admin", "writer", "commenter"];
        if (! in_array($user["role"], $roles)) {
            addError("Role must be 'commenter', 'writer' or 'admin'.");
            $userOK = false;
        }
    }

    return $userOK;
}

// --------------------------------------------------

function verifyRecaptcha($userResponse)
{
    global $config;

    $params = [
        "secret" => $config["recaptchaSecretKey"],
        "response" => $userResponse
    ];

    $url = "https://www.google.com/recaptcha/api/siteverify";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    $response = curl_exec($curl);
    curl_close($curl);

    if (is_string($response)) {
        $response = json_decode($response, true);
        $response = $response["success"];
    }

    return $response;
}

// --------------------------------------------------
// messages

$errors = [];
$successes = [];

function addError($msg)
{
    global $errors;
    $errors[] = $msg;
}

function addSuccess($msg)
{
    global $successes;
    $successes[] = $msg;
}

function saveMsgForLater()
{
    global $db, $errors, $successes;

    $query = $db->prepare("INSERT INTO messages(type, text, session_id) VALUES(:type, :text, :session_id)");
    $params = [
        "type" => "error",
        "text" => "",
        "session_id" => session_id()
    ];

    if (count($errors) > 0) {
        foreach ($errors as $msg) {
            $params["text"] = $msg;
            $query->execute($params);
        }
    }

    $params["type"] = "success";
    if (count($successes) > 0) {
        foreach ($successes as $msg) {
            $params["text"] = $msg;
            $query->execute($params);
        }
    }
}

function populateMsgs()
{
    global $errors, $successes;
    $sessionId = session_id();

    $raw = queryDB("SELECT * FROM messages WHERE type='error' AND session_id=?", $sessionId);
    while ($msg = $raw->fetch()) {
        $errors[] = $msg["text"];
    }
    queryDB("DELETE FROM messages WHERE type='error' AND session_id=?", $sessionId);

    $raw = queryDB("SELECT * FROM messages WHERE type='success' AND session_id=?", $sessionId);
    while ($msg = $raw->fetch()) {
        $successes[] = $msg["text"];
    }
    queryDB("DELETE FROM messages WHERE type='success' AND session_id=?", $sessionId);
}