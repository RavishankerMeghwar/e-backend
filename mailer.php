<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.mail.com'; 
    $mail->SMTPAuth = true;
    $mail->Username = 'dev1@gmail.com'; 
    $mail->Password = 'rfrpcrkzpzbsdchg'; 
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('ravi@blinkswag.com', 'Sender Name');
    $mail->addAddress('ravi@blinkswag.com', 'Recipient Name');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email sent from PHP using PHPMailer.';

    $mail->send();
    echo 'Email sent successfully.';
} catch (Exception $e) {
    echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
}
?>