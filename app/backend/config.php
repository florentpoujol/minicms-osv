<?php
if (! $isUserAdmin) {
    redirect();
}

$title = "Config";
require_once "header.php";
?>

<h1>Configuration</h1>

<?php
$configData = $config;

if (isset($_POST["site_title"])) {
    $newData = [];
    $dataOK = true;

    foreach ($config as $key => $oldValue) {
        if (isset($_POST[$key])) {
            $newValue = $_POST[$key];

            if ($key === "use_url_rewrite" || $key === "allow_comments") {
                $newData[$key] = 1;
            }
            elseif ($key === "mailer_from_address" && ! checkEmailFormat($newValue)) {
                $dataOK = false;
            }
            elseif (substr($key, 0, 3) === "db_" && $key !== "db_password" && strlen($newValue) < 3) {
                addError("The field '$key' is too short (mini 3 chars long)");
                $dataOK = false;
            }
            elseif ($key === "smtp_port") {
                $newData[$key] = (int)$newValue;
            }
            else {
                if (strstr($key, "password") !== false && trim($newValue) === "") {
                    continue;
                }
                $newData[$key] = $newValue;
            }
        }
        elseif ($key === "use_url_rewrite" || $key === "allow_comments") {
            $newData[$key] = 0;
        }
    }

    if ($dataOK) {
        $configPath = "../../app/config.php";
        $str = file_get_contents($configPath);

        if (is_string($str) === true) {
            foreach ($newData as $key => $value) {
                if (is_int($value)) {
                    $str = preg_replace(
                        '/("'.$key.'" => )[0-9]+(,?)/i',
                        '${1}'.$value.',',
                        $str
                    );
                }
                else {
                    $str = preg_replace(
                        '/("'.$key.'" => ")[^"]*"(,?)/i',
                        '${1}'.$value.'",',
                        $str
                    );
                }
            }

            if (file_put_contents($configPath, $str)) {
                addSuccess("config file written successfully");
                redirect(["p" => "config"]);
            }
            else {
                addError("Couldn't write config file");
            }
        }
        else {
            addError("could not read config file");
        }
    }
}
?>

<?php include "../../app/messages.php"; ?>

<form action="?p=config" method="post">
    <h3>Site</h3>

    <label>Website title: <input type="text" name="site_title" value="<?php echo $configData["site_title"]; ?>"></label> <br>
    <br>

    <label>Use URL rewrite:
        <input type="checkbox" name="use_url_rewrite" <?php echo ($configData["use_url_rewrite"] === 0 ? null : "checked"); ?>>
    </label>
    <?php createTooltip("Use the 'url name' of each pages as their URL instead of 'index.php?q=[the page id]'"); ?> <br>
    <br>

    <label>Allow comments on pages: <input type="checkbox" name="allow_comments" <?php echo ($configData["allow_comments"] === 0 ? null : "checked"); ?>>
    </label><br>
    <br>

    <label>Recaptcha Secret: <input type="text" name="recaptcha_secret" value="<?php echo $configData["recaptcha_secret"]; ?>"></label> The secret key that you find in your Recaptcha's dashboard. No antispam method is used when empty.<br>
    <br>


    <h3>Email</h3>

    <label>Mailer From Address: <input type="email" name="mailer_from_address" value="<?php echo $configData["mailer_from_address"]; ?>" required></label> <br>
    <br>
    <label>Mailer From Name: <input type="text" name="mailer_from_name" value="<?php echo $configData["mailer_from_name"]; ?>"></label> <br>
    <br>

    <label>SMTP Host: <input type="text" name="smtp_host" value="<?php echo $configData["smtp_host"]; ?>"></label> If empty, PHP's mail() function will be used to send emails instead.<br>
    <br>
    <label>SMTP user: <input type="text" name="smtp_user" value="<?php echo $configData["smtp_user"]; ?>"></label> <br>
    <br>
    <label>SMTP password: <input type="password" name="smtp_password"></label> Will only be updated when filled.<br>
    <br>
    <label>SMTP port: <input type="number" name="smtp_port" value="<?php echo $configData["smtp_port"]; ?>"></label> <br>
    <br>


    <h3>Databbase</h3>

    <p>
        Warning ! <br>
        Making a mistake when updating any of the fields below WILL render the site inaccessible. <br>
        You will have to fix the mistake by opening the file config file directly on the server.
    </p>

    <label>Host: <input type="text" name="db_host" value="<?php echo $configData["db_host"]; ?>" required></label> <br>
    <br>
    <label>Database name: <input type="text" name="db_name" value="<?php echo $configData["db_name"]; ?>" required></label> <br>
    <br>
    <label>User: <input type="text" name="db_user" value="<?php echo $configData["db_user"]; ?>" required></label> <br>
    <br>
    <label>Password: <input type="password" name="db_password"></label> Will only be updated when filled.<br>
    <br>

    <input type="submit" value="Update configuration">
</form>
