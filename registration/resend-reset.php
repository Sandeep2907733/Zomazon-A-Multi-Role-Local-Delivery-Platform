<?php
include __DIR__ . '/../config/db.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── SMTP credentials — same Gmail App Password used everywhere ────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USERNAME', 'yourname@gmail.com');   // ← your Gmail address
define('SMTP_PASSWORD', 'abcdefghijklmnop');     // ← your Gmail App Password
define('SMTP_FROM',     'yourname@gmail.com');   // ← same as SMTP_USERNAME
define('SMTP_FROM_NAME','Zomazon');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
    echo json_encode(['ok' => false]);
    exit;
}

$posted_email = trim($_POST['email']);

if (!filter_var($posted_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT p_id, name FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $posted_email);
$stmt->execute();
$stmt->bind_result($user_id, $user_name);
$found = $stmt->fetch();
$stmt->close();

if ($found) {
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $up = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE p_id = ?");
    $up->bind_param("ssi", $token, $expires, $user_id);
    $up->execute();
    $up->close();

    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    $reset_link = "{$scheme}://{$host}{$base_dir}/reset-password.php?token=" . $token;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($posted_email, $user_name);

        $mail->isHTML(true);
        $mail->Subject = 'Reset your Zomazon password';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;'>
                <h2 style='color:#16a34a;'>Zomazon</h2>
                <p>Hi " . htmlspecialchars($user_name) . ",</p>
                <p>Here is your new password reset link:</p>
                <p style='text-align:center;margin:24px 0;'>
                    <a href='{$reset_link}'
                       style='background:#16a34a;color:#fff;padding:12px 28px;border-radius:8px;
                              text-decoration:none;font-weight:600;display:inline-block;'>
                       Reset Password
                    </a>
                </p>
                <p style='font-size:13px;color:#6b7280;'>This link expires in 1 hour. If you didn't request this, you can ignore this email.</p>
                <p style='font-size:13px;color:#9ca3af;'>Or copy this link: {$reset_link}</p>
            </div>
        ";
        $mail->AltBody = "Hi {$user_name},\n\nHere is your new password reset link (valid for 1 hour):\n{$reset_link}\n\nIf you didn't request this, ignore this email.";

        $mail->send();
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        error_log("Zomazon resend reset email failed: " . $mail->ErrorInfo);
        echo json_encode(['ok' => false]);
    }
} else {
    // Don't reveal whether the email exists
    echo json_encode(['ok' => true]);
}