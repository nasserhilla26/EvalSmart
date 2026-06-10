<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

function sendResetEmail($toEmail, $token) {

    $mail = new PHPMailer(true);

    try {

        // SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'evalsmart.noreply@gmail.com';
        $mail->Password = 'bhbe fmkg ytnk kgep'; // replace

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // SENDER
        $mail->setFrom('evalsmart.noreply@gmail.com', 'EvalSmart System');

        // RECEIVER
        $mail->addAddress($toEmail);

        // CONTENT
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';

        $resetLink = "http://localhost/evalsmart/account/reset_password.php?token=$token";

        $mail->Body = "
            <h3>Password Reset Request</h3>
            <p>You requested to reset your password.</p>
            <p>Click the link below:</p>
            <a href='$resetLink'>$resetLink</a>
            <br><br>
            <small>This link will expire in 1 hour.</small>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}