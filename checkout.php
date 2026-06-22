<?php
include 'config/db.php';
// No Razorpay SDK needed — we talk to Razorpay's REST API directly via cURL
// (same approach as your working Here2ThereRentals project).

// ── PHPMailer ─────────────────────────────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

// ── SMTP credentials ──────────────────────────────────────────────────────────
// Copy these EXACTLY from your registration/send-reset-link.php on disk
// (the same values that make your forgot-password email work)
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USERNAME', 'dassandeep479@gmail.com');   // ← copy from send-reset-link.php
define('SMTP_PASSWORD', 'cdjx bkak ozlr ncqq'); // ← copy from send-reset-link.php
define('SMTP_FROM',     'dassandeep479@gmail.com');   // ← copy from send-reset-link.php
define('SMTP_FROM_NAME','Zomazon');


define('RZP_KEY_ID',     'rzp_test_SsmI4QFt1VJxUX');
define('RZP_KEY_SECRET', 'K6nq00sdLGtw0D3KHbmAz9Nm');

// ═══════════════════════════════════════════════════════════════════════════════
// MODE 1 — AJAX: JS calls this file with X-Request header to create Razorpay order
// ═══════════════════════════════════════════════════════════════════════════════
if (
    isset($_SERVER['HTTP_X_REQUEST']) &&
    $_SERVER['HTTP_X_REQUEST'] === 'razorpay-order' &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    header('Content-Type: application/json');

    // ── Make this endpoint JSON-only, no matter what goes wrong ───────────────
    // Without this, any PHP warning/notice/fatal error gets printed as raw HTML
    // (e.g. "<br />\n<b>Fatal error</b>...") in front of/instead of the JSON,
    // and `r.json()` on the frontend fails with "unexpected <br> tag".
    ini_set('display_errors', '0');
    error_reporting(E_ALL);

    set_exception_handler(function ($e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    });
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Unauthorized']); exit;
    }

    $input  = json_decode(file_get_contents('php://input'), true);
    $amount = intval($input['amount'] ?? 0);

    if ($amount <= 0) {
        echo json_encode(['error' => 'Invalid amount']); exit;
    }

    $rzp_payload = json_encode([
        'amount'          => $amount,        // in paise
        'currency'        => 'INR',
        'receipt'         => 'zomazon_' . time(),
        'payment_capture' => 1,
    ]);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $rzp_payload,
        CURLOPT_USERPWD        => RZP_KEY_ID . ':' . RZP_KEY_SECRET,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $rzp_response = curl_exec($ch);
    $curl_err     = curl_error($ch);
    curl_close($ch);

    if ($rzp_response === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not reach Razorpay: ' . $curl_err]);
        exit;
    }

    // Razorpay already replies with JSON — either the order object (has "id")
    // or an error object (has "error"). Pass it straight through.
    echo $rzp_response;
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// MODE 2 — FORM POST: verify payment signature, save order, redirect
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['total'])) {

    if (!isset($_SESSION['user_id'])) {
        header("Location: registration/Login.php"); exit;
    }

    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';
    $payment_status = 'pending';
    $rpPayId = $rpOrdId = $rpSig = '';

    // ── Verify Razorpay signature for online payments ─────────────────────────
    if ($payment_method !== 'Cash on Delivery') {
        $rpPayId = trim($_POST['razorpay_payment_id'] ?? '');
        $rpOrdId = trim($_POST['razorpay_order_id']   ?? '');
        $rpSig   = trim($_POST['razorpay_signature']  ?? '');

        if (empty($rpPayId) || empty($rpOrdId) || empty($rpSig)) {
            die("❌ Payment data missing. Please try again.");
        }

        try {
            $expected_signature = hash_hmac('sha256', $rpOrdId . '|' . $rpPayId, RZP_KEY_SECRET);
            if (hash_equals($expected_signature, $rpSig)) {
                $payment_status = 'paid';
            } else {
                throw new \Exception('Signature mismatch');
            }
        } catch (\Throwable $e) {
            die("❌ Payment verification failed. Please contact support.");
        }
    }

    // ── Sanitise inputs ───────────────────────────────────────────────────────
    $uid          = intval($_SESSION['user_id']);
    $total        = floatval($_POST['total']);
    $subtotal     = floatval($_POST['subtotal']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    $platform_fee = floatval($_POST['platform_fee']);
    $full_name    = htmlspecialchars(trim($_POST['full_name'] ?? ''));
    $phone        = htmlspecialchars(trim($_POST['phone']     ?? ''));
    $email        = htmlspecialchars(trim($_POST['email']     ?? ''));

    // ── Build address string ──────────────────────────────────────────────────
    if (!empty($_POST['address'])) {
        // Saved address
        $address = htmlspecialchars(trim($_POST['address']));
    } else {
        // New address fields
        $parts = array_filter([
            trim($_POST['address_line1'] ?? ''),
            trim($_POST['address_area']  ?? ''),
            trim($_POST['address_city']  ?? ''),
            trim($_POST['address_state'] ?? ''),
            trim($_POST['address_pin']   ?? ''),
        ]);
        $address = htmlspecialchars(implode(', ', $parts));
        if (!empty($_POST['address_note'])) {
            $address .= ' — ' . htmlspecialchars(trim($_POST['address_note']));
        }
    }

    if (empty($address)) {
        die("❌ Delivery address is required.");
    }

    // ── Build aggregated cart summary for the orders row ─────────────────────
    // The orders table stores a snapshot: shop_id (first shop), product_id
    // (first product), a JSON products list, and the total price.
    reset($_SESSION['cart']);
    $first_product_id = intval(key($_SESSION['cart']));          // array key = product id
    $first_item       = current($_SESSION['cart']);

    // Resolve shop_id: try to look it up from the products table
    $shop_id_val = '';
    $sq = $conn->prepare("SELECT shop_id FROM products WHERE id = ? LIMIT 1");
    if ($sq) {
        $sq->bind_param("i", $first_product_id);
        $sq->execute();
        $sq->bind_result($shop_id_val);
        $sq->fetch();
        $sq->close();
    }

    // Build a compact JSON snapshot of all cart items for the `products` column
    $products_snapshot = [];
    foreach ($_SESSION['cart'] as $pid => $item) {
        $products_snapshot[] = [
            'product_id' => intval($pid),
            'name'       => $item['name'],
            'price'      => floatval($item['price']),
            'qty'        => intval($item['qty']),
        ];
    }
    $products_json = json_encode($products_snapshot, JSON_UNESCAPED_UNICODE);

    // delivery_date = today + 3 days
    $delivery_date = date('Y-m-d', strtotime('+3 days'));

    // ── Insert order ──────────────────────────────────────────────────────────
    $stmt = $conn->prepare("
        INSERT INTO orders
            (user_id, full_name, phone, email, address,
             subtotal, delivery_fee, platform_fee, payment_method, payment_status,
             razorpay_order_id, razorpay_payment_id, razorpay_signature,
             delivery_date, status,
             shop_id, product_id, products, price, total,
             created_at)
        VALUES
            (?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?,
             ?, ?, ?,
             ?, ?,
             ?, ?, ?, ?, ?,
             NOW())
    ");
    $status_val = 'pending';
    $stmt->bind_param(
        "issssddd" . "ss" . "sss" . "ss" . "i" . "ssdd",
        $uid, $full_name, $phone, $email, $address,
        $subtotal, $delivery_fee, $platform_fee, $payment_method, $payment_status,
        $rpOrdId, $rpPayId, $rpSig,
        $delivery_date, $status_val,
        $shop_id_val, $first_product_id, $products_json, $total, $total
    );

    if (!$stmt->execute()) {
        die("❌ Could not save order: " . $stmt->error . ". Please try again.");
    }

    $order_id = $conn->insert_id;

    // ── Send order confirmation email ─────────────────────────────────────────
    if (!empty($email)) {
        sendOrderEmail(
            $email, $full_name, $order_id,
            $_SESSION['cart'],
            $subtotal, $delivery_fee, $platform_fee, $total,
            $payment_method, $delivery_date
        );
    }

    // ── Clear cart & redirect ─────────────────────────────────────────────────
    unset($_SESSION['cart']);
    header("Location: Success.php?order_id=" . $order_id);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// HELPER — Send order confirmation email via PHPMailer
// ═══════════════════════════════════════════════════════════════════════════════
function sendOrderEmail(
    string $to_email,
    string $to_name,
    int    $order_id,
    array  $cart,
    float  $subtotal,
    float  $delivery_fee,
    float  $platform_fee,
    float  $total,
    string $payment_method,
    string $delivery_date
): void {

    // ── Build the items rows for the receipt table ────────────────────────────
    $items_html = '';
    foreach ($cart as $item) {
        $name      = htmlspecialchars($item['name']);
        $qty       = intval($item['qty']);
        $unit      = floatval($item['price']);
        $line      = $unit * $qty;
        $items_html .= "
        <tr>
          <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#1a1a1a;'>
            {$name}
          </td>
          <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#555;text-align:center;'>
            &times; {$qty}
          </td>
          <td style='padding:10px 14px;border-bottom:1px solid #f0f0f0;font-size:14px;color:#1a1a1a;text-align:right;'>
            &#8377;" . number_format($line, 2) . "
          </td>
        </tr>";
    }

    $delivery_row = $delivery_fee > 0
        ? "<tr>
             <td colspan='2' style='padding:8px 14px;font-size:13px;color:#555;'>Delivery Fee</td>
             <td style='padding:8px 14px;font-size:13px;color:#1a1a1a;text-align:right;'>&#8377;" . number_format($delivery_fee, 2) . "</td>
           </tr>"
        : "<tr>
             <td colspan='2' style='padding:8px 14px;font-size:13px;color:#555;'>Delivery Fee</td>
             <td style='padding:8px 14px;font-size:13px;color:#16a34a;text-align:right;font-weight:600;'>FREE</td>
           </tr>";

    $delivery_date_fmt = date('D, d M Y', strtotime($delivery_date));

    $pay_badge_color = ($payment_method === 'Cash on Delivery') ? '#f59e0b' : '#3b82f6';
    $pay_badge_bg    = ($payment_method === 'Cash on Delivery') ? '#fffbeb' : '#eff6ff';

    // ── Build full HTML receipt ───────────────────────────────────────────────
    $html = "
<!DOCTYPE html>
<html lang='en'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f4f4f5;font-family:\"DM Sans\",Arial,sans-serif;'>

  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f5;padding:32px 0;'>
    <tr><td align='center'>
      <table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;'>

        <!-- Header -->
        <tr>
          <td align='center' style='padding-bottom:24px;'>
            <span style='font-size:26px;font-weight:800;color:#16a34a;letter-spacing:-0.5px;'>
              Zoma<span style='color:#1a1a1a;'>zon</span>
            </span>
          </td>
        </tr>

        <!-- Card -->
        <tr>
          <td style='background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);'>

            <!-- Green top bar -->
            <table width='100%' cellpadding='0' cellspacing='0'>
              <tr>
                <td style='background:linear-gradient(135deg,#16a34a,#22c55e);padding:28px 32px;text-align:center;'>
                  <div style='width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:50%;
                              display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;
                              font-size:26px;line-height:56px;'>✅</div>
                  <h1 style='margin:0;color:#ffffff;font-size:22px;font-weight:700;'>
                    Order Confirmed!
                  </h1>
                  <p style='margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:14px;'>
                    Thank you, {$to_name}. Your order has been placed successfully.
                  </p>
                </td>
              </tr>
            </table>

            <!-- Order meta -->
            <table width='100%' cellpadding='0' cellspacing='0' style='padding:24px 32px 0;'>
              <tr>
                <td>
                  <table width='100%' cellpadding='0' cellspacing='0'
                         style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>
                    <tr>
                      <td style='padding:14px 20px;border-right:1px solid #e5e7eb;'>
                        <p style='margin:0;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.6px;'>Order ID</p>
                        <p style='margin:4px 0 0;font-size:16px;font-weight:700;color:#1a1a1a;'>#ZMZ-{$order_id}</p>
                      </td>
                      <td style='padding:14px 20px;border-right:1px solid #e5e7eb;'>
                        <p style='margin:0;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.6px;'>Expected Delivery</p>
                        <p style='margin:4px 0 0;font-size:15px;font-weight:700;color:#16a34a;'>{$delivery_date_fmt}</p>
                      </td>
                      <td style='padding:14px 20px;'>
                        <p style='margin:0;font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.6px;'>Payment</p>
                        <p style='margin:4px 0 0;'>
                          <span style='display:inline-block;background:{$pay_badge_bg};color:{$pay_badge_color};
                                       border:1px solid {$pay_badge_color};border-radius:20px;
                                       padding:3px 10px;font-size:12px;font-weight:600;'>
                            {$payment_method}
                          </span>
                        </p>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- Items table -->
            <table width='100%' cellpadding='0' cellspacing='0' style='padding:24px 32px 0;'>
              <tr>
                <td>
                  <p style='margin:0 0 12px;font-size:13px;font-weight:700;color:#6b7280;
                             text-transform:uppercase;letter-spacing:0.6px;'>Order Items</p>
                  <table width='100%' cellpadding='0' cellspacing='0'
                         style='border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>
                    <thead>
                      <tr style='background:#f9fafb;'>
                        <th style='padding:10px 14px;text-align:left;font-size:12px;color:#9ca3af;font-weight:600;'>Item</th>
                        <th style='padding:10px 14px;text-align:center;font-size:12px;color:#9ca3af;font-weight:600;'>Qty</th>
                        <th style='padding:10px 14px;text-align:right;font-size:12px;color:#9ca3af;font-weight:600;'>Price</th>
                      </tr>
                    </thead>
                    <tbody>
                      {$items_html}
                    </tbody>
                  </table>
                </td>
              </tr>
            </table>

            <!-- Price summary -->
            <table width='100%' cellpadding='0' cellspacing='0' style='padding:16px 32px 0;'>
              <tr>
                <td>
                  <table width='100%' cellpadding='0' cellspacing='0'
                         style='border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>
                    <tr>
                      <td colspan='2' style='padding:8px 14px;font-size:13px;color:#555;'>Subtotal</td>
                      <td style='padding:8px 14px;font-size:13px;color:#1a1a1a;text-align:right;'>&#8377;" . number_format($subtotal, 2) . "</td>
                    </tr>
                    {$delivery_row}
                    <tr>
                      <td colspan='2' style='padding:8px 14px;font-size:13px;color:#555;'>Platform Fee</td>
                      <td style='padding:8px 14px;font-size:13px;color:#1a1a1a;text-align:right;'>&#8377;" . number_format($platform_fee, 2) . "</td>
                    </tr>
                    <tr style='background:#f0fdf4;'>
                      <td colspan='2' style='padding:12px 14px;font-size:16px;font-weight:700;color:#1a1a1a;'>Total</td>
                      <td style='padding:12px 14px;font-size:18px;font-weight:800;color:#16a34a;text-align:right;'>
                        &#8377;" . number_format($total, 2) . "
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>

            <!-- Footer note -->
            <table width='100%' cellpadding='0' cellspacing='0' style='padding:24px 32px 32px;'>
              <tr>
                <td style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px 20px;text-align:center;'>
                  <p style='margin:0;font-size:13px;color:#6b7280;line-height:1.6;'>
                    🚚 Your order will be delivered by <strong style='color:#16a34a;'>{$delivery_date_fmt}</strong><br>
                    Questions? Reply to this email or visit <strong>zomazon.com/support</strong>
                  </p>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td align='center' style='padding:24px 0 0;'>
            <p style='margin:0;font-size:12px;color:#9ca3af;'>
              &copy; " . date('Y') . " Zomazon. All rights reserved.<br>
              This email was sent to {$to_email} because you placed an order on Zomazon.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>

</body>
</html>";

    // ── Plain-text fallback ───────────────────────────────────────────────────
    $text  = "Hi {$to_name},\n\n";
    $text .= "Your Zomazon order #ZMZ-{$order_id} has been confirmed!\n\n";
    $text .= "Expected delivery: {$delivery_date_fmt}\n";
    $text .= "Payment: {$payment_method}\n\n";
    $text .= "---- Items ----\n";
    foreach ($cart as $item) {
        $text .= htmlspecialchars($item['name']) . " x{$item['qty']} — Rs." . number_format($item['price'] * $item['qty'], 2) . "\n";
    }
    $text .= "\nSubtotal:     Rs." . number_format($subtotal, 2);
    $text .= "\nDelivery:     " . ($delivery_fee > 0 ? "Rs." . number_format($delivery_fee, 2) : "FREE");
    $text .= "\nPlatform Fee: Rs." . number_format($platform_fee, 2);
    $text .= "\nTotal:        Rs." . number_format($total, 2);
    $text .= "\n\nThank you for shopping with Zomazon!\n";

    // ── PHPMailer send ────────────────────────────────────────────────────────
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
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = "Order Confirmed #ZMZ-{$order_id} — Zomazon";
        $mail->Body    = $html;
        $mail->AltBody = $text;

        $mail->send();
    } catch (Exception $e) {
        // Email failure should NOT block the order — silently log it
        error_log("Zomazon order email failed for order #{$order_id}: " . $mail->ErrorInfo);
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// MODE 3 — GET: render the checkout page
// ═══════════════════════════════════════════════════════════════════════════════

if (!isset($_SESSION['user_id'])) {
    header("Location: registration/Login.php"); exit;
}
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php"); exit;
}

// ── Fetch user details ────────────────────────────────────────────────────────
$uid = intval($_SESSION['user_id']);
$ps  = $conn->prepare("SELECT name, phone, address, email FROM users WHERE p_id = ?");
$ps->bind_param("i", $uid);
$ps->execute();
$user = $ps->get_result()->fetch_assoc() ?? [];

// ── Calculate totals ──────────────────────────────────────────────────────────
$subtotal                = 0;
$platform_fee            = 9;
$free_delivery_threshold = 299;

foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

$delivery_fee = ($subtotal >= $free_delivery_threshold) ? 0 : 40;
$total        = $subtotal + $delivery_fee + $platform_fee;
$cartCount    = array_sum(array_column($_SESSION['cart'], 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — Zomazon</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        bg:      '#0b0f1a',
        surface: '#111827',
        surface2:'#1a2235',
        green:   '#22c55e',
        muted:   '#64748b',
      },
      fontFamily: {
        syne: ['Syne', 'sans-serif'],
        dm:   ['DM Sans', 'sans-serif'],
      },
      keyframes: {
        fadeUp:  { '0%':{ opacity:'0', transform:'translateY(14px)' }, '100%':{ opacity:'1', transform:'translateY(0)' } },
        slideIn: { '0%':{ opacity:'0', transform:'translateX(40px)' }, '100%':{ opacity:'1', transform:'translateX(0)' } },
      },
      animation: {
        'fade-up':  'fadeUp 0.45s ease both',
        'slide-in': 'slideIn 0.35s ease both',
      },
    }
  }
}
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

<style>
  body { font-family: 'DM Sans', sans-serif; }

  body::before {
    content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  }

  .zform-input {
    width: 100%; background: #1a2235;
    border: 1px solid rgba(255,255,255,0.07); border-radius: 12px;
    color: #f1f5f9; font-family: 'DM Sans', sans-serif;
    font-size: 14px; padding: 12px 16px; outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .zform-input::placeholder { color: #64748b; }
  .zform-input:focus {
    border-color: rgba(34,197,94,0.45);
    box-shadow: 0 0 0 3px rgba(34,197,94,0.08);
  }
  textarea.zform-input { resize: vertical; min-height: 90px; }

  .pay-card input[type="radio"] { display: none; }
  .pay-card label {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 18px; border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.07);
    background: #1a2235; cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
  }
  .pay-card input[type="radio"]:checked + label {
    border-color: rgba(34,197,94,0.45);
    background: rgba(34,197,94,0.06);
  }
  .pay-card label .radio-dot {
    width: 18px; height: 18px; border-radius: 50%;
    border: 2px solid #64748b; display: grid;
    place-items: center; flex-shrink: 0; transition: border-color 0.2s;
  }
  .pay-card input[type="radio"]:checked + label .radio-dot { border-color: #22c55e; }
  .pay-card input[type="radio"]:checked + label .radio-dot::after {
    content: ''; width: 8px; height: 8px;
    border-radius: 50%; background: #22c55e; display: block;
  }

  .addr-tab.active {
    background: rgba(34,197,94,0.12) !important;
    border-color: rgba(34,197,94,0.4) !important;
    color: #22c55e !important;
  }

  #razorpay-panel {
    margin-top: 14px; padding: 14px 16px;
    background: rgba(59,130,246,0.06);
    border: 1px solid rgba(59,130,246,0.2);
    border-radius: 12px;
  }

  .section-card:nth-child(1){ animation-delay: .04s }
  .section-card:nth-child(2){ animation-delay: .10s }
  .section-card:nth-child(3){ animation-delay: .16s }

  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>

<body class="bg-bg text-white min-h-screen overflow-x-hidden">

<!-- ═══════ NAVBAR ═══════ -->
<nav class="sticky top-0 z-30 flex items-center justify-between px-6 md:px-10 h-16
            bg-bg/85 backdrop-blur-xl border-b border-white/5">
  <a href="index.php" class="flex items-center gap-2 font-syne font-extrabold text-lg no-underline">
    <div class="w-8 h-8 rounded-lg bg-green/10 border border-green/30 grid place-items-center text-base">🛒</div>
    <span class="text-white">Zoma<span class="text-green">zon</span></span>
  </a>
  <div class="flex items-center gap-3">
    <a href="cart.php"
       class="hidden sm:flex items-center gap-1.5 text-muted text-sm font-medium
              px-4 py-2 rounded-xl border border-white/5 bg-surface2
              hover:text-white hover:border-green/30 transition-all no-underline">
      <span class="material-icons-round text-[18px]">arrow_back</span>Back to Cart
    </a>
    <div class="flex items-center gap-2 bg-surface2 border border-white/5 rounded-xl px-4 py-2">
      <span class="material-icons-round text-green text-[18px]">shopping_cart</span>
      <span class="font-syne font-bold text-sm">
        <?= $cartCount ?> item<?= $cartCount !== 1 ? 's' : '' ?>
      </span>
    </div>
  </div>
</nav>

<!-- ═══════ PROGRESS BAR ═══════ -->
<div class="relative z-10 max-w-5xl mx-auto px-5 md:px-10 pt-8 pb-2">
  <div class="flex items-center gap-2 mb-8">

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-green/20 border border-green/40 flex items-center justify-center">
        <span class="material-icons-round text-green text-sm">check</span>
      </div>
      <span class="text-xs text-muted hidden sm:block">Cart</span>
    </div>

    <div class="flex-1 h-px bg-green/30 mx-1"></div>

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-green flex items-center justify-center">
        <span class="font-syne font-bold text-bg text-xs">2</span>
      </div>
      <span class="text-xs text-green font-semibold hidden sm:block">Checkout</span>
    </div>

    <div class="flex-1 h-px bg-white/10 mx-1"></div>

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-surface2 border border-white/10 flex items-center justify-center">
        <span class="font-syne font-bold text-muted text-xs">3</span>
      </div>
      <span class="text-xs text-muted hidden sm:block">Confirmation</span>
    </div>

  </div>

  <div class="mb-8">
    <div class="inline-flex items-center gap-2 bg-green/10 border border-green/25 text-green
                text-xs font-semibold uppercase tracking-wider px-3 py-1.5 rounded-full mb-3">
      <span class="material-icons-round text-[13px]">payments</span>Checkout
    </div>
    <h1 class="font-syne font-extrabold text-2xl md:text-3xl tracking-tight">Complete your order</h1>
  </div>
</div>

<!-- ═══════ FORM — posts back to this same file ═══════ -->
<form method="POST" action="checkout.php" id="checkoutForm">

  <!-- Price inputs -->
  <input type="hidden" name="subtotal"            value="<?= $subtotal ?>">
  <input type="hidden" name="delivery_fee"        value="<?= $delivery_fee ?>">
  <input type="hidden" name="platform_fee"        value="<?= $platform_fee ?>">
  <input type="hidden" name="total"               value="<?= $total ?>">

  <!-- Razorpay fields — filled by JS after payment succeeds -->
  <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
  <input type="hidden" name="razorpay_order_id"   id="razorpay_order_id">
  <input type="hidden" name="razorpay_signature"  id="razorpay_signature">

  <div class="relative z-10 max-w-5xl mx-auto px-5 md:px-10 pb-24">
    <div class="grid lg:grid-cols-3 gap-6 items-start">

      <!-- ── LEFT COLUMN ── -->
      <div class="lg:col-span-2 space-y-5">

        <!-- 1. Contact Details -->
        <div class="section-card animate-fade-up bg-surface border border-white/5 rounded-2xl p-6">
          <h2 class="font-syne font-bold text-base mb-5 flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-green/10 border border-green/25 flex items-center justify-center">
              <span class="material-icons-round text-green text-sm">person</span>
            </span>
            Contact Details
          </h2>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-muted mb-1.5 block">Full Name</label>
              <input type="text" name="full_name" id="customer_name" class="zform-input"
                     placeholder="Your full name" required
                     value="<?= htmlspecialchars($user['name'] ?? '') ?>">
            </div>
            <div>
              <label class="text-xs text-muted mb-1.5 block">Phone Number</label>
              <input type="tel" name="phone" id="customer_phone" class="zform-input"
                     placeholder="+91 XXXXX XXXXX" required
                     value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="sm:col-span-2">
              <label class="text-xs text-muted mb-1.5 block">Email</label>
              <input type="email" name="email" id="customer_email" class="zform-input"
                     placeholder="you@example.com"
                     value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
          </div>
        </div>

        <!-- 2. Delivery Address -->
        <div class="section-card animate-fade-up bg-surface border border-white/5 rounded-2xl p-6">
          <h2 class="font-syne font-bold text-base mb-5 flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-green/10 border border-green/25 flex items-center justify-center">
              <span class="material-icons-round text-green text-sm">location_on</span>
            </span>
            Delivery Address
          </h2>

          <!-- Tabs -->
          <div class="flex gap-2 mb-5">
            <button type="button" id="tabSaved" onclick="switchAddr('saved')"
                    class="addr-tab active flex items-center gap-1.5 text-xs font-semibold px-4 py-2
                           rounded-xl border border-white/5 bg-surface2 transition-all">
              <span class="material-icons-round text-sm">bookmark</span>Saved Address
            </button>
            <button type="button" id="tabNew" onclick="switchAddr('new')"
                    class="addr-tab flex items-center gap-1.5 text-xs font-semibold px-4 py-2
                           rounded-xl border border-white/5 bg-surface2 text-muted transition-all">
              <span class="material-icons-round text-sm">add_location_alt</span>New Address
            </button>
          </div>

          <!-- Saved address panel -->
          <div id="panelSaved">
            <?php if (!empty($user['address'])): ?>
              <div class="flex items-start gap-3 p-4 bg-surface2 border border-green/20 rounded-xl mb-3">
                <span class="material-icons-round text-green mt-0.5 text-base">home</span>
                <div>
                  <p class="text-sm font-medium"><?= htmlspecialchars($user['name'] ?? '') ?></p>
                  <p class="text-xs text-muted mt-0.5 leading-relaxed"><?= nl2br(htmlspecialchars($user['address'])) ?></p>
                  <?php if (!empty($user['phone'])): ?>
                    <p class="text-xs text-muted mt-1"><?= htmlspecialchars($user['phone']) ?></p>
                  <?php endif; ?>
                </div>
                <span class="ml-auto text-[10px] bg-green/10 text-green border border-green/20
                             px-2 py-0.5 rounded-full font-semibold flex-shrink-0">Default</span>
              </div>
              <input type="hidden" name="address" id="savedAddressVal"
                     value="<?= htmlspecialchars($user['address']) ?>">
              <p class="text-xs text-muted">Delivering to your saved address.
                Switch to <b class="text-white/60">New Address</b> to use a different one.</p>
            <?php else: ?>
              <p class="text-sm text-muted py-4 text-center">No saved address found.</p>
              <script>document.addEventListener('DOMContentLoaded', () => switchAddr('new'));</script>
            <?php endif; ?>
          </div>

          <!-- New address panel -->
          <div id="panelNew" class="hidden space-y-4">
            <div>
              <label class="text-xs text-muted mb-1.5 block">House / Flat / Building</label>
              <input type="text" name="address_line1" class="zform-input"
                     placeholder="House No., Building Name">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="text-xs text-muted mb-1.5 block">Area / Street</label>
                <input type="text" name="address_area" class="zform-input" placeholder="Street or locality">
              </div>
              <div>
                <label class="text-xs text-muted mb-1.5 block">City</label>
                <input type="text" name="address_city" class="zform-input" placeholder="City">
              </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="text-xs text-muted mb-1.5 block">State</label>
                <input type="text" name="address_state" class="zform-input" placeholder="State">
              </div>
              <div>
                <label class="text-xs text-muted mb-1.5 block">PIN Code</label>
                <input type="text" name="address_pin" class="zform-input"
                       placeholder="6-digit PIN" maxlength="6">
              </div>
            </div>
            <div>
              <label class="text-xs text-muted mb-1.5 block">
                Delivery Instructions <span class="text-white/20">(optional)</span>
              </label>
              <textarea name="address_note" class="zform-input"
                        placeholder="Landmark, gate code, special instructions…"></textarea>
            </div>
          </div>
        </div>

        <!-- 3. Payment Method -->
        <div class="section-card animate-fade-up bg-surface border border-white/5 rounded-2xl p-6">
          <h2 class="font-syne font-bold text-base mb-5 flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-green/10 border border-green/25 flex items-center justify-center">
              <span class="material-icons-round text-green text-sm">credit_card</span>
            </span>
            Payment Method
          </h2>

          <div class="space-y-3">

            <!-- Cash on Delivery -->
            <div class="pay-card">
              <input type="radio" name="payment_method" id="pay_cod"
                     value="Cash on Delivery" checked onchange="toggleRazorpayPanel()">
              <label for="pay_cod">
                <span class="radio-dot"></span>
                <span class="text-2xl">💵</span>
                <div class="flex-1">
                  <p class="font-semibold text-sm">Cash on Delivery</p>
                  <p class="text-xs text-muted mt-0.5">Pay when your order arrives</p>
                </div>
                <span class="text-[10px] bg-green/10 text-green border border-green/20
                             px-2 py-0.5 rounded-full font-semibold">Available</span>
              </label>
            </div>

            <!-- Pay Online (UPI / Card / Netbanking — all handled inside Razorpay) -->
            <div class="pay-card">
              <input type="radio" name="payment_method" id="pay_online"
                     value="Online Payment" onchange="toggleRazorpayPanel()">
              <label for="pay_online">
                <span class="radio-dot"></span>
                <span class="text-2xl">💳</span>
                <div class="flex-1">
                  <p class="font-semibold text-sm">Pay Online</p>
                  <p class="text-xs text-muted mt-0.5">UPI, Card, Netbanking &amp; more</p>
                </div>
                <span class="text-[10px] bg-blue-500/10 text-blue-400 border border-blue-500/20
                             px-2 py-0.5 rounded-full font-semibold">Razorpay</span>
              </label>
            </div>

          </div>

          <!-- Razorpay info — shown when Pay Online is selected -->
          <div id="razorpay-panel" class="hidden">
            <div class="flex items-center gap-2 mb-2">
              <span class="material-icons-round text-blue-400 text-sm">lock</span>
              <p class="text-xs text-blue-300 font-semibold">Powered by Razorpay — 100% secure</p>
            </div>
            <p class="text-xs text-muted leading-relaxed">
              A secure Razorpay payment window will open after clicking
              <b class="text-white/70">Place Order</b>, where you can pay via
              UPI, Card, or Netbanking.
              Your card / UPI details are never stored on Zomazon's servers.
            </p>
          </div>

        </div>
      </div><!-- /left col -->

      <!-- ── RIGHT COLUMN — Order Summary ── -->
      <div class="lg:col-span-1">
        <div class="bg-surface border border-white/5 rounded-2xl p-6 sticky top-24 animate-slide-in">

          <h2 class="font-syne font-bold text-base mb-5 flex items-center gap-2">
            <span class="material-icons-round text-green text-[18px]">receipt_long</span>
            Order Summary
          </h2>

          <!-- Cart items -->
          <div class="space-y-3 mb-5 max-h-52 overflow-y-auto pr-1">
            <?php foreach ($_SESSION['cart'] as $id => $item):
              $fromSeller = !empty($item['from_seller']);
              $imgSrc = ($fromSeller ? "Seller/uploads/" : "images/")
                        . htmlspecialchars($item['image'] ?? '');
            ?>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg overflow-hidden bg-surface2 flex-shrink-0">
                <img src="<?= $imgSrc ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>"
                     class="w-full h-full object-cover"
                     onerror="this.src='https://placehold.co/40x40/1a2235/64748b?text=?'">
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate"><?= htmlspecialchars($item['name']) ?></p>
                <p class="text-xs text-muted">× <?= $item['qty'] ?></p>
              </div>
              <span class="text-sm font-semibold flex-shrink-0">
                ₹<?= number_format($item['price'] * $item['qty'], 2) ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Price breakdown -->
          <div class="border-t border-white/5 pt-4 space-y-3 mb-5">

            <div class="flex justify-between text-sm">
              <span class="text-muted">Subtotal</span>
              <span>₹<?= number_format($subtotal, 2) ?></span>
            </div>

            <div class="flex justify-between text-sm">
              <span class="text-muted flex items-center gap-1.5">
                Delivery
                <?php if ($delivery_fee === 0): ?>
                  <span class="text-[10px] bg-green/10 text-green border border-green/20
                               px-1.5 py-0.5 rounded-full font-bold">FREE</span>
                <?php endif; ?>
              </span>
              <span class="<?= $delivery_fee === 0 ? 'line-through text-muted text-xs self-center' : '' ?>">
                ₹<?= $delivery_fee === 0 ? '40.00' : number_format($delivery_fee, 2) ?>
              </span>
            </div>

            <?php if ($delivery_fee > 0): ?>
            <div class="bg-surface2 border border-white/5 rounded-xl px-3 py-2.5 text-xs text-muted flex items-center gap-2">
              <span class="material-icons-round text-amber-400 text-sm flex-shrink-0">info</span>
              Add ₹<?= number_format($free_delivery_threshold - $subtotal, 2) ?> more for free delivery
            </div>
            <?php endif; ?>

            <div class="flex justify-between text-sm">
              <span class="text-muted flex items-center gap-1">
                Platform Fee
                <span class="material-icons-round text-muted/50 text-xs">help_outline</span>
              </span>
              <span>₹<?= number_format($platform_fee, 2) ?></span>
            </div>

          </div>

          <!-- Grand total -->
          <div class="border-t border-white/5 pt-4 mb-6">
            <div class="flex items-center justify-between">
              <span class="font-syne font-bold">Total</span>
              <span class="font-syne font-extrabold text-green text-2xl">
                ₹<?= number_format($total, 2) ?>
              </span>
            </div>
            <?php if ($delivery_fee === 0): ?>
              <p class="text-xs text-green mt-1.5 text-right">🎉 You saved ₹40 on delivery!</p>
            <?php endif; ?>
          </div>

          <!-- Place Order button -->
          <button type="button" id="placeOrderBtn" onclick="handlePlaceOrder()"
                  class="w-full flex items-center justify-center gap-2 bg-green text-bg
                         font-syne font-bold text-base py-3.5 rounded-xl
                         hover:bg-green/90 transition-all hover:scale-[1.02]
                         active:scale-[0.98] cursor-pointer border-0">
            <span class="material-icons-round text-[20px]">check_circle</span>
            Place Order
          </button>

          <p class="text-center text-muted text-xs mt-4 flex items-center justify-center gap-1">
            <span class="material-icons-round text-sm">lock</span>
            Safe &amp; secure checkout
          </p>

        </div>
      </div>

    </div>
  </div>
</form>

<!-- ═══════ FOOTER ═══════ -->
<footer class="border-t border-white/5 py-6 text-center text-muted text-xs relative z-10">
  © <?= date('Y') ?> Zomazon. All rights reserved.
</footer>

<script>
const RAZORPAY_KEY  = '<?= RZP_KEY_ID ?>';
const ORDER_TOTAL_P = <?= intval($total * 100) ?>;  // paise

// ── Show / hide Razorpay info panel ──────────────────────────────────────────
function toggleRazorpayPanel() {
  const method = document.querySelector('input[name="payment_method"]:checked').value;
  document.getElementById('razorpay-panel').classList.toggle('hidden', method === 'Cash on Delivery');
}

// ── Address tab switcher ──────────────────────────────────────────────────────
function switchAddr(tab) {
  const panelSaved  = document.getElementById('panelSaved');
  const panelNew    = document.getElementById('panelNew');
  const savedHidden = document.getElementById('savedAddressVal');
  const newFields   = panelNew ? panelNew.querySelectorAll('input, textarea') : [];

  if (tab === 'saved') {
    panelSaved.classList.remove('hidden');
    panelNew.classList.add('hidden');
    document.getElementById('tabSaved').classList.add('active');
    document.getElementById('tabNew').classList.remove('active');
    if (savedHidden) savedHidden.disabled = false;
    newFields.forEach(f => f.removeAttribute('required'));
  } else {
    panelSaved.classList.add('hidden');
    panelNew.classList.remove('hidden');
    document.getElementById('tabNew').classList.add('active');
    document.getElementById('tabSaved').classList.remove('active');
    if (savedHidden) savedHidden.disabled = true;
    const line1 = document.querySelector('input[name="address_line1"]');
    if (line1) line1.setAttribute('required', 'required');
  }
}

// ── Main button handler ───────────────────────────────────────────────────────
function handlePlaceOrder() {
  const form = document.getElementById('checkoutForm');

  // Validate all required fields first
  if (!form.reportValidity()) return;

  const method = document.querySelector('input[name="payment_method"]:checked').value;

  // COD — submit directly
  if (method === 'Cash on Delivery') {
    setLoading('Placing Order…');
    form.submit();
    return;
  }

  // Online payment — call this same file with AJAX header
  setLoading('Opening Payment…');

  fetch('checkout.php', {
    method:  'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Request':    'razorpay-order'   // PHP checks this at the very top
    },
    body: JSON.stringify({ amount: ORDER_TOTAL_P })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.id) throw new Error(data.error || 'Razorpay order creation failed');

    const rzp = new Razorpay({
      key:         RAZORPAY_KEY,
      amount:      data.amount,
      currency:    'INR',
      name:        'Zomazon',
      description: 'Order Payment',
      order_id:    data.id,

      prefill: {
        name:    document.getElementById('customer_name').value,
        email:   document.getElementById('customer_email').value,
        contact: document.getElementById('customer_phone').value,
      },

      theme: { color: '#22c55e' },

      handler: function(response) {
        // Payment succeeded — store IDs in hidden fields and submit the form
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.getElementById('razorpay_order_id').value   = response.razorpay_order_id;
        document.getElementById('razorpay_signature').value  = response.razorpay_signature;
        setLoading('Confirming Order…');
        form.submit();
      },

      modal: {
        ondismiss: function() {
          // User closed the popup without paying
          resetButton();
        }
      }
    });

    rzp.on('payment.failed', function(response) {
      alert('Payment failed: ' + response.error.description);
      resetButton();
    });

    rzp.open();
  })
  .catch(err => {
    alert('Could not initiate payment. Please try again.\n' + err.message);
    resetButton();
  });
}

// ── Button helpers ────────────────────────────────────────────────────────────
function setLoading(msg) {
  const btn = document.getElementById('placeOrderBtn');
  btn.disabled = true;
  btn.innerHTML = `
    <span class="material-icons-round text-[20px]"
          style="display:inline-block; animation:spin 1s linear infinite">autorenew</span>
    &nbsp;${msg}`;
}

function resetButton() {
  const btn = document.getElementById('placeOrderBtn');
  btn.disabled = false;
  btn.innerHTML = `
    <span class="material-icons-round text-[20px]">check_circle</span>
    Place Order`;
}
</script>

</body>
</html>