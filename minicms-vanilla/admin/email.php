<?php
require_once "../gitignore/emailconfig.php";
require_once "phpmailer/class.smtp.php";
require_once "phpmailer/class.phpmailer.php";

function sendEmail($to, $subject, $body) {
  global $smtpHost, $smtpUser, $smtpPassword, $emailFrom;
  if (!isset($altBody))
    $altBody = $body;

  $mail = new PHPmailer;
	// $mail->SMTPDebug = 3;                               // Enable verbose debug output

	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = $smtpHost;  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = $smtpUser;                 // SMTP username
	$mail->Password = $smtpPassword;                           // SMTP password
	$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 587;                                    // TCP port to connect to

	$mail->setFrom($emailFrom, 'MiiCMS Vanilla Mailer');
	$mail->addAddress($to);     // Add a recipient
	$mail->isHTML(true);                                  // Set email format to HTML

	$mail->Subject = $subject;
	$mail->Body    = $body;
	$mail->AltBody = $altBody;

	if(!$mail->send()) {
    $error = "Email wasn't sent. <br>";
    $error .= "Mailer Error: ".$mail->ErrorInfo;
    return $error;
	}

	return "";
}


function sendConfirmEmail($to, $token) {
  global $currentSiteURL;
  $subject = "Confirm your email address";
  $body = "You have registered or changed your email address on the site. <br> Please click the link below to verify the email adress. <br><br>";
  $link = $currentSiteURL."index.php?email=$to&confirmtoken=$token";
  $body .= "<a href='$link'>$link</a>";

  return sendEmail($to, $subject, $body);
}


function sendChangePasswordEmail($to, $token) {
  global $currentSiteURL;
  $subject = "Change your password";
  $body = "You have requested to change your password. <br> Click the link below within 48 hours to access the form.<br>";
  $link = $currentSiteURL."index.php?action=forgotPassword&email=$to&token=$token";
  $body .= "<a href='$link'>$link</a>";

  return sendEmail($to, $subject, $body);
}