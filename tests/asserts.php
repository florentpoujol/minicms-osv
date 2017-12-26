<?php

function assertIdentical($expected, $actual)
{
    if ($expected !== $actual) {
        outputFailedTest(
            "Failed asserting that two values are identical:\n" .
            "Expected: $expected\nActual: $actual\n"
        );
    }
}

function assertStringContains(string $haystack, string $needle)
{
    if (strpos($haystack, $needle) === false) {
        outputFailedTest(
            "Failed asserting the haystack string below contains the substring '$needle'.\n$haystack"
        );
    }
}

/**
 * @param string|array  $actual
 */
function assertEmpty($actual)
{
    if (!empty($actual)) {
        $msg = "Failed asserting that the value is empty.";
        if (is_array($actual)) {
            $msg = "Failed asserting that array is empty. It has " . count($actual) . " elements";
        } elseif (is_string($actual)) {
            $msg = "Failed asserting that string '$actual' is empty.";
        }
        outputFailedTest($msg);
    }
}

function assertNotEmpty($actual)
{
    if (empty($actual)) {
        outputFailedTest("Failed asserting that the value is not empty.");
    }
}

function assertEmailContains(string $text)
{
    $content = @file_get_contents(__dir__ . "/email.txt");
    if ($content === false || strpos($content, $text) === false) {
        outputFailedTest("Failed asserting that the email has the following substring: '$text'\nEmail: $content\n");
    }
}

function deleteEmail()
{
    @unlink(__dir__ . "/email.txt");
}

/*function assertMessageSaved(string $text)
{
    global $testDb;
    $msg = $testDb->query("SELECT * FROM messages WHERE text = '$text'")->fetch();
    if ($msg === false) {
        outputFailedTest("Failed asserting that the message below is present in the database.\nMessage: '$text'\n");
    }
}*/
