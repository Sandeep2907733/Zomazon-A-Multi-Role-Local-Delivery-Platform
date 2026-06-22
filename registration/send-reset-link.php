<?php
include __DIR__ . '/../config/db.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── SMTP credentials — same Gmail App Password used everywhere ────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USERNAME', 'dassandeep479@gmail.com');   // ← your Gmail address
define('SMTP_PASSWORD', 'cdjx bkak ozlr ncqq');     // ← your Gmail App Password
define('SMTP_FROM',     'dassandeep479@gmail.com');   // ← same as SMTP_USERNAME
define('SMTP_FROM_NAME','Zomazon');

// ── Handle the POST from forgot_password.php ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {

    $posted_email = trim($_POST['email']);

    if (filter_var($posted_email, FILTER_VALIDATE_EMAIL)) {

        // Look up the user
        $stmt = $conn->prepare("SELECT p_id, name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $posted_email);
        $stmt->execute();
        $stmt->bind_result($user_id, $user_name);
        $found = $stmt->fetch();
        $stmt->close();

        if ($found) {
            // Generate a secure token, valid for 1 hour
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $up = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE p_id = ?");
            $up->bind_param("ssi", $token, $expires, $user_id);
            $up->execute();
            $up->close();

            // Build reset link
            $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'];
            $base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/');
            $reset_link = "{$scheme}://{$host}{$base_dir}/reset-password.php?token=" . $token;

            // ── Send the email ─────────────────────────────────────────────────
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
                        <p>We received a request to reset your password. Click the button below to set a new password:</p>
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
                $mail->AltBody = "Hi {$user_name},\n\nReset your password using this link (valid for 1 hour):\n{$reset_link}\n\nIf you didn't request this, ignore this email.";

                $mail->send();
            } catch (Exception $e) {
                error_log("Zomazon password reset email failed: " . $mail->ErrorInfo);
            }
        }
        // NOTE: We show the "check your inbox" page regardless of whether the
        // email exists, so we don't leak which emails are registered.
    }
}

// ── Email to display on the "check your inbox" page ───────────────────────────
$email = '';
if (!empty($_POST['email'])) {
    $email = htmlspecialchars(trim($_POST['email']));
} elseif (!empty($_GET['email'])) {
    $email = htmlspecialchars(trim($_GET['email']));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check your inbox — Zomazon</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background: #f9fafb;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      color: #111827;
    }

    /* Logo */
    .logo {
      font-size: 22px;
      font-weight: 600;
      color: #16a34a;
      letter-spacing: -0.4px;
      margin-bottom: 1.75rem;
    }
    .logo span { color: #111827; }

    /* Card */
    .card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 2.25rem 2rem;
      width: 100%;
      max-width: 420px;
      text-align: center;
    }

    /* Icon ring */
    .icon-ring {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: #dcfce7;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      animation: pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    @keyframes pop {
      from { transform: scale(0.6); opacity: 0; }
      to   { transform: scale(1);   opacity: 1; }
    }
    .icon-ring svg {
      width: 34px;
      height: 34px;
      stroke: #16a34a;
      stroke-width: 1.8;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* Headings */
    .card h1 {
      font-size: 20px;
      font-weight: 600;
      color: #111827;
      margin-bottom: 0.5rem;
    }
    .card p.sub {
      font-size: 14px;
      color: #6b7280;
      margin-bottom: 1.25rem;
      line-height: 1.6;
    }

    /* Email badge */
    .email-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: #f3f4f6;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 7px 14px;
      font-size: 13px;
      font-weight: 500;
      color: #374151;
      margin-bottom: 1.5rem;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .email-badge svg {
      width: 15px; height: 15px;
      stroke: #9ca3af; stroke-width: 1.8;
      fill: none; stroke-linecap: round; stroke-linejoin: round;
      flex-shrink: 0;
    }

    /* Steps */
    .steps {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 6px;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
    }
    .step {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      color: #6b7280;
    }
    .step svg {
      width: 14px; height: 14px;
      stroke: #16a34a; stroke-width: 2;
      fill: none; stroke-linecap: round; stroke-linejoin: round;
      flex-shrink: 0;
    }
    .step-dot { color: #d1d5db; font-size: 16px; line-height: 1; }

    /* Divider */
    .divider {
      border: none;
      border-top: 1px solid #f3f4f6;
      margin: 1.25rem 0;
    }

    /* Note */
    .note {
      font-size: 12px;
      color: #9ca3af;
      line-height: 1.65;
      margin-bottom: 1.25rem;
    }
    .note strong { color: #6b7280; font-weight: 500; }

    /* Resend button */
    .resend-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 13px;
      font-weight: 500;
      color: #16a34a;
      font-family: inherit;
      padding: 0;
      transition: opacity 0.15s;
    }
    .resend-btn:hover { opacity: 0.75; }
    .resend-btn:disabled { color: #9ca3af; cursor: default; opacity: 1; }
    .resend-btn svg {
      width: 14px; height: 14px;
      stroke: currentColor; stroke-width: 2;
      fill: none; stroke-linecap: round; stroke-linejoin: round;
    }
    .timer-msg {
      font-size: 11px;
      color: #9ca3af;
      margin-top: 6px;
      height: 16px;
    }

    /* Back link */
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-top: 1.5rem;
      font-size: 13px;
      color: #6b7280;
      text-decoration: none;
      transition: color 0.15s;
    }
    .back-link:hover { color: #111827; }
    .back-link svg {
      width: 14px; height: 14px;
      stroke: currentColor; stroke-width: 2;
      fill: none; stroke-linecap: round; stroke-linejoin: round;
    }

    @media (max-width: 480px) {
      .card { padding: 1.75rem 1.25rem; }
    }
  </style>
</head>
<body>

  <div class="logo">Zoma<span>zon</span></div>

  <div class="card">

    <!-- Icon -->
    <div class="icon-ring" aria-hidden="true">
      <!-- Mail forward icon -->
      <svg viewBox="0 0 24 24">
        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/>
        <rect x="3" y="6" width="18" height="12" rx="2"/>
        <path d="M16 12h4m0 0l-2-2m2 2l-2 2"/>
      </svg>
    </div>

    <h1>Check your inbox</h1>
    <p class="sub">We sent a password reset link to</p>

    <div class="email-badge">
      <!-- Mail icon -->
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <rect x="2" y="4" width="20" height="16" rx="2"/>
        <path d="M2 7l10 7 10-7"/>
      </svg>
      <?= $email ?>
    </div>

    <!-- Steps -->
    <div class="steps">
      <div class="step">
        <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        Open the email
      </div>
      <span class="step-dot" aria-hidden="true">·</span>
      <div class="step">
        <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        Click the link
      </div>
      <span class="step-dot" aria-hidden="true">·</span>
      <div class="step">
        <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        Set new password
      </div>
    </div>

    <hr class="divider">

    <p class="note">
      The link expires in <strong>1 hour</strong>.<br>
      If you don't see the email, check your spam folder.
    </p>

    <button class="resend-btn" id="resendBtn" onclick="handleResend()">
      <!-- Refresh icon -->
      <svg viewBox="0 0 24 24" id="resendIcon">
        <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
        <path d="M3.51 9a9 9 0 0114.36-3.36L23 10M1 14l5.13 4.36A9 9 0 0020.49 15"/>
      </svg>
      <span id="resendLabel">Resend email</span>
    </button>
    <div class="timer-msg" id="timerMsg"></div>

  </div>

  <a href="login.php" class="back-link">
    <!-- Arrow left icon -->
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <line x1="19" y1="12" x2="5" y2="12"/>
      <polyline points="12 19 5 12 12 5"/>
    </svg>
    Back to sign in
  </a>

  <script>
    let cooldown = false;

    function handleResend() {
      if (cooldown) return;
      cooldown = true;

      const btn      = document.getElementById('resendBtn');
      const label    = document.getElementById('resendLabel');
      const icon     = document.getElementById('resendIcon');
      const timerMsg = document.getElementById('timerMsg');

      // Swap to check icon
      icon.innerHTML = '<polyline points="20 6 9 17 4 12"/>';
      label.textContent = 'Sent!';
      btn.disabled = true;

      let secs = 30;
      timerMsg.textContent = 'You can resend again in ' + secs + 's';

      const interval = setInterval(() => {
        secs--;
        if (secs <= 0) {
          clearInterval(interval);
          cooldown = false;
          btn.disabled = false;
          icon.innerHTML = '<path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0114.36-3.36L23 10M1 14l5.13 4.36A9 9 0 0020.49 15"/>';
          label.textContent = 'Resend email';
          timerMsg.textContent = '';
        } else {
          timerMsg.textContent = 'You can resend again in ' + secs + 's';
        }
      }, 1000);

      // Actual resend AJAX call
      fetch('resend-reset.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent('<?= addslashes($email) ?>')
      });
    }
  </script>

</body>
</html>