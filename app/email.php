<?php
require_once "../../phpmailer/class.smtp.php";
require_once "../../phpmailer/class.phpmailer.php";

function sendEmail($to, $subject, $body)
{
    global $config;

    if ($config["smtp_host"] === "") {
        // $header = "";
        // mail();
    }
    else {
        $mail = new PHPmailer;
        // $mail->SMTPDebug = 3;                               // Enable verbose debug output

        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = $config["smtp_host"];  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = $config["smtp_user"];                 // SMTP username
        $mail->Password = $config["smtp_password"];                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $config["smtp_port"];                                    // TCP port to connect to

        $mail->setFrom($config["mailer_from_address"], $config["mailer_from_name"]);
        $mail->addAddress($to);     // Add a recipient
        $mail->isHTML(true);                                  // Set email format to HTML

        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $body;

        if(! $mail->send()) {
            addError("Email wasn't sent.");
            addError("Mailer Error: ".$mail->ErrorInfo);
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
    $link = $siteURL."index.php?p=changepassword&id=$id&token=$token";
    $body .= "<a href='$link'>$link</a>";

    return sendEmail($email, $subject, $body);
}
