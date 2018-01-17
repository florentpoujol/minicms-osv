<?php
// note it is important that this file run last, after all the other tests
// see test_install_success() function

function test_install_read()
{
    $content = loadInstallScript();
    assertStringContains($content, "<title>MiniCMS OSV Installer</title>");
    assertStringContains($content, "You are about to install MINICMS OSV.");
}

function test_install_wrong_form()
{
    $_POST["user_name"] = "azer ty";
    $_POST["user_email"] = "azerty@email";
    $_POST["user_password"] = "azerty";
    $_POST["user_password_confirm"] = "a";
    $_POST["config"] = [];

    $content = loadInstallScript();
    assertStringContains($content, "The name has the wrong format");
    assertStringContains($content, "The email has the wrong format");
    assertStringContains($content, "The password must be at least");
    assertStringContains($content, "The password confirmation does not match the password");
}

function test_install_wrong_database_connection()
{
    $_POST["user_name"] = "Florent";
    $_POST["user_email"] = "florent@email.fr";
    $_POST["user_password"] = "Az3rty";
    $_POST["user_password_confirm"] = "Az3rty";
    $config = [
        "db_host" => "localhost",
        "db_name" => "test_install_minicms_osv",
        "db_user" => "root",
        "db_password" => "not_the_root_password",
    ];
    $_POST["config"] = $config;

    $content = loadInstallScript();
    assertStringContains($content, "Error connecting to the database. Probably wrong host, username or password.");
}

function test_install_wrong_database_name()
{
    $_POST["user_name"] = "Florent";
    $_POST["user_email"] = "florent@email.fr";
    $_POST["user_password"] = "Az3rty";
    $_POST["user_password_confirm"] = "Az3rty";

    $testConfig = json_decode(file_get_contents(__dir__ . "/config.json"), true);
    $config = [
        "db_host" => $testConfig["db_host"],
        "db_name" => "d",
        "db_user" => $testConfig["db_user"],
        "db_password" => $testConfig["db_password"],
    ];
    $_POST["config"] = $config;

    $content = loadInstallScript();
    assertStringContains($content, "The database name has the wrong format");
}

function test_install_success()
{
    $_POST["user_name"] = "Florent";
    $_POST["user_email"] = "florent@email.fr";
    $_POST["user_password"] = "Az3rty";
    $_POST["user_password_confirm"] = "Az3rty";

    $testConfig = json_decode(file_get_contents(__dir__ . "/config.json"), true);
    $config = [
        "db_host" => $testConfig["db_host"],
        "db_name" => "test_install_minicms_osv",
        "db_user" => $testConfig["db_user"],
        "db_password" => $testConfig["db_password"],
    ];
    $_POST["config"] = $config;

    loadInstallScript();

    assertRedirect(buildUrl("login"));
    global $testDb, $db;
    $testDb = $db; // needed so that assertMessageSaved() can query the right DB (the new one an not the one used for the tests)
    assertMessageSaved("Congratulation, the site is now installed, you can login and start creating content. Take a look at the config page for more configuration options.");
}

function test_cleanup_config_install_file()
{
    $path = __dir__ . "/../app/config.json.testinstall";
    if (file_exists($path)) {
        unlink($path);
    }
}
