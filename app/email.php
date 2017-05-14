<?php
require_once "/../phpmailer/class.smtp.php";
require_once "/../phpmailer/class.phpmailer.php";
// note: the leading slashes are mandatory here
// otherwise, the include path would be considered differents
// when included from /public/index.php or /public/admin/index.php

function sendEmail($to, $subject, $body)
{
    global $config;

    if ($config["smtp_host"] === "") {
        $headers = "MIME-Version: 1.0 \n";
        $headers .= "Content-type: text/html; charset=utf-8 \n";
        $headers .= "From: ".$config["mailer_from_name"]." <".$config["mailer_from_address"]."> \n";
        $headers .= "Reply-To: ".$config["mailer_from_address"]." \n";

        if (! mail($to, $subject, $body, $headers)) {
            addError("Error: email wasn't sent.");
            return false;
        }
    }
    else {
        $mail = new PHPmailer;
        // $mail->SMTPDebug = 3;                              // Enable verbose debug output

        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = $config["smtp_host"];  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = $config["smtp_user"];               // SMTP username
        $mail->Password = $config["smtp_password"];           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $config["smtp_port"];                   // TCP port to connect to

        $mail->setFrom($config["mailer_from_address"], $config["mailer_from_name"]);
        $mail->addAddress($to);     // Add a recipient
        $mail->isHTML(true);                                  // Set email format to HTML

        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $body;

        if(! $mail->send()) {
            addError("Email wasn't sent. Mailer Error: ".$mail->ErrorInfo);
            return false;
        }
    }

    return true;
}

function sendConfirmEmail($email, $id, $token)
{
    global $siteURL;
    $subject = "Confirm your email address";
    $body = "You have registered or changed your email address on the site. <br> Please click the link below to verify the email adress. <br><br>";
    $link = $siteURL."index.php?p=register&a=confirmemail&id=$id&token=$token";
    $body .= "<a href='$link'>$link</a>";

    return sendEmail($email, $subject, $body);
}

function sendChangePasswordEmail($email, $id, $token)
{
    global $siteURL;
    $subject = "Change your password";
    $body = "You have requested to change your password. <br> Click the link below within 48 hours to access the form.<br>";
    $link = $siteURL."index.php?p=login&a=changepassword&id=$id&token=$token";
    $body .= "<a href='$link'>$link</a>";

    return sendEmail($email, $subject, $body);
}

function sendTestEmail($email)
{
    global $siteURL;
    $subject = "Test Email";
    $body = "This is a test email from the Mini CMS Vanilla.";

    return sendEmail($email, $subject, $body);
}