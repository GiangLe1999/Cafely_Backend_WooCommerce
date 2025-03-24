<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once get_template_directory() . '/vendor/autoload.php';

function send_custom_email($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Thay bằng SMTP của bạn
        $mail->SMTPAuth = true;
        $mail->Username = 'legiangbmt09@gmail.com'; 
        $mail->Password = 'jazlethrhndagxqe'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('info@cafely.com', 'CAFELY');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Gửi email thất bại. Lỗi: {$mail->ErrorInfo}");
        return false;
    }
}
