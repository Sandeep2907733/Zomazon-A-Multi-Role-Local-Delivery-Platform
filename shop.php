<?php
include "config/db.php";

$shop_id = intval($_GET['id'] ?? 0);

if (!$shop_id) {
    header("Location: index.php");
    exit;
}

// ── ADD TO CART ──────────────────────────────────────────────────────────────
if (isset($_GET['add'])) {
    $add_id     = intval($_GET['add']);
    $fromSeller = isset($_GET['from_seller']) && $_GET['from_seller'] == '1';

    $ps = $conn->prepare("
        SELECT p.id, p.name, p.price, p.image, p.category,
               ls.shop_name
        FROM products p
        LEFT JOIN local_shops ls ON ls.id = p.shop_id
        WHERE p.id = ?
    ");
    $ps->bind_param("i", $add_id);
    $ps->execute();
    $product = $ps->get_result()->fetch_assoc();

    if ($product) {
        if (isset($_SESSION['cart'][$add_id])) {
            $_SESSION['cart'][$add_id]['qty']++;
        } else {
            $_SESSION['cart'][$add_id] = [
                'name'        => $product['name'],
                'price'       => $product['price'],
                'image'       => $product['image'],
                'shop_name'   => $product['shop_name'] ?? $product['category'],
                'qty'         => 1,
                'from_seller' => $fromSeller,
            ];
        }
    }

    header("Location: cart.php?added=1");
    exit();
}

/* CART COUNT */
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['qty'];
    }
}

// Fetch shop details
$stmt = $conn->prepare("SELECT * FROM local_shops WHERE id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();

if (!$shop) {
    header("Location: index.php");
    exit;
}

// Fetch products for this shop
$stmt2 = $conn->prepare("SELECT * FROM products WHERE shop_id = ? ORDER BY id DESC");
$stmt2->bind_param("i", $shop_id);
$stmt2->execute();
$products = $stmt2->get_result();

// ── REVIEWS: handle new review submission ─────────────────────────────────────
$review_error   = '';
$review_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit'])) {

    if (!isset($_SESSION['user_id'])) {
        $review_error = "Please log in to leave a review.";
    } else {
        $rating  = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $review_error = "Please select a rating between 1 and 5.";
        } elseif ($comment === '') {
            $review_error = "Please write a short review.";
        } else {
            $rev_uid = intval($_SESSION['user_id']);

            $un = $conn->prepare("SELECT name FROM users WHERE p_id = ?");
            $un->bind_param("i", $rev_uid);
            $un->execute();
            $un->bind_result($rev_name);
            $un->fetch();
            $un->close();
            $rev_name = $rev_name ?: 'Anonymous';

            $ins = $conn->prepare("
                INSERT INTO shop_reviews (shop_id, user_id, user_name, rating, comment, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $ins->bind_param("iisis", $shop_id, $rev_uid, $rev_name, $rating, $comment);

            if ($ins->execute()) {
                $review_success = "Thanks for your review!";
            } else {
                $review_error = "Could not submit review. Please try again.";
            }
            $ins->close();
        }
    }
}

// ── REVIEWS: fetch all reviews + average rating ───────────────────────────────
$reviews_stmt = $conn->prepare("
    SELECT user_name, rating, comment, created_at
    FROM shop_reviews
    WHERE shop_id = ?
    ORDER BY created_at DESC
");
$reviews_stmt->bind_param("i", $shop_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();
$reviews_count  = $reviews_result->num_rows;

$avg_stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM shop_reviews WHERE shop_id = ?");
$avg_stmt->bind_param("i", $shop_id);
$avg_stmt->execute();
$avg_row      = $avg_stmt->get_result()->fetch_assoc();
$avg_rating   = $avg_row['avg_rating'] ? round($avg_row['avg_rating'], 1) : 0;
$total_reviews = intval($avg_row['total']);

// Rating distribution (5★ → 1★)
$dist_stmt = $conn->prepare("SELECT rating, COUNT(*) AS c FROM shop_reviews WHERE shop_id = ? GROUP BY rating");
$dist_stmt->bind_param("i", $shop_id);
$dist_stmt->execute();
$dist_raw    = $dist_stmt->get_result();
$rating_dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
while ($d = $dist_raw->fetch_assoc()) {
    $rating_dist[intval($d['rating'])] = intval($d['c']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($shop['shop_name']) ?> — Local Goods</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:        #0b0f1a;
  --surface:   #111827;
  --surface2:  #1a2235;
  --border:    rgba(255,255,255,0.07);
  --green:     #22c55e;
  --green-dim: rgba(34,197,94,0.12);
  --text:      #f1f5f9;
  --muted:     #64748b;
  --card-shadow: 0 8px 32px rgba(0,0,0,0.4);
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}

body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 0;
}

.glow-blob {
  position: fixed;
  width: 700px; height: 700px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(34,197,94,0.07) 0%, transparent 70%);
  top: -200px; left: -200px;
  pointer-events: none;
  z-index: 0;
}

/* ── Navbar ── */
.navbar {
  position: sticky;
  top: 0; z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 40px;
  height: 68px;
  background: rgba(11,15,26,0.85);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
}

.logo {
  font-family: 'Syne', sans-serif;
  font-size: 20px; font-weight: 800;
  color: var(--text);
  letter-spacing: -0.5px;
  display: flex; align-items: center; gap: 8px;
  text-decoration: none;
}
.logo span { color: var(--green); }
.logo-icon {
  width: 32px; height: 32px;
  background: var(--green-dim);
  border: 1px solid rgba(34,197,94,0.3);
  border-radius: 8px;
  display: grid; place-items: center;
  font-size: 16px;
}

.back-btn {
  display: inline-flex; align-items: center; gap: 6px;
  color: var(--muted); font-size: 14px; font-weight: 500;
  text-decoration: none;
  padding: 8px 14px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: var(--surface2);
  transition: color 0.2s, border-color 0.2s;
}
.back-btn:hover { color: var(--text); border-color: rgba(34,197,94,0.3); }
.back-btn .material-icons-round { font-size: 18px; }

/* ── Shop Banner ── */
.shop-banner {
  position: relative; z-index: 1;
  height: 260px; overflow: hidden;
  background: var(--surface2);
}
.shop-banner img {
  width: 100%; height: 100%;
  object-fit: cover;
  filter: brightness(0.35);
}
.shop-banner .banner-fallback {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, var(--surface2) 0%, #0d1525 100%);
}
.banner-fallback .material-icons-round { font-size: 64px; color: var(--border); }
.shop-banner-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(11,15,26,1) 0%, rgba(11,15,26,0.2) 60%, transparent 100%);
}

/* ── Shop Info Card ── */
.shop-info-wrap {
  position: relative; z-index: 2;
  max-width: 1200px;
  margin: -70px auto 0;
  padding: 0 40px;
}

.shop-info-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 28px 32px;
  display: flex; align-items: flex-start;
  justify-content: space-between;
  gap: 24px; flex-wrap: wrap;
}

.shop-avatar {
  width: 72px; height: 72px;
  border-radius: 16px;
  background: var(--green-dim);
  border: 2px solid rgba(34,197,94,0.3);
  display: grid; place-items: center;
  font-size: 32px; flex-shrink: 0;
}

.shop-details { display: flex; gap: 20px; flex: 1; min-width: 0; }
.shop-text { flex: 1; }
.shop-name {
  font-family: 'Syne', sans-serif;
  font-size: 24px; font-weight: 800;
  letter-spacing: -0.5px; margin-bottom: 10px;
}

.shop-meta { display: flex; flex-wrap: wrap; gap: 16px; }
.shop-meta-item {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; color: var(--muted);
}
.shop-meta-item .material-icons-round { font-size: 16px; color: var(--green); }

.shop-badge {
  background: var(--green-dim);
  border: 1px solid rgba(34,197,94,0.25);
  color: var(--green);
  font-size: 12px; font-weight: 600;
  padding: 4px 12px; border-radius: 100px;
  white-space: nowrap; align-self: flex-start;
  text-transform: uppercase; letter-spacing: 0.5px;
}

/* ── Products Section ── */
.products-wrap {
  position: relative; z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 40px 60px;
}

.section-header {
  display: flex; align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border);
}
.section-title {
  font-family: 'Syne', sans-serif;
  font-size: 20px; font-weight: 700;
}
.section-title span {
  color: var(--green); font-size: 14px;
  margin-left: 10px;
  background: var(--green-dim);
  padding: 2px 10px; border-radius: 100px; font-weight: 600;
}

.search-bar { position: relative; width: 240px; }
.search-bar input {
  width: 100%;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  padding: 9px 14px 9px 38px;
  outline: none;
  transition: border-color 0.2s;
}
.search-bar input::placeholder { color: var(--muted); }
.search-bar input:focus { border-color: rgba(34,197,94,0.4); }
.search-bar .material-icons-round {
  position: absolute; left: 11px; top: 50%;
  transform: translateY(-50%);
  font-size: 18px; color: var(--muted); pointer-events: none;
}

/* ── Product Grid ── */
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 18px;
}

/* ── Product Card ── */
.product-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px; overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
  animation: fadeUp 0.5s ease both;
  cursor: default;
}
.product-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--card-shadow), 0 0 0 1px rgba(34,197,94,0.12);
  border-color: rgba(34,197,94,0.18);
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
.product-card:nth-child(1) { animation-delay: 0.05s; }
.product-card:nth-child(2) { animation-delay: 0.10s; }
.product-card:nth-child(3) { animation-delay: 0.15s; }
.product-card:nth-child(4) { animation-delay: 0.20s; }
.product-card:nth-child(5) { animation-delay: 0.25s; }
.product-card:nth-child(6) { animation-delay: 0.30s; }

.product-img {
  height: 160px; overflow: hidden;
  background: var(--surface2); position: relative;
}
.product-img img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform 0.5s ease; display: block;
}
.product-card:hover .product-img img { transform: scale(1.07); }
.product-img .img-fallback {
  display: none; width: 100%; height: 100%;
  align-items: center; justify-content: center;
  flex-direction: column; gap: 6px;
  color: var(--muted); font-size: 12px;
}
.product-img img.broken + .img-fallback { display: flex; }
.product-img .img-fallback .material-icons-round { font-size: 32px; color: var(--border); }

.price-badge {
  position: absolute; top: 10px; right: 10px;
  background: rgba(11,15,26,0.85);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(34,197,94,0.3);
  color: var(--green);
  font-family: 'Syne', sans-serif;
  font-size: 14px; font-weight: 700;
  padding: 3px 10px; border-radius: 8px;
}

.product-body { padding: 14px 16px 18px; }
.product-name {
  font-family: 'Syne', sans-serif;
  font-size: 15px; font-weight: 700;
  margin-bottom: 6px; line-height: 1.3;
}
.product-desc {
  font-size: 12px; color: var(--muted);
  line-height: 1.5; margin-bottom: 14px;
  display: -webkit-box;
  -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}

.product-footer {
  display: flex; align-items: center;
  justify-content: space-between; gap: 8px;
}

.unit-label {
  font-size: 11px; color: var(--muted);
  background: var(--surface2); padding: 3px 8px; border-radius: 6px;
}

.add-btn {
  display: inline-flex; align-items: center; gap: 4px;
  background: var(--green); color: #0b0f1a;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px; font-weight: 600;
  padding: 7px 14px; border-radius: 8px;
  border: none; cursor: pointer;
  transition: background 0.2s, transform 0.15s;
  text-decoration: none;
}
.add-btn:hover { background: #16a34a; transform: scale(1.04); }
.add-btn .material-icons-round { font-size: 16px; }

.stock-in  { color: var(--green); font-size: 11px; display: flex; align-items: center; gap: 4px; }
.stock-out { color: #ef4444; font-size: 11px; display: flex; align-items: center; gap: 4px; }
.dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

.empty {
  grid-column: 1/-1;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 80px 20px; text-align: center; color: var(--muted);
}
.empty .material-icons-round { font-size: 52px; margin-bottom: 14px; color: var(--border); }
.empty h3 { font-family: 'Syne', sans-serif; font-size: 18px; color: var(--text); margin-bottom: 6px; }

/* ── Reviews Section ── */
.reviews-wrap {
  position: relative; z-index: 1;
  max-width: 1200px; margin: 0 auto;
  padding: 0 40px 60px;
}

.reviews-summary {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 32px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 28px; margin-bottom: 28px;
}

.rs-score {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  text-align: center;
  border-right: 1px solid var(--border);
  padding-right: 28px;
}
.rs-score-num {
  font-family: 'Syne', sans-serif;
  font-size: 44px; font-weight: 800;
  color: var(--text); line-height: 1;
}
.rs-stars { color: #fbbf24; font-size: 18px; margin: 8px 0 6px; letter-spacing: 2px; }
.rs-count { font-size: 12px; color: var(--muted); }

.rs-bars { display: flex; flex-direction: column; gap: 8px; justify-content: center; }
.rs-bar-row { display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--muted); }
.rs-bar-row .star-label {
  width: 36px; flex-shrink: 0;
  display: flex; align-items: center; gap: 3px; color: var(--text);
}
.rs-bar-row .star-label .material-icons-round { font-size: 13px; color: #fbbf24; }
.rs-bar-track { flex: 1; height: 7px; border-radius: 100px; background: var(--surface2); overflow: hidden; }
.rs-bar-fill { height: 100%; background: var(--green); border-radius: 100px; transition: width 0.4s ease; }
.rs-bar-row .rs-bar-count { width: 28px; text-align: right; flex-shrink: 0; }

/* ── Review Form ── */
.review-form {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 24px 28px; margin-bottom: 28px;
}
.review-form h3 {
  font-family: 'Syne', sans-serif;
  font-size: 16px; font-weight: 700; margin-bottom: 16px;
}

/* ── Star Rating Input (FIXED) ── */
.star-input {
  display: flex;
  gap: 6px;
  margin-bottom: 8px;
}
.star-input input[type="radio"] { display: none; }
.star-input label {
  font-size: 32px;
  color: var(--surface2);
  cursor: pointer;
  transition: color 0.12s, transform 0.12s;
  line-height: 1;
  user-select: none;
}
.star-input label.active { color: #fbbf24; }
.star-input label:hover  { color: #fbbf24; transform: scale(1.12); }

.star-hint {
  font-size: 13px;
  color: var(--muted);
  margin-bottom: 16px;
  min-height: 18px;
  transition: color 0.15s;
}

.review-form textarea {
  width: 100%;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  padding: 12px 14px;
  outline: none; resize: vertical;
  min-height: 80px; margin-bottom: 14px;
  transition: border-color 0.2s;
}
.review-form textarea:focus { border-color: rgba(34,197,94,0.4); }
.review-form textarea::placeholder { color: var(--muted); }

.review-submit-btn {
  background: var(--green); color: #0b0f1a;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px; font-weight: 600;
  border: none; border-radius: 10px;
  padding: 11px 24px; cursor: pointer;
  transition: background 0.2s, transform 0.15s;
}
.review-submit-btn:hover { background: #16a34a; transform: scale(1.02); }

.review-msg {
  font-size: 13px; border-radius: 10px;
  padding: 10px 14px; margin-bottom: 14px;
}
.review-msg.success { background: var(--green-dim); color: var(--green); border: 1px solid rgba(34,197,94,0.25); }
.review-msg.error   { background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.25); }

.review-login-note {
  font-size: 13px; color: var(--muted);
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 14px; text-align: center;
}
.review-login-note a { color: var(--green); text-decoration: none; font-weight: 600; }

/* ── Review List ── */
.review-list { display: flex; flex-direction: column; gap: 16px; }
.review-item {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px; padding: 18px 22px;
}
.review-item-head {
  display: flex; align-items: center;
  justify-content: space-between;
  margin-bottom: 8px; flex-wrap: wrap; gap: 8px;
}
.review-author { display: flex; align-items: center; gap: 10px; }
.review-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--green-dim);
  border: 1px solid rgba(34,197,94,0.3);
  display: grid; place-items: center;
  font-family: 'Syne', sans-serif; font-weight: 700;
  font-size: 14px; color: var(--green); flex-shrink: 0;
}
.review-author-name { font-size: 14px; font-weight: 600; color: var(--text); }
.review-date { font-size: 12px; color: var(--muted); }
.review-stars { color: #fbbf24; font-size: 14px; letter-spacing: 1px; }
.review-comment { font-size: 14px; color: var(--muted); line-height: 1.6; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .navbar { padding: 0 20px; }
  .shop-info-wrap, .products-wrap, .reviews-wrap { padding-left: 20px; padding-right: 20px; }
  .shop-info-card { padding: 20px; }
  .shop-name { font-size: 20px; }
  .section-header { flex-direction: column; align-items: flex-start; gap: 12px; }
  .search-bar { width: 100%; }
  .shop-banner { height: 180px; }
  .shop-info-wrap { margin-top: -50px; }
  .reviews-summary { grid-template-columns: 1fr; }
  .rs-score { border-right: none; border-bottom: 1px solid var(--border); padding-right: 0; padding-bottom: 20px; }
}
</style>
</head>
<body>

<div class="glow-blob"></div>

<!-- Navbar -->
<nav class="navbar">
  <a href="index.php" class="logo">
    <div class="logo-icon">🌿</div>
    Local<span>Goods</span>
  </a>
  <a href="index.php" class="back-btn">
    <span class="material-icons-round">arrow_back</span>
    Back to Shops
  </a>
</nav>

<!-- Banner -->
<div class="shop-banner">
  <?php if (!empty($shop['image'])): ?>
    <img
      src="uploads/<?= htmlspecialchars($shop['image']) ?>"
      alt="<?= htmlspecialchars($shop['shop_name']) ?>"
      onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
    >
    <div class="banner-fallback" style="display:none; position:absolute; inset:0;">
      <span class="material-icons-round">storefront</span>
    </div>
  <?php else: ?>
    <div class="banner-fallback">
      <span class="material-icons-round">storefront</span>
    </div>
  <?php endif; ?>
  <div class="shop-banner-overlay"></div>
</div>

<!-- Shop Info -->
<div class="shop-info-wrap">
  <div class="shop-info-card">
    <div class="shop-details">
      <div class="shop-avatar">🏪</div>
      <div class="shop-text">
        <div class="shop-name"><?= htmlspecialchars($shop['shop_name']) ?></div>
        <div class="shop-meta">
          <div class="shop-meta-item">
            <span class="material-icons-round">person</span>
            <?= htmlspecialchars($shop['owner_name']) ?>
          </div>
          <div class="shop-meta-item">
            <span class="material-icons-round">location_on</span>
            <?= htmlspecialchars($shop['area']) ?>, <?= htmlspecialchars($shop['city']) ?>
          </div>
          <div class="shop-meta-item">
            <span class="material-icons-round">call</span>
            <?= htmlspecialchars($shop['phone']) ?>
          </div>
        </div>
      </div>
    </div>
    <div class="shop-badge">✓ Verified Shop</div>
  </div>
</div>

<!-- Products -->
<div class="products-wrap">
  <?php $count = $products->num_rows; ?>
  <div class="section-header">
    <div class="section-title">
      Products <span><?= $count ?></span>
    </div>
    <div class="search-bar">
      <span class="material-icons-round">search</span>
      <input type="text" id="searchInput" placeholder="Search products…" oninput="filterProducts(this.value)">
    </div>
  </div>

  <div class="product-grid" id="productGrid">
    <?php if ($count > 0): ?>
      <?php while ($p = $products->fetch_assoc()): ?>
        <div class="product-card" data-name="<?= strtolower(htmlspecialchars($p['name'] ?? $p['product_name'] ?? '')) ?>">
          <div class="product-img">
            <?php $pimg = $p['product_image'] ?? $p['image'] ?? ''; ?>
            <img
              src="Seller/uploads/<?= htmlspecialchars($pimg) ?>"
              alt="<?= htmlspecialchars($p['name'] ?? $p['product_name'] ?? '') ?>"
              onerror="this.classList.add('broken'); this.style.display='none'; this.nextElementSibling.style.display='flex';"
            >
            <div class="img-fallback" style="display:none">
              <span class="material-icons-round">inventory_2</span>
              No image
            </div>
            <div class="price-badge">₹<?= number_format(floatval($p['price']), 2) ?></div>
          </div>
          <div class="product-body">
            <div class="product-name"><?= htmlspecialchars($p['name'] ?? $p['product_name'] ?? 'Product') ?></div>
            <?php if (!empty($p['description'])): ?>
              <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>
            <?php endif; ?>
            <div class="product-footer">
              <?php $stock = $p['stock'] ?? $p['quantity'] ?? $p['in_stock'] ?? 1; ?>
              <?php if ($stock > 0): ?>
                <span class="stock-in"><span class="dot"></span> In Stock</span>
                <a href="cart.php?add=<?= intval($p['id']) ?>&from_seller=1" class="add-btn">
                  <span class="material-icons-round">add_shopping_cart</span>
                  Add
                </a>
              <?php else: ?>
                <span class="stock-out"><span class="dot"></span> Out of Stock</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty">
        <span class="material-icons-round">inventory_2</span>
        <h3>No products yet</h3>
        <p>This shop hasn't added any products.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Reviews -->
<div class="reviews-wrap">
  <div class="section-header">
    <div class="section-title">
      Reviews <span><?= $total_reviews ?></span>
    </div>
  </div>

  <!-- Rating summary -->
  <div class="reviews-summary">
    <div class="rs-score">
      <div class="rs-score-num"><?= $total_reviews > 0 ? $avg_rating : '—' ?></div>
      <div class="rs-stars">
        <?php
          $full = floor($avg_rating);
          $half = ($avg_rating - $full) >= 0.5;
          for ($i = 1; $i <= 5; $i++) {
              echo ($i <= $full || ($half && $i == $full + 1)) ? '★' : '☆';
          }
        ?>
      </div>
      <div class="rs-count">
        Based on <?= $total_reviews ?> review<?= $total_reviews !== 1 ? 's' : '' ?>
      </div>
    </div>
    <div class="rs-bars">
      <?php for ($star = 5; $star >= 1; $star--):
        $star_count = $rating_dist[$star];
        $pct = $total_reviews > 0 ? round(($star_count / $total_reviews) * 100) : 0;
      ?>
        <div class="rs-bar-row">
          <span class="star-label"><?= $star ?> <span class="material-icons-round">star</span></span>
          <div class="rs-bar-track"><div class="rs-bar-fill" style="width:<?= $pct ?>%"></div></div>
          <span class="rs-bar-count"><?= $star_count ?></span>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Write a review -->
  <div class="review-form">
    <h3>Write a Review</h3>

    <?php if ($review_success): ?>
      <div class="review-msg success"><?= htmlspecialchars($review_success) ?></div>
    <?php elseif ($review_error): ?>
      <div class="review-msg error"><?= htmlspecialchars($review_error) ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
      <form method="POST" action="shop.php?id=<?= $shop_id ?>">
        <!-- Hidden input that JS writes the selected value into -->
        <input type="hidden" name="rating" id="ratingValue" value="0">

        <div class="star-input" id="starInput">
          <!-- No radio inputs needed — JS handles state via the hidden field -->
          <label data-val="1">★</label>
          <label data-val="2">★</label>
          <label data-val="3">★</label>
          <label data-val="4">★</label>
          <label data-val="5">★</label>
        </div>

        <div class="star-hint" id="starHint">Click to rate</div>

        <textarea name="comment" placeholder="Share your experience with this shop…" required></textarea>
        <button type="submit" name="review_submit" value="1" class="review-submit-btn">
          Submit Review
        </button>
      </form>
    <?php else: ?>
      <div class="review-login-note">
        <a href="registration/Login.php">Log in</a> to write a review for this shop.
      </div>
    <?php endif; ?>
  </div>

  <!-- Review list -->
  <div class="review-list">
    <?php if ($reviews_count > 0): while ($r = $reviews_result->fetch_assoc()):
      $initial = strtoupper(substr($r['user_name'], 0, 1));
    ?>
      <div class="review-item">
        <div class="review-item-head">
          <div class="review-author">
            <div class="review-avatar"><?= htmlspecialchars($initial) ?></div>
            <div>
              <div class="review-author-name"><?= htmlspecialchars($r['user_name']) ?></div>
              <div class="review-date"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
            </div>
          </div>
          <div class="review-stars">
            <?= str_repeat('★', intval($r['rating'])) . str_repeat('☆', 5 - intval($r['rating'])) ?>
          </div>
        </div>
        <div class="review-comment"><?= nl2br(htmlspecialchars($r['comment'])) ?></div>
      </div>
    <?php endwhile; else: ?>
      <div class="review-login-note">No reviews yet. Be the first to review this shop!</div>
    <?php endif; ?>
  </div>
</div>

<script>
/* ── Product search ── */
function filterProducts(query) {
  const q = query.toLowerCase().trim();
  document.querySelectorAll('.product-card').forEach(card => {
    card.style.display = (card.dataset.name || '').includes(q) ? '' : 'none';
  });
}

/* ── Star rating (FIXED) ── */
(function () {
  const labels      = document.querySelectorAll('#starInput label');
  const hiddenInput = document.getElementById('ratingValue');
  const hint        = document.getElementById('starHint');

  if (!labels.length || !hiddenInput) return;

  const hintText = ['', 'Terrible', 'Poor', 'Okay', 'Good', 'Excellent'];
  let selected = 0;

  function highlight(upTo) {
    labels.forEach(l => l.classList.toggle('active', parseInt(l.dataset.val) <= upTo));
  }

  labels.forEach(label => {
    const val = parseInt(label.dataset.val);

    label.addEventListener('mouseover', () => {
      highlight(val);
      hint.textContent = hintText[val];
    });

    label.addEventListener('mouseout', () => {
      highlight(selected);
      hint.textContent = selected ? hintText[selected] : 'Click to rate';
    });

    label.addEventListener('click', () => {
      selected = val;
      hiddenInput.value = val;
      highlight(val);
      hint.textContent = hintText[val];
    });
  });
})();
</script>
</body>
</html>