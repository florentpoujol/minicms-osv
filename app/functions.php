<?php

/**
 * Logout the user by destroying the session cookie, the session itself
 * and redirecting it to the index
 */
function logout()
{
    setcookie(session_name(), null, 1); // destroy session cookie
    session_destroy();
    header("Location: index.php");
    exit;
}

/**
 * Run the specified query with the specified data against the database
 *
 * @param mixed $data
 * @return bool|PDOStatement
 */
function queryDB(string $strQuery, $data = null, bool $getSuccess = false)
{
    global $db;
    $query = $db->prepare($strQuery);

    if ($data === null) {
        $success = $query->execute();
    } else {
        if (! is_array($data)) {
            $data = [$data];
        }
        $success = $query->execute($data);
    }

    if ($getSuccess) {
        return $success;
    }
    return $query;
}

$httpResponseCode = -1;
function setHTTPResponseCode(int $code)
{
    global $httpResponseCode;
    $httpResponseCode = $code;
    header($_SERVER["SERVER_PROTOCOL"] . " $code");
}

if (!IS_TEST) {
    /**
     * @param string|array $section
     */
    function redirect($section = null, string $action = null, string $id = null, string $csrfToken = null)
    {
        global $httpResponseCode;
        saveMsgForLater();
        $url = buildUrl($section, $action, $id, $csrfToken);
        if ($httpResponseCode !== -1) {
            header("Location: $url", true, $httpResponseCode);
            exit;
        }
        header("Location: $url");
        exit;
    }
}

/**
 * Build a URL from the params passed as argument
 * Url is relative to the site directory
 *
 * @param string|array $section
 */
function buildUrl($section = null, string $action = null, string $id = null, string $csrfToken = null): string
{
    global $config, $site;

    if (! is_array($section)) {
        $array['section'] = $section;
        $array['action'] = $action;
        $array['id'] = $id;
        $array['csrftoken'] = $csrfToken;
        $section = $array;
    }

    $queryStr = "";
    foreach ($section as $key => $value) {
        if ($value !== null) {
            if ($key === 'section' && strpos($value, 'admin') === 0) {
                $value = str_replace('admin', $config['admin_section_name'], $value);
            }
            $queryStr .= "$key=$value&";
        }
    }

    if (
        (! isset($section['csrfToken']) || $section['csrfToken'] === null) &&
        $config["use_url_rewrite"]
    ) {
        $queryStr = str_replace("&", "", $queryStr);
        $queryStr = str_replace(["section=", "action=", "id="], "/", $queryStr);
        $queryStr = ltrim($queryStr, "/");
    } else {
        // @todo: allow to use csrf token with url rewrite
        if ($queryStr !== "") {
            $queryStr = "?" . rtrim($queryStr, "&");
        }
        $queryStr = "index.php" . $queryStr;
    }

    return $site['directory'] . $queryStr;
}

/**
 * Get the file's extension
 *
 * @param string $path
 * @return string
 */
function getExtension(string $path): string
{
    return pathinfo($path, PATHINFO_EXTENSION);
}

/**
 * Tell whether the provided file's path is an image
 *
 * @param string $path
 * @return bool
 */
function isImage(string $path): bool
{
    $ext = getExtension($path);
    return ($ext === "jpg" || $ext === "jpeg" || $ext === "png");
}

/**
 * Echo the HTML span of a tooltip
 *
 * @param string $text
 */
function createTooltip(string $text)
{
    echo '<span class="tooltip"><span class="icon">?</span><span class="text">' . $text . '</span></span>';
}

/**
 * Build the menu hierarchy as an array
 *
 * @return array
 */
function buildMenuHierarchy(): array
{
    $menu = queryDB(
        "SELECT * FROM pages WHERE parent_page_id IS NULL AND published = 1 ORDER BY menu_priority ASC"
    )->fetchAll();

    foreach ($menu as $i => $parentPage) {
        $menu[$i]["children"] = queryDB(
            "SELECT * FROM pages WHERE parent_page_id = ? AND published = 1 ORDER BY menu_priority ASC",
            $parentPage['id']
        )->fetchAll();
    }

    return $menu;
}

/**
 * @param array $menuItems
 * @return string|array
 */
function getMenuHomepage(array $menuItems)
{
    foreach ($menuItems as $id => $item) {
        if ($item["type"] === "homepage") {
            return $item["target"];
        } elseif (isset($item["children"]) && count($item["children"]) > 0) {
            $homepage = getMenuHomepage($item["children"]);
            if (is_string($homepage)) {
                return $homepage;
            }
        }
    }
    return null; // should not happens
}

function cleanMenuStructure(array &$array)
{
    // do not make local copies !
    for ($i = count($array)-1; $i >= 0; $i--) {
        if (isset($array[$i]["children"])) {
            cleanMenuStructure($array[$i]["children"]);
        }

        if (trim($array[$i]["name"]) === "" && trim($array[$i]["target"]) === "") {
            unset($array[$i]);
        }
    }
}

/**
 * Return the HTML for the sort buttons
 *
 * @param string $table
 * @param string $field
 * @return string
 */
function getTableSortButtons(string $table, string $field = "id"): string
{
    global $query;
    $ASC = "";
    $DESC = "";
    if ($table === $query['orderbytable'] && $field === $query['orderbyfield']) {
        // $query['orderdir'] is 'ASC' or 'DESC'
        ${ $query['orderdir'] } = "selected-sort-option";
    }

    $_query = $query;
    $_query['orderbytable'] = $table;
    $_query['orderbyfield'] = $field;

    $_query['orderdir'] = 'ASC';
    $ascUrl = buildUrl($_query);
    $_query['orderdir'] = 'DESC';
    $descUrl = buildUrl($_query);

    return
    "<div class='table-sort-arrows'>
    <a class='$ASC' href='$ascUrl'>&#9650</a>
    <a class='$DESC' href='$descUrl'>&#9660</a>
    </div>";
}

// --------------------------------------------------

/**
 * Match a subject to an array of regexes.
 * Returns 1 only if subject match all of the regexes.
 *
 * @param array  $patterns
 * @param string $subject
 * @return bool
 */
function pregMatches(array $patterns, string $subject): bool
{
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $subject) !== 1) {
            return false;
        }
    }
    return true;
}

/**
 * @param string $title
 * @return bool
 */
function checkPageTitleFormat(string $title): bool
{
    $minTitleLength = 4;
    if (strlen($title) < $minTitleLength) {
        addError("The title must be at least $minTitleLength characters long.");
        return false;
    }
    return true;
}

/**
 * @param string $slug
 * @return bool
 */
function checkSlugFormat(string $slug): bool
{
    if (preg_match("/^[a-z][a-z0-9-]{2,}$/", $slug) !== 1) {
        // must begin by a letter; it prevents slug to may be considered as numeric
        addError("The slug has the wrong format. Minimum 2 letters, numbers or hyphens.");
        return false;
    }
    return true;
}

/**
 * @param string $name
 * @return bool
 */
function checkNameFormat(string $name): bool
{
    if (preg_match("/^[a-zA-Z0-9_-]{4,}$/", $name) !== 1) {
        addError("The name has the wrong format. Minimum four letters, numbers, hyphens or underscores. No Spaces.");
        return false;
    }
    return true;
}

/**
 * @param string $email
 * @return bool
 */
function checkEmailFormat(string $email): bool
{
    $emailPattern = "^[a-z0-9_\.+-]+@[a-z0-9-_\.]+\.[a-z]+$";

    if (preg_match("/$emailPattern/i", $email) !== 1) {
        addError("The email has the wrong format.");
        return false;
    }
    return true;
}


/**
 * @param string      $password
 * @param string|null $passwordConfirm
 * @return bool
 */
function checkPasswordFormat(string $password, string $passwordConfirm = null): bool
{
    $patterns = ["/[A-Z]+/", "/[a-z]+/", "/[0-9]+/"];
    $minPasswordLength = 3;
    $ok = true;

    if (! pregMatches($patterns, $password) || strlen($password) < $minPasswordLength) {
        addError("The password must be at least $minPasswordLength characters long and have at least one lowercase letter, one uppercase letter and one number.");
        $ok = false;
    }

    if (isset($passwordConfirm) && $password !== $passwordConfirm) {
        addError("The password confirmation does not match the password.");
        $ok = false;
    }

    return $ok;
}

/**
 * @param array $newUser
 * @return bool
 */
function checkNewUserData(array $newUser): bool
{
    if (! checkUserData($newUser)) {
        return false;
    }

    $user = queryDB(
        "SELECT id FROM users WHERE name = ? OR email = ?",
        [$newUser["name"], $newUser["email"]]
    )->fetch();

    if (is_array($user)) {
        addError("A user already exists with that name or email.");
        return false;
    }
    return true;
}

/**
 * @param array $user
 * @return bool
 */
function checkUserData(array $user): bool
{
    $userOK = checkNameFormat($user["name"]);
    $userOK = checkEmailFormat($user["email"]) && $userOK;

    if (isset($user["password"]) && $user["password"] !== "") {
        if (! isset($user["password_confirm"])) {
            $user["password_confirm"] = null;
        }

        $userOK = checkPasswordFormat($user["password"], $user["password_confirm"]) && $userOK;
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

/**
 * @param string $token
 * @return bool
 */
function checkToken(string $token): bool
{
    // we suppose the token here has been generated by getRandomString() below
    // the string is thus only composed of lowercase hexadecimal chars
    if (preg_match("/^[a-fA-F0-9]+$/", $token) !== 1) {
        addError("The token has the wrong format.");
        return false;
    }
    return true;
}

// --------------------------------------------------

/**
 * @param string $userResponse Value that comes from the 'g-recaptcha-response' POST key
 * @return bool
 */
function verifyRecaptcha(string $userResponse): bool
{
    global $config;
    $params = [
        "secret" => $config["recaptcha_secret"],
        "response" => $userResponse, // note that the value comes right from POST and is not checked for type or value
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

/**
 * @param string $msg
 */
function addError(string $msg)
{
    global $errors;
    $errors[] = $msg;
}

/**
 * @param string $msg
 */
function addSuccess(string $msg)
{
    global $successes;
    $successes[] = $msg;
}

/**
 * Save the error and success mesages in the database to be retrieved after the page load.
 * Typically called just before a redirection.
 */
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

function populateMsg()
{
    global $errors, $successes;
    $sessionId = session_id();

    $msgs = queryDB("SELECT text, type FROM messages WHERE session_id = ?", $sessionId);
    while ($msg = $msgs->fetch()) {
        if ($msg['type'] === 'error') {
            $errors[] = $msg["text"];
        } elseif ($msg['type'] === 'success') {
            $successes[] = $msg["text"];
        }
    }

    queryDB("DELETE FROM messages WHERE session_id = ?", $sessionId);
}

// --------------------------------------------------

/**
 * Process shortcodes and markdown in the content
 *
 * @param string $content
 * @return string
 */
function processContent(string $content): string
{
    $content = processShortcodes($content);
    return Michelf\Markdown::defaultTransform($content);
}

/**
 * @param string $content
 * @return string
 */
function processShortcodes(string $content): string
{
    global $config, $site;

    $matches = [];
    preg_match_all("/link:(page|post|category|media):([a-z0-9-]+)/", $content, $matches);
    $processedShortcodes = [];

    foreach ($matches[0] as $id => $shortcode) {
        if (in_array($shortcode, $processedShortcodes)) {
            continue;
        }
        $processedShortcodes[] = $shortcode;

        $table = $matches[1][$id];
        $tablePlural = $table . 's';
        if ($tablePlural === 'categorys') {
            $tablePlural = 'categories';
        }

        $slugOrId = $matches[2][$id];

        $field = 'id';
        if (! is_numeric($slugOrId)) {
            $field = 'slug';
        }

        $resource = queryDB("SELECT * FROM $tablePlural WHERE $field = ?", $slugOrId)->fetch();
        if ($resource !== false) {
            if ($config["use_url_rewrite"]) {
                $slugOrId = $resource["slug"];
            } else {
                $slugOrId = $resource["id"];
            }

            if ($table === 'media') {
                $url = $site['directory'] . "uploads/" . $resource["filename"];
            } else {
                $url = buildUrl($table, null, $slugOrId);
            }

            $content = str_replace($shortcode, $url, $content);
        }
    }

    return $content;
}

// --------------------------------------------------

/**
 * Returns a crypto random string of length size.
 *
 * @param int $length
 * @return string
 */
function getRandomString(int $length = 40): string
{
    return bin2hex( random_bytes($length / 2) );
}

/**
 * Add a CSRF token in session and returns it
 * @param string $requestName
 * @return string
 */
function setCSRFToken(string $requestName = ""): string
{
    // @todo allow to have several csrf tokens per user
    $token = getRandomString();
    $_SESSION[$requestName . "_csrf_token"] = $token;
    $_SESSION[$requestName . "_csrf_time"] = time();
    return $token;
}

/**
 * Echo the HTML of the hidden input field with the csrf token
 * @param string $formName
 * @param string $fieldName
 */
function addCSRFFormField(string $formName, string $fieldName = "csrf_token")
{
    $token = setCSRFToken($formName);
    echo '<input type="hidden" name="' . $fieldName . '" value="' . $token . '">';
}

/**
 * @param string $requestToken
 * @param string $requestName
 * @param int    $timeLimit
 * @return bool
 */
function verifyCSRFToken(string $requestToken, string $requestName, int $timeLimit = 900): bool
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

    addError("Wrong CSRF token for request '$requestName'");
    return false;
}

// --------------------------------------------------

/**
 * Echo the text using htmlspecialchars()
 * @param string $text
 */
function safeEcho(string $text)
{
    echo htmlspecialchars($text);
}

/**
 * Returns either the id of the provided resource, or its slug (safe for echoing) when URl rewrite is used.
 *
 * @param array $resource
 * @return string
 */
function idOrSlug(array $resource): string
{
    global $config;
    return htmlspecialchars($config["use_url_rewrite"] ? $resource["slug"] : $resource["id"]);
}


if (! IS_TEST) {
    function moveUploadedFile(string $src, string $dest): bool
    {
        return move_uploaded_file($src, $dest);
    }
}