<?php
declare(strict_types=1);

/**
 * Redirect the user toward the specified URL
 *
 * @param string|array $section
 * @param string $action
 * @param string $id
 * @param string $csrfToken
 */
function redirect($section = null, string $action = null, string $id = null, string $csrfToken = null): void
{
    saveMsgForLater();
    header("HTTP/1.0 301");

    $url = buildUrl($section, $action, $id, $csrfToken);
    header("Location: $url");
    exit;
}

/**
 * Build a URL from the params passed as argument
 *
 * @param string|array $section
 * @param string $action
 * @param string $id
 * @param string $csrfToken
 * @return string
 */
function buildUrl($section = null, string $action = null, string $id = null, string $csrfToken = null): string
{
    global $config, $site;

    if (! is_array($section)) {
        $array['section'] = $section;
        $array['action'] = $action;
        $array['id'] = $id;
        $array['csrfToken'] = $csrfToken;
        $section = $array;
    }

    $queryStr = "";
    // section is now an array
    foreach ($section as $key => $value) {
        if ($value !== null) {
            if ($key === 'section' && strpos($value, 'admin:') === 0) {
                $value = str_replace('admin', $config['adminSectionName'], $value);
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
function createTooltip(string $text): void
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
        // $query['orderDir'] is 'ASC' or 'DESC'
        ${ $query['orderDir'] } = "selected-sort-option";
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
    $emailPattern = "^[a-zA-Z0-9_\.+-]{1,}@[a-zA-Z0-9-_\.]{3,}$";

    if (preg_match("/$emailPattern/", $email) !== 1) {
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
function addError(string $msg): void
{
    global $errors;
    $errors[] = $msg;
}

/**
 * @param string $msg
 */
function addSuccess(string $msg): void
{
    global $successes;
    $successes[] = $msg;
}

/**
 * Save the error and success mesages in the database to be retrieved after the page load.
 * Typically called just before a redirection.
 */
function saveMsgForLater(): void
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

function populateMsg(): void
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
function setCSRFTokens(string $requestName = ""): string
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
function addCSRFFormField(string $formName, string $fieldName = "csrf_token"): void
{
    $token = setCSRFTokens($formName);
    echo '<input type="hidden" name="' . $fieldName . '" value="' . $token . '">';
}

/**
 * @param string $requestToken
 * @param string $requestName
 * @param int    $timeLimit
 * @return bool
 */
function verifyCSRFToken(string $requestToken, string $requestName = "", int $timeLimit = 900): bool
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

// --------------------------------------------------

/**
 * Echo the text using htmlspecialchars()
 * @param string $text
 */
function safeEcho(string $text): void
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