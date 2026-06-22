<?php
session_start();

$delivery_date = date("d F", strtotime("+5 days"));
$order_id = strtoupper(substr(md5(uniqid()), 0, 8));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Inter', sans-serif;
    background: #0F1B2D;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }

  .card {
    background: #162338;
    border: 1px solid rgba(139, 156, 244, 0.15);
    border-radius: 20px;
    padding: 52px 44px 44px;
    width: 100%;
    max-width: 440px;
    text-align: center;
  }

  /* Pulse checkmark */
  .pulse-wrap {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 32px;
  }

  .pulse-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid #22C55E;
    animation: pulse-out 2s ease-out infinite;
    opacity: 0;
  }

  .pulse-ring:nth-child(2) { animation-delay: 0.6s; }
  .pulse-ring:nth-child(3) { animation-delay: 1.2s; }

  @keyframes pulse-out {
    0%   { transform: scale(1);   opacity: 0.7; }
    100% { transform: scale(2.2); opacity: 0; }
  }

  .check-circle {
    position: relative;
    z-index: 1;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(34, 197, 94, 0.12);
    border: 2px solid #22C55E;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .check-circle svg {
    width: 36px;
    height: 36px;
    stroke: #22C55E;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
  }

  .check-path {
    stroke-dasharray: 40;
    stroke-dashoffset: 40;
    animation: draw-check 0.5s ease-out 0.2s forwards;
  }

  @keyframes draw-check {
    to { stroke-dashoffset: 0; }
  }

  /* Text */
  h1 {
    font-family: 'Sora', sans-serif;
    font-size: 24px;
    font-weight: 600;
    color: #F0F4FF;
    letter-spacing: -0.3px;
    margin-bottom: 8px;
  }

  .subtitle {
    font-size: 14px;
    color: rgba(240, 244, 255, 0.5);
    line-height: 1.6;
    margin-bottom: 32px;
  }

  /* Info strip */
  .info-strip {
    background: rgba(139, 156, 244, 0.07);
    border: 1px solid rgba(139, 156, 244, 0.15);
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 32px;
    display: flex;
    align-items: center;
    gap: 16px;
    text-align: left;
  }

  .info-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(139, 156, 244, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .info-icon svg {
    width: 20px;
    height: 20px;
    stroke: #8B9CF4;
    fill: none;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .info-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: rgba(240, 244, 255, 0.35);
    margin-bottom: 4px;
  }

  .info-value {
    font-family: 'Sora', sans-serif;
    font-size: 16px;
    font-weight: 500;
    color: #F0F4FF;
  }

  .divider { width: 1px; height: 36px; background: rgba(139, 156, 244, 0.15); }

  /* Order ID */
  .order-id {
    font-size: 12px;
    color: rgba(240, 244, 255, 0.35);
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
    margin-bottom: 28px;
  }

  .order-id span { color: rgba(240, 244, 255, 0.55); }

  /* CTA */
  .btn {
    display: block;
    width: 100%;
    padding: 14px;
    background: #22C55E;
    color: #0F1B2D;
    font-family: 'Sora', sans-serif;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.18s, transform 0.12s;
  }

  .btn:hover { background: #16A34A; transform: translateY(-1px); }
  .btn:active { transform: translateY(0); }

  .btn-secondary {
    display: block;
    margin-top: 12px;
    font-size: 13px;
    color: rgba(240, 244, 255, 0.4);
    text-decoration: none;
    transition: color 0.18s;
  }

  .btn-secondary:hover { color: rgba(240, 244, 255, 0.7); }

  /* SweetAlert overrides */
  .swal2-popup {
    font-family: 'Inter', sans-serif !important;
    background: #162338 !important;
    border: 1px solid rgba(139, 156, 244, 0.2) !important;
    border-radius: 16px !important;
    color: #F0F4FF !important;
  }

  .swal2-title { color: #F0F4FF !important; font-family: 'Sora', sans-serif !important; }
  .swal2-html-container { color: rgba(240, 244, 255, 0.6) !important; }

  .swal2-confirm {
    background: #22C55E !important;
    color: #0F1B2D !important;
    font-family: 'Sora', sans-serif !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    padding: 10px 28px !important;
    box-shadow: none !important;
  }
</style>
</head>
<body>

<div class="card">

  <div class="pulse-wrap">
    <div class="pulse-ring"></div>
    <div class="pulse-ring"></div>
    <div class="pulse-ring"></div>
    <div class="check-circle">
      <svg viewBox="0 0 24 24">
        <polyline class="check-path" points="4,13 9,18 20,7"></polyline>
      </svg>
    </div>
  </div>

  <h1>Order confirmed</h1>
  <p class="subtitle">We've received your order and are getting it ready. You'll get an email once it ships.</p>

  <div class="info-strip">
    <div class="info-icon">
      <!-- truck icon -->
      <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
    </div>
    <div>
      <div class="info-label">Expected delivery</div>
      <div class="info-value"><?php echo $delivery_date; ?></div>
    </div>
    <div class="divider"></div>
    <div>
      <div class="info-label">Items</div>
      <div class="info-value">1 item</div>
    </div>
  </div>

  <div class="order-id">Order <span>#<?php echo $order_id; ?></span></div>

  <a href="index.php" class="btn">Continue shopping</a>
  <a href="#" class="btn-secondary">Track my order →</a>

</div>

<script>
Swal.fire({
  title: "You're all set!",
  html: "Your order will arrive by <strong><?php echo $delivery_date; ?></strong>.",
  icon: "success",
  confirmButtonText: "Got it",
  iconColor: "#22C55E",
  customClass: {
    popup: 'swal2-popup',
    title: 'swal2-title',
    htmlContainer: 'swal2-html-container',
    confirmButton: 'swal2-confirm'
  }
});
</script>

</body>
</html>