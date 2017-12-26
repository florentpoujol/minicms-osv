<?php
require_once __dir__ . "/../includes/phpmailer/class.smtp.php";
require_once __dir__ . "/../includes/phpmailer/class.phpmailer.php";

if (!IS_TEST) {
    /**
     * Send an email. Return success or failure
     *
     * @param string $to Destination address
     * @param string $subject
     * @param string $body
     * @return bool
     */
    function sendEmail(string $to, string $subject, string $body): bool
    {
        global $config;

        if ($config["smtp_host"] === "") {
            $mail = new PHPmailer;
            // $mail->SMTPDebug = 3;                              // Enable verbose debug output

            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = $config["smtp_host"];  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $config["smtp_user"];               // SMTP username
            $mail->Password = $config["smtp_password"];           // SMTP password
            $mail->SMTPSecure = "tls";                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $config["smtp_port"];                   // TCP port to connect to

            $mail->setFrom($config["mailer_from_address"], $config["mailer_from_name"]);
            $mail->addAddress($to);     // Add a recipient
            $mail->isHTML(true);                                  // Set email format to HTML

            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $body;

            if (!$mail->send()) {
                addError("Email wasn't sent. <br> Mailer Error: " . $mail->ErrorInfo);
                return false;
            }
        } else {
            $headers = "MIME-Version: 1.0 \r\n";
            $headers .= "Content-type: text/html; charset=utf-8 \r\n";
            $headers .= "From: " . $config["mailer_from_name"] . " <" . $config["mailer_from_address"] . "> \r\n";
            $headers .= "Reply-To: " . $config["mailer_from_address"] . " \r\n";

            if (!mail($to, $subject, $body, $headers)) {
                addError("Error: email wasn't sent.");
                return false;
            }
        }

        return true;
    }
}

/**
 * @param string $email
 * @param string $id
 * @param string $token
 * @return bool
 */
function sendConfirmEmail(string $email, string $id, string $token): bool
{
    global $site;

    $subject = "Confirm your email address";
    $body = "You have registered or changed your email address on the site. <br> Please click the link below to verify the email adress. <br><br>";
    $link = $site["domainUrl"] . buildUrl([
        "section" =>  "register",
        "action" => "confirmemail",
        "id" => $id,
        "token" => $token
    ]);
    $body .= "<a href='$link'>$link</a>";

    return sendEmail($email, $subject, $body);
}

/**
 * @param string $email
 * @param string $id
 * @param string $token
 * @return bool
 */
function sendChangePasswordEmail(string $email, string $id,  string $token): bool
{
    global $site;

    $subject = "Change your password";
    $body = "You have requested to change your password. <br> Click the link below within 48 hours to access the form.<br>";
    $link = $site["domainUrl"] . buildUrl([
        "section" =>  "login",
        "action" => "changepassword",
        "id" => $id,
        "token" => $token
    ]);
    $body .= "<a href='$link'>$link</a>";

    return sendEmail($email, $subject, $body);
}

/**
 * @param string $email
 * @return bool
 */
function sendTestEmail(string $email): bool
{
    $subject = "Test Email";
    $body = "This is a test email from the Mini CMS Vanilla.";

    return sendEmail($email, $subject, $body);
}
