<?php
if (! $isUserAdmin || ! verifyCSRFToken($csrfToken, "gotoconfig")) {
    redirect($folder);
}

$title = "Config";
require_once "header.php";
?>

<h1>Configuration</h1>

<?php
if (! is_writable("../app/config.json")) {
    addError("The config file is not writable.");
}

$testEmailAddress = "";
if (isset($_POST["test_email_params"]) && verifyCSRFToken($_POST["configtestemail_csrf_token"], "configtestemail")) {
    $testEmailAddress = $_POST["test_email_address"];
    if (checkEmailFormat($testEmailAddress) && sendTestEmail($testEmailAddress)) {
        addSuccess("email sent");
    }
}

$configData = $config;

if (isset($_POST["site_title"]) && ! isset($_POST["test_email_params"])) {
    $dataOK = true;

    foreach ($config as $key => $oldValue) {
        if (isset($_POST[$key])) {
            $newValue = $_POST[$key];

            switch ($key) {
                case "use_url_rewrite":
                case "allow_comments":
                case "allow_registration":
                    $configData[$key] = true;
                    break;

                case "mailer_from_address":
                    if (! checkEmailFormat($newValue)) {
                        $dataOK = false;
                    }
                    $configData[$key] = $newValue;
                    break;

                case "db_host":
                case "db_name":
                case "db_user":
                    if (strlen($newValue) < 3) {
                        addError("The field '$key' is too short (mini 3 chars long)");
                        $dataOK = false;
                    }
                    $configData[$key] = $newValue;
                    break;

                case "smtp_port":
                    $configData[$key] = (int)$newValue;
                    break;

                case "admin_section_name":
                    if (trim($newValue) === "") {
                        $configData[$key] = "admin";
                    }
                    if (! checkSlugFormat($newValue)) {
                        addError("The admin section name has the wrong format");
                        $dataOK = false;
                    }
                    $configData[$key] = $newValue;
                    break;

                default:
                    $configData[$key] = $newValue;
                    break;
            }
        }
        elseif ($key === "use_url_rewrite" || $key === "allow_comments" || $key === "allow_registration") {
            $configData[$key] = false;
        }
    }

    if ($dataOK && verifyCSRFToken($_POST["csrf_token"], "configedit")) {
        $configStr = json_encode($configData, JSON_PRETTY_PRINT);
        if (file_put_contents("../app/config.json", $configStr)) {
            addSuccess("config file written successfully");
            redirect($configData["admin_section_name"], "config", null, null, $goToConfigCSRFToken);
        }
        else {
            addError("Couldn't write config file");
        }
    }
}
?>

<?php include "../app/messages.php"; ?>

<p>
    You can also edit the config file manually.
</p>

<form action="<?php echo buildLink($folder, $pageName, null, null, $goToConfigCSRFToken); ?>" method="post">
    <input type="submit" value="Update configuration">

    <h3>Site</h3>

    <label>Website title: <input type="text" name="site_title" value="<?php echo $configData["site_title"]; ?>"></label> <br>
    <br>

    <label>Use URL rewrite:
        <input type="checkbox" name="use_url_rewrite" <?php echo ($configData["use_url_rewrite"] ? "checked" : null); ?>>
    </label>
    <?php createTooltip("Use the 'slug' of each pages as their URL instead of 'index.php?q=[the page id]'"); ?> <br>
    <br>

    <label>Allow comments on pages: <input type="checkbox" name="allow_comments" <?php echo ($configData["allow_comments"] ? "checked" : null); ?>>
    </label><br>
    <br>

    <label>Allow regsitration of new users: <input type="checkbox" name="allow_regsitration" <?php echo ($configData["allow_registration"] ? "checked" : null); ?>>
    </label> Doesn't prevent to add new users via the admin panel when disabled. <br>
    <br>

    <label>Recaptcha Secret: <input type="text" name="recaptcha_secret" value="<?php echo $configData["recaptcha_secret"]; ?>"></label> The secret key that you find in your Recaptcha's dashboard. No antispam method is used when empty.<br>
    <br>

    <label>Admin Section Name: <input type="text" name="admin_section_name" value="<?php echo $configData["admin_section_name"]; ?>" required></label> For security reasons, it is best to change it to anything else than "admin" (only letters, numbers, hyphens)<br>
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
    <label>SMTP password: <input type="password" name="smtp_password" value="<?php echo $configData["smtp_password"]; ?>"></label> <br>
    <br>
    <label>SMTP port: <input type="number" name="smtp_port" value="<?php echo $configData["smtp_port"]; ?>"></label> <br>
    <br>

    After having saved the config : <br>
    <input type="email" name="test_email_address" value="<?php echo $testEmailAddress; ?>">
    <?php addCSRFFormField("configtestemail", "configtestemail_csrf_token"); ?>
    <input type="submit" name="test_email_params" value="Test sending of email"> <br>
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
    <label>Password: <input type="password" name="db_password" value="<?php echo $configData["db_password"]; ?>"></label> <br>
    <br>

    <?php addCSRFFormField("configedit"); ?>
    <input type="submit" value="Update configuration">
</form>
