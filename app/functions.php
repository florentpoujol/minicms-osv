<?php

function logout()
{
    $_SESSION = [];
    unset($_SESSION);
    session_destroy();
    header("Location: index.php");
    exit;
}


function redirect($folder = null, $page = null, $action = null, $id = null, $csrfToken = null)
{
    if (is_array($folder)) {
        if (isset($folder["p"])) {
            $page = $folder["p"];
        }

        if (isset($folder["a"])) {
            $action = $folder["a"];
        }

        if (isset($folder["id"])) {
            $id = $folder["id"];
        }

        if (isset($folder["f"])) {
            $folder = $folder["f"];
        }
    }

    $url = buildLink($folder, $page, $action, $id, $csrfToken);

    saveMsgForLater();
    header("Location: $url");
    exit;
}


function buildLink($folder = null, $page = null, $action = null, $id = null, $csrfToken = null)
{
    global $config, $siteDirectory;
    $link = "";

    if (isset($folder)) {
        $link .= "f=$folder&";
    }
    if (isset($page)) {
        $link .= "p=$page&";
    }
    if (isset($action)) {
        $link .= "a=$action&";
    }
    if (isset($id)) {
        $link .= "id=$id&";
    }
    if (isset($csrfToken)) {
        $link .= "csrftoken=$csrfToken";
    }

    if ($folder !== $config["admin_section_name"] && $config["use_url_rewrite"]) {
        $link = str_replace("&", "", $link);
        $link = str_replace(["f=", "a=", "p=", "id="], "/", $link);
        $link = ltrim($link, "/");
    }
    else {
        if ($link !== "") {
            $link = "?".rtrim($link, "&");
        }
        $link = "index.php$link";
    }

    return $siteDirectory.$link;
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
    $menu = queryDB("SELECT * FROM pages WHERE parent_page_id IS NULL AND published = 1 ORDER BY menu_priority ASC")->fetchAll();

    foreach ($menu as $i => $parentPage) {
        $menu[$i]["children"] = queryDB("SELECT * FROM pages WHERE parent_page_id = ".$parentPage["id"]." AND published = 1 ORDER BY menu_priority ASC")->fetchAll();
    }

    return $menu;
}


function printTableSortButtons($table, $field = "id")
{
    global $pageName, $orderByTable, $orderByField, $orderDir, $siteDirectory;
    $ASC = "";
    $DESC = "";
    if ($table === $orderByTable && $field === $orderByField) {
        ${$orderDir} = "selected-sort-option";
    }

    return
    "<div class='table-sort-arrows'>
    <a class='$ASC' href='$siteDirectory?f=admin&p=$pageName&orderbytable=$table&orderbyfield=$field&orderdir=ASC'>&#9650</a>
    <a class='$DESC' href='$siteDirectory?f=admin&p=$pageName&orderbytable=$table&orderbyfield=$field&orderdir=DESC'>&#9660</a>
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

function checkSlugFormat($slug)
{
    if (preg_match("/^[a-z0-9-]{2,}$/", $slug) !== 1) {
        addError("The slug has the wrong format. Minimum 2 letters, numbers or hyphens.");
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
        "secret" => $config["recaptcha_secret"],
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

// --------------------------------------------------

function processContent($content) {
    $content = processShortcodes($content);
    $content = Michelf\Markdown::defaultTransform($content);
    return $content;
}

function processShortcodes($content) {
    global $siteDirectory, $config;

    $matches = [];
    preg_match_all("/link:(pages|posts|categories|medias):([a-z0-9-]+)/", $content, $matches);
    $processedShortcodes = [];

    foreach ($matches[0] as $id => $shortcode) {
        if (in_array($shortcode, $processedShortcodes)) {
            continue;
        }

        $processedShortcodes[] = $shortcode;
        $table = $matches[1][$id];
        $slug = $matches[2][$id]; // can be the actual slug or the id

        $resource = queryDB("SELECT * FROM $table WHERE slug=? OR id=?", [$slug, $slug])->fetch();
        if ($config["use_url_rewrite"]) {
            $slug = $resource["slug"];
        }
        else {
            $slug = $resource["id"];
        }

        if ($resource !== false) {
            $link = "";
            switch ($table) {
                case "pages":
                    $link = buildLink(null, $slug);
                    break;
                case "posts":
                    $link = buildLink("blog", $slug);
                    break;
                case "categories":
                    $link = buildLink("categories", $slug);
                    break;
                case "medias":
                    $link = $siteDirectory."uploads/".$resource["filename"];
                    break;
            }
            $content = str_replace($shortcode, $link, $content);
        }
    }

    return $content;
}

// --------------------------------------------------

function getUniqueToken()
{
    // don't use random_bytes() so that it work on PHP5.6 too
    $strong = true;
    $bytes = openssl_random_pseudo_bytes(20, $strong);
    return bin2hex($bytes); // 40 chars
}

function setCSRFTokens($requestName = "")
{
    $token = getUniqueToken();
    $_SESSION[$requestName."_csrf_token"] = $token;
    $_SESSION[$requestName."_csrf_time"] = time();
    return $token;
}

function addCSRFFormField($formName, $fieldName = "csrf_token") {
    $token = setCSRFTokens($formName);
    echo '<input type="hidden" name="'.$fieldName.'" value="'.$token.'">';
}

function verifyCSRFToken($requestToken, $requestName = "", $timeLimit = 900)
{
    // 900 sec = 15 min
    if (
        isset($_SESSION[$requestName."_csrf_token"]) &&
        $_SESSION[$requestName."_csrf_token"] === $requestToken &&
        isset($_SESSION[$requestName."_csrf_time"]) &&
        time() < $_SESSION[$requestName."_csrf_time"] + $timeLimit
    ) {
        unset($_SESSION[$requestName."_csrf_token"]);
        unset($_SESSION[$requestName."_csrf_time"]);
        return true;
    }

    addError("Wrong CSRF token for request $requestName");
    return false;
}
