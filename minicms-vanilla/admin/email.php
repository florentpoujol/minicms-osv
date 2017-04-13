<?php
require_once "../gitignore/emailconfig.php";
require_once "phpmailer/class.smtp.php";
require_once "phpmailer/class.phpmailer.php";

function sendEmail($to, $subject, $body) {
  $mail = new PHPmailer;

	$mail->SMTPDebug = 3;                               // Enable verbose debug output

	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = $smtpHost;  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = $smtpUser;                 // SMTP username
	$mail->Password = $smtpPassword;                           // SMTP password
	$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 587;                                    // TCP port to connect to

	$mail->setFrom($emailFrom, 'Mailer');
	$mail->addAddress($to);     // Add a recipient
	// $mail->isHTML(true);                                  // Set email format to HTML

	$mail->Subject = $subject;
	$mail->Body    = $body;
	$mail->AltBody = $altBody;

	if(!$mail->send()) {
	    echo 'Message could not be sent.';
	    echo 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
	    echo 'Message has been sent';
	}
}

function sendConfirmEmail($to, $token) {
	$subject = "Confirm your email adress";
	$body = "You have registered or changed email adresse on the site. <br> Please click the link below to verify the email adress. <br><br>";
	$link = $_SERVER['request_uri']
	$body = "<a href='' ></a>";
}
