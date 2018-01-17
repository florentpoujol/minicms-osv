<?php

$appFolder = __dir__ . "/../../app";
$sampleConfig = file_get_contents("$appFolder/config.sample.json");
$sampleConfig = json_decode($sampleConfig, true);

function test_config_setup_file()
{
    global $appFolder;
    if (file_exists("$appFolder/config.testconfig.json")) {
        unlink("$appFolder/config.testconfig.json");
    }
    copy("$appFolder/config.sample.json", "$appFolder/config.testconfig.json");
}

function test_config_not_for_writers()
{
    $user = getUser("writer");
    $token = setTestCSRFToken("gotoconfig");
    loadSite("section=admin:config&csrftoken=$token", $user["id"]);
    assertRedirect(buildUrl("admin:users", "read"));
}

function test_config_not_for_commenters()
{
    $user = getUser("commenter");
    $token = setTestCSRFToken("gotoconfig");
    loadSite("section=admin:config&csrftoken=$token", $user["id"]);
    assertRedirect(buildUrl("admin:users", "read"));
}

function test_config_read()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("gotoconfig");
    $content = loadSite("section=admin:config&csrftoken=$token", $admin["id"]);

    global $sampleConfig;
    assertStringContains($content, "<h1>Configuration</h1>");
    assertStringContains($content, $sampleConfig["site_title"]);
}

function test_config_test_email()
{
    $_POST["test_email_submit_button_clicked"] = ""; // set when the submit button is cliked
    $_POST["test_email_address"] = "test@email.fr";
    $_POST["configtestemail_csrf_token"] = setTestCSRFToken("configtestemail");

    $admin = getUser("admin");
    $token = setTestCSRFToken("gotoconfig");
    $content = loadSite("section=admin:config&csrftoken=$token", $admin["id"]);

    assertStringContains($content, "Test email sent.");
    assertEmailContains("Test Email");
    assertEmailContains("This is a test email from the Mini CMS OSV");
    assertEmailContains("test@email.fr");
    deleteEmail();
}

function test_config_wrong_form()
{
    $_POST["site_title"] = "My test site";
    $_POST["mailer_from_address"] = "mailer@email";
    $_POST["db_host"] = "";
    $_POST["db_name"] = "";
    $_POST["db_user"] = "";
    $_POST["admin_section_name"] = "admin section";

    $admin = getUser("admin");
    $token = setTestCSRFToken("gotoconfig");
    $content = loadSite("section=admin:config&csrftoken=$token", $admin["id"]);

    assertStringContains($content, "The email has the wrong format");
    assertStringContains($content, "The field 'db_host' is too short (mini 3 chars long)");
    assertStringContains($content, "The field 'db_name' is too short (mini 3 chars long)");
    assertStringContains($content, "The field 'db_user' is too short (mini 3 chars long)");
    assertStringContains($content, "The admin section name has the wrong format");
}

function test_config_success()
{
    global $sampleConfig, $appFolder;

    $_POST["db_host"] = "test_config";
    $_POST["db_name"] = "test_config";
    $_POST["db_user"] = "test_config";
    $_POST["db_password"] = "test_config";

    $_POST["mailer_from_address"] = "test@config.fr";
    $_POST["mailer_from_name"] = "test_config";
    $_POST["smtp_host"] = "test_config";
    $_POST["smtp_user"] = "test_config";
    $_POST["smtp_password"] = "test_config";
    $_POST["smtp_port"] = 1337;

    $_POST["site_title"] = "test_config";
    $_POST["recaptcha_secret"] = "test_config";
    $_POST["use_url_rewrite"] = "";
    // do not set allow_comments and allow_registration so that they get turned to false
    $_POST["admin_section_name"] = "test-config";

    assertDifferent($sampleConfig["db_host"], "test_config");
    assertDifferent($sampleConfig["mailer_from_name"], "test_config");
    assertDifferent($sampleConfig["smtp_port"], 1337);
    assertDifferent($sampleConfig["use_url_rewrite"], true);

    $admin = getUser("admin");
    $token = setTestCSRFToken("gotoconfig");
    setTestCSRFToken("updateconfig"); // both tokens are needed

    loadSite("section=admin:config&csrftoken=$token", $admin["id"]);
    // echo $content;
    // printSavedMessages();
    assertMessageSaved("Config file written successfully.");
    // can't check the url since I don't know the value of the "gotoconfig" csrf token

    $newConfig = json_decode(file_get_contents("$appFolder/config.testconfig.json"), true);
    assertIdentical("test_config", $newConfig["db_host"]);
    assertIdentical("test_config", $newConfig["db_name"]);
    assertIdentical("test_config", $newConfig["db_user"]);
    assertIdentical("test_config", $newConfig["db_password"]);
    assertIdentical("test@config.fr", $newConfig["mailer_from_address"]);
    assertIdentical("test_config", $newConfig["mailer_from_name"]);
    assertIdentical("test_config", $newConfig["smtp_host"]);
    assertIdentical("test_config", $newConfig["smtp_user"]);
    assertIdentical("test_config", $newConfig["smtp_password"]);
    assertIdentical(1337, $newConfig["smtp_port"]);
    assertIdentical("test_config", $newConfig["site_title"]);
    assertIdentical("test_config", $newConfig["recaptcha_secret"]);
    assertIdentical(true, $newConfig["use_url_rewrite"]);
    assertIdentical(false, $newConfig["allow_comments"]);
    assertIdentical(false, $newConfig["allow_registration"]);
}

function test_config_cleanup_file()
{
    global $appFolder;
    if (file_exists("$appFolder/config.testconfig.json")) {
        unlink("$appFolder/config.testconfig.json");
    }
}
