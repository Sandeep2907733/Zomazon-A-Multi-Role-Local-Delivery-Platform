<?php
include "../config/db.php";

if (!isset($_SESSION['shop_id'])) {
    header("Location: shop_login.php");
    exit();
}

$id = intval($_SESSION['shop_id']); // ✅ FIXED: sanitize session ID

// ✅ FIXED: Use prepared statements instead of raw $id in query strings
$stmt = mysqli_prepare($conn, "SELECT * FROM local_shops WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$shop = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$stmt2 = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM orders WHERE shop_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $id);
mysqli_stmt_execute($stmt2);
$total_orders = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

$stmt3 = mysqli_prepare($conn, "SELECT SUM(total) as total FROM orders WHERE shop_id = ?");
mysqli_stmt_bind_param($stmt3, "i", $id);
mysqli_stmt_execute($stmt3);
$total_sales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt3));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seller Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>

  :root {
    --bg: #0f1117;
    --surface: #1a1d27;
    --surface2: #22263a;
    --border: rgba(255,255,255,0.07);
    --accent: #22c55e;
    --accent2: #3b82f6;
    --accent3: #f59e0b;
    --text: #f0f2f8;
    --muted: #7c829a;
    --danger: #ef4444;
  }

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
  }

  /* ── NAVBAR ── */
  .navbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .navbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: 'Syne', sans-serif;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.5px;
  }

  .navbar-brand .dot {
    width: 10px; height: 10px;
    background: var(--accent);
    border-radius: 50%;
    box-shadow: 0 0 10px var(--accent);
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(0.85); }
  }

  .nav-right {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .shop-badge {
    font-size: 13px;
    color: var(--muted);
    background: var(--surface2);
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid var(--border);
  }

  .logout-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    background: rgba(239,68,68,0.12);
    color: var(--danger);
    border: 1px solid rgba(239,68,68,0.25);
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
  }

  .logout-btn:hover {
    background: rgba(239,68,68,0.22);
  }

  /* ── PAGE ── */
  .page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 36px 24px;
  }

  .page-header {
    margin-bottom: 28px;
  }

  .page-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.5px;
  }

  .page-header p {
    color: var(--muted);
    font-size: 14px;
    margin-top: 4px;
  }

  /* ── STAT CARDS ── */
  .stat-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 22px 24px;
    display: flex;
    align-items: center;
    gap: 18px;
    transition: border-color 0.2s, transform 0.2s;
    animation: fadeUp 0.5s ease both;
  }

  .stat-card:nth-child(1) { animation-delay: 0.05s; }
  .stat-card:nth-child(2) { animation-delay: 0.10s; }
  .stat-card:nth-child(3) { animation-delay: 0.15s; }

  .stat-card:hover {
    border-color: rgba(255,255,255,0.15);
    transform: translateY(-2px);
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
  }

  .stat-icon.green { background: rgba(34,197,94,0.12); color: var(--accent); }
  .stat-icon.blue  { background: rgba(59,130,246,0.12); color: var(--accent2); }
  .stat-icon.amber { background: rgba(245,158,11,0.12); color: var(--accent3); }

  .stat-info label {
    font-size: 12px;
    color: var(--muted);
    display: block;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .stat-info strong {
    font-family: 'Syne', sans-serif;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -1px;
  }

  /* ── BOTTOM GRID ── */
  .bottom-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
  }

  /* ── SHOP PROFILE CARD ── */
  .profile-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    animation: fadeUp 0.5s 0.2s ease both;
  }

  .profile-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    display: block;
  }

  .profile-body {
    padding: 20px;
  }

  .profile-body h2 {
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 14px;
    letter-spacing: -0.3px;
  }

  .info-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 13px;
  }

  .info-row .material-icons-round {
    font-size: 16px;
    color: var(--muted);
    margin-top: 1px;
    flex-shrink: 0;
  }

  .info-row span {
    color: var(--muted);
  }

  .info-row strong {
    color: var(--text);
  }

  .category-tag {
    display: inline-block;
    background: rgba(34,197,94,0.1);
    color: var(--accent);
    border: 1px solid rgba(34,197,94,0.25);
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 6px;
  }

  /* ── ACTIONS CARD ── */
  .actions-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    animation: fadeUp 0.5s 0.25s ease both;
  }

  .actions-card h3 {
    font-family: 'Syne', sans-serif;
    font-size: 16px;
    font-weight: 800;
    margin-bottom: 18px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 12px;
  }

  .action-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .action-btn {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 18px;
    border-radius: 12px;
    text-decoration: none;
    color: var(--text);
    border: 1px solid var(--border);
    background: var(--surface2);
    transition: all 0.2s;
    group: true;
  }

  .action-btn:hover {
    border-color: rgba(255,255,255,0.15);
    transform: translateX(4px);
  }

  .action-btn .action-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
  }

  .action-btn.green .action-icon { background: rgba(34,197,94,0.12); color: var(--accent); }
  .action-btn.blue  .action-icon { background: rgba(59,130,246,0.12); color: var(--accent2); }
  .action-btn.amber .action-icon { background: rgba(245,158,11,0.12); color: var(--accent3); }

  .action-btn .action-text strong {
    display: block;
    font-size: 14px;
    font-weight: 600;
  }

  .action-btn .action-text span {
    font-size: 12px;
    color: var(--muted);
  }

  .action-btn .arrow {
    margin-left: auto;
    color: var(--muted);
    font-size: 18px;
    transition: transform 0.2s;
  }

  .action-btn:hover .arrow {
    transform: translateX(3px);
    color: var(--text);
  }

  /* ── RESPONSIVE ── */
  @media (max-width: 768px) {
    .stat-row { grid-template-columns: 1fr 1fr; }
    .bottom-grid { grid-template-columns: 1fr; }
  }

  @media (max-width: 480px) {
    .stat-row { grid-template-columns: 1fr; }
    .navbar { padding: 0 16px; }
    .shop-badge { display: none; }
  }

</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="navbar-brand">
    <div class="dot"></div>
    Seller Panel
  </div>
  <div class="nav-right">
    <span class="shop-badge">
      🏪 <?php echo htmlspecialchars($shop['shop_name']); ?>
    </span>
    <a href="shop_logout.php" class="logout-btn">
      <span class="material-icons-round" style="font-size:16px;">logout</span>
      Logout
    </a>
  </div>
</nav>

<!-- PAGE -->
<div class="page">

  <div class="page-header">
    <h1>Good day, <?php echo htmlspecialchars($shop['owner_name']); ?> 👋</h1>
    <p>Here's what's happening with your shop today.</p>
  </div>

  <!-- STAT CARDS -->
  <div class="stat-row">

    <div class="stat-card">
      <div class="stat-icon green">
        <span class="material-icons-round">shopping_bag</span>
      </div>
      <div class="stat-info">
        <label>Total Orders</label>
        <strong><?php echo $total_orders['total'] ?? 0; ?></strong>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon amber">
        <span class="material-icons-round">currency_rupee</span>
      </div>
      <div class="stat-info">
        <label>Total Sales</label>
        <strong>₹<?php echo number_format($total_sales['total'] ?? 0, 2); ?></strong>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon blue">
        <span class="material-icons-round">storefront</span>
      </div>
      <div class="stat-info">
        <label>Shop Status</label>
        <strong style="color:var(--accent);font-size:18px;">Active</strong>
      </div>
    </div>

  </div>

  <!-- BOTTOM GRID -->
  <div class="bottom-grid">

    <!-- SHOP PROFILE -->
    <div class="profile-card">
      <img src="uploads/<?php echo htmlspecialchars($shop['image']); ?>"
           alt="Shop Image"
           onerror="this.src='https://placehold.co/320x180/1a1d27/7c829a?text=No+Image'">
      <div class="profile-body">
        <h2><?php echo htmlspecialchars($shop['shop_name']); ?></h2>

        <div class="info-row">
          <span class="material-icons-round">person</span>
          <div><span>Owner</span><br><strong><?php echo htmlspecialchars($shop['owner_name']); ?></strong></div>
        </div>

        <div class="info-row">
          <span class="material-icons-round">phone</span>
          <div><span>Phone</span><br><strong><?php echo htmlspecialchars($shop['phone']); ?></strong></div>
        </div>

        <div class="info-row">
          <span class="material-icons-round">location_on</span>
          <div><span>Location</span><br>
            <strong><?php echo htmlspecialchars($shop['area']); ?>, <?php echo htmlspecialchars($shop['city']); ?></strong>
          </div>
        </div>

        <div class="category-tag">
          <?php echo htmlspecialchars($shop['category']); ?>
        </div>
      </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="actions-card">
      <h3>⚡ Quick Actions</h3>
      <div class="action-list">

        <a href="add_product.php" class="action-btn green">
          <div class="action-icon">
            <span class="material-icons-round">add_box</span>
          </div>
          <div class="action-text">
            <strong>Add Product</strong>
            <span>List a new item in your shop</span>
          </div>
          <span class="material-icons-round arrow">arrow_forward</span>
        </a>

        <a href="my_products.php" class="action-btn blue">
          <div class="action-icon">
            <span class="material-icons-round">inventory_2</span>
          </div>
          <div class="action-text">
            <strong>My Products</strong>
            <span>View and manage your inventory</span>
          </div>
          <span class="material-icons-round arrow">arrow_forward</span>
        </a>

        <a href="orders.php" class="action-btn amber">
          <div class="action-icon">
            <span class="material-icons-round">receipt_long</span>
          </div>
          <div class="action-text">
            <strong>Shop Orders</strong>
            <span>Track and manage customer orders</span>
          </div>
          <span class="material-icons-round arrow">arrow_forward</span>
        </a>

      </div>
    </div>

  </div><!-- end bottom-grid -->

</div><!-- end page -->

</body>
</html>