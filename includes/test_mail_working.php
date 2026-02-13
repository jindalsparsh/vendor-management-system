<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'it.admin@jagatjit.com';
    $mail->Password = 'M@jee@1987';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('it.admin@jagatjit.com', 'Your App');
    $mail->addAddress('raj.rajpoot@jagatjit.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test email from PHPMailer';
    $mail->Body = '<b>Hello!</b> This email confirms that PHPMailer is configured correctly.';
    $mail->AltBody = 'Hello! This email confirms that PHPMailer is configured correctly.';

    $mail->send();
    echo 'Message has been sent successfully';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}


?>