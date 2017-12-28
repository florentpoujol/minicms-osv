<?php

function assertIdentical($expected, $actual)
{
    if ($expected !== $actual) {
        outputFailedTest(
            "Failed asserting that two values are identical:\n" .
            "Expected: $expected (" . gettype($expected) . ")\nActual: $actual (" . gettype($actual) . ")\n"
        );
    }
}

function assertDifferent($value1, $value2)
{
    if ($value1 == $value2) {
        outputFailedTest(
            "Failed asserting that two values are different:\n" .
            "Expected: $value1\nActual: $value2\n"
        );
    }
}

function assertArrayHasKey(array $array, $key)
{
    if (!isset($array[$key])) {
        outputFailedTest("Failed asserting the array has key '$key'");
    }
}

function assertStringContains(string $haystack, string $needle)
{
    if (strpos($haystack, $needle) === false) {
        outputFailedTest(
            "Failed asserting the haystack string below contains the substring '$needle'.\nHaystack:\n$haystack\n"
        );
    }
}

/**
 * @param string|array $actual
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

// email

$testEmailContent = "";
function sendEmail(string $to, string $subject, string $body): bool
{
    global $testEmailContent;
    $testEmailContent = "$to\n$subject\n$body";
    return true;
}

function assertEmailContains(string $text)
{
    global $testEmailContent;
    if (strpos($testEmailContent, $text) === false) {
        outputFailedTest("Failed asserting that the email has the following substring: '$text'\nEmail:\n$testEmailContent\n");
    }
}

function deleteEmail()
{
    global $testEmailContent;
    $testEmailContent = "";
}

// redirect

$testRedirectUrl = "";
function assertRedirect(string $url)
{
    global $testRedirectUrl;
    if ($testRedirectUrl !== $url) {
        $tmp = $testRedirectUrl;
        $testRedirectUrl = "";
        outputFailedTest("Failed asserting that the redirect URL is correct.\nExpected: '$url'.\nActual:   '$tmp'.");
    }
    $testRedirectUrl = "";
}

// used when we are getting an un expected empty content
function assertNoRedirect()
{
    global $testRedirectUrl;
    if (!empty($testRedirectUrl)) {
        $tmp = $testRedirectUrl;
        $testRedirectUrl = "";
        outputFailedTest("Failed asserting that there is no redirect.\nRedirect URL: '$tmp'.");
    }
}

function redirect($section = null, string $action = null, string $id = null, string $csrfToken = null)
{
    saveMsgForLater();
    global $testRedirectUrl;
    $testRedirectUrl = buildUrl($section, $action, $id, $csrfToken);
}
// redirect does not use exit anymore which means
// that all pages that really relies on it to stop the execution after a redirect
// needs instead to use a return after the call to redirect()...





function assertMessageSaved(string $text)
{
    global $db;
    $msg = $db->query("SELECT * FROM messages WHERE text = '$text'")->fetch();
    if ($msg === false) {
        outputFailedTest("Failed asserting that the message below is present in the database.\nMessage: '$text'\n");
    }
}
