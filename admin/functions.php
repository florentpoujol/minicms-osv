<?php

function logout()
{
    unset($_SESSION["minicms_handmade_auth"]);
    header("Location: index.php");
    exit;
}


function redirect($dest = [])
{
    global $section, $action, $resourceId;

    if (! isset($dest["page"])) {
        $dest["page"] = "index.php";
    }
    if (! isset($dest["section"])) {
        $dest["section"] = $section;
    }
    if (! isset($dest["action"])) {
        $dest["action"] = $action;
    }
    if (! isset($dest["id"])) {
        $dest["id"] = $resourceId;
    }

    $url = $dest["page"]."?";
    $url .= "&section=".$dest["section"];
    $url .= "&action=".$dest["action"];
    $url .= "&id=".$dest["id"];

    foreach ($dest as $name => $value) {
        if ($name === "section" || $name === "action" || $name === "id") {
            continue;
        }

        $url .= "&$name=$value";
    }

  header("Location: $url");
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


function checkPatterns($patterns, $subject)
{
    if (! is_array($patterns)) {
        $patterns = [$patterns];
    }

    for ($i=0; $i<count($patterns); $i++) {
        if (preg_match($patterns[$i], $subject) == false) {
            // keep loose comparison !
            // preg_match() returns 0 if pattern isn't found, or false on error
            return false;
        }
    }

  return true;
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
    global $section, $orderByTable, $orderByField, $orderDir;
    $ASC = "";
    $DESC = "";
    if ($table === $orderByTable && $field === $orderByField) {
        ${$orderDir} = "selected-sort-option";
    }

    return
    "<div class='table-sort-arrows'>
    <a class='$ASC' href='?section=$section&orderbytable=$table&orderbyfield=$field&orderdir=ASC'>&#9650</a>
    <a class='$DESC' href='?section=$section&orderbytable=$table&orderbyfield=$field&orderdir=DESC'>&#9660</a>
</div>";
}


function checkNewUserData($addedUser)
{
    global $db;
    $errorMsg = checkNameFormat($addedUser["name"]);
    $errorMsg .= checkEmailFormat($addedUser["email"]);
    $errorMsg .= checkPasswordFormat($addedUser["password"], $addedUser["password_confirm"]);

// check that the name doesn't already exist
    $query = $db->prepare('SELECT id FROM users WHERE name=? OR email=?');
    $query->execute([$addedUser["name"], $addedUser["email"]]);
    $user = $query->fetch();

    if ($user !== false) {
        $errorMsg .= "A user with the name '".htmlspecialchars($addedUser["name"])."' already exists \n";
    }

    return $errorMsg;
}


function checkNameFormat($name)
{
    $namePattern = "[a-zA-Z0-9_-]{4,}";
    if (checkPatterns("/$namePattern/", $name) === false) {
        return "The user name has the wrong format. Minimum four letters, numbers, hyphens or underscores. \n";
    }

    return "";
}


function checkEmailFormat($email)
{
    $emailPattern = "^[a-zA-Z0-9_\.-]{1,}@[a-zA-Z0-9-_\.]{3,}$";
    if (checkPatterns("/$emailPattern/", $email) === false) {
        return "The email has the wrong format. \n";
    }

    return "";
}


function checkPasswordFormat($password, $passwordConfirm)
{
    $errorMsg = "";
    $patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
    $minPasswordLength = 3;

    if (checkPatterns($patterns, $password) === false || strlen($password) < $minPasswordLength) {
        $errorMsg .= "The password must be at least $minPasswordLength characters long and have at least one lowercase letter, one uppercase letter and one number. \n";
    }

    if ($password !== $passwordConfirm) {
        $errorMsg .= "The password confirmation does not match the password. \n";
    }

    return $errorMsg;
}


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
