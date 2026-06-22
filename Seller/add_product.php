<?php
include "../config/db.php";

if (!isset($_SESSION['shop_id'])) {
    header("Location: shop_login.php");
    exit();
}

// ✅ This is the KEY fix — get shop_id from session
$shop_id = intval($_SESSION['shop_id']);

$error      = "";
$show_popup = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name     = trim($_POST['name']);
    $price    = floatval($_POST['price']);
    $stock    = intval($_POST['stock']);
    $category = ucfirst(strtolower(trim($_POST['category'])));

    // Image validation
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = "❌ Image upload failed.";
    } else {

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['image']['type'], $allowed)) {
            $error = "❌ Only JPG, PNG or WEBP allowed.";
        } else {

            $image = basename($_FILES['image']['name']);

            if (!move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image)) {
                $error = "❌ Could not save image. Check uploads/ folder permissions.";
            } else {

                // ✅ shop_id is NOW included in the INSERT
                // So this product belongs to the logged-in shop
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO products (shop_id, name, price, stock, category, image)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                //                      ↑
                //              shop_id tied here
                mysqli_stmt_bind_param($stmt, "isdiss",
                    $shop_id, $name, $price, $stock, $category, $image
                );

                if (mysqli_stmt_execute($stmt)) {
                    $show_popup = true;
                } else {
                    $error = "❌ Failed: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>

  :root {
    --bg: #0f1117;
    --surface: #1a1d27;
    --surface2: #22263a;
    --border: rgba(255,255,255,0.07);
    --accent: #22c55e;
    --text: #f0f2f8;
    --muted: #7c829a;
    --danger: #ef4444;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }

  .navbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .navbar-brand {
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .navbar-brand .dot {
    width: 9px; height: 9px;
    background: var(--accent);
    border-radius: 50%;
    box-shadow: 0 0 8px var(--accent);
  }

  .back-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: var(--muted);
    font-size: 14px;
    transition: color 0.2s;
  }

  .back-btn:hover { color: var(--text); }

  .page {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 36px 32px;
    width: 100%;
    max-width: 460px;
    animation: fadeUp 0.4s ease both;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .card-header { margin-bottom: 28px; }

  .card-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.5px;
  }

  .card-header p { color: var(--muted); font-size: 13px; margin-top: 4px; }

  /* Shop badge — shows which shop this product goes to */
  .shop-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(34,197,94,0.1);
    border: 1px solid rgba(34,197,94,0.25);
    color: var(--accent);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 8px;
  }

  .error-box {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.25);
    color: var(--danger);
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .section-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--muted);
    margin: 20px 0 10px;
    font-weight: 600;
  }

  .input-group { margin-bottom: 14px; }

  .input-group label {
    display: block;
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 6px;
  }

  .input-wrap {
    position: relative;
    display: flex;
    align-items: center;
  }

  .input-wrap .material-icons-round {
    position: absolute;
    left: 12px;
    font-size: 18px;
    color: var(--muted);
    pointer-events: none;
  }

  .input-wrap input {
    width: 100%;
    padding: 11px 14px 11px 40px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
  }

  .input-wrap input:focus { border-color: var(--accent); }
  .input-wrap input::placeholder { color: var(--muted); }

  .file-label {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--surface2);
    border: 1px dashed rgba(255,255,255,0.15);
    border-radius: 10px;
    padding: 14px 16px;
    cursor: pointer;
    transition: border-color 0.2s;
  }

  .file-label:hover { border-color: var(--accent); }

  .file-label .material-icons-round { font-size: 22px; color: var(--accent); }
  .file-label span { font-size: 13px; color: var(--muted); }
  .file-label strong { display: block; font-size: 13px; color: var(--text); margin-bottom: 2px; }

  input[type="file"] { display: none; }

  .two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }

  .submit-btn {
    width: 100%;
    padding: 13px;
    background: var(--accent);
    color: white;
    border: none;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s, transform 0.15s;
  }

  .submit-btn:hover { background: #16a34a; transform: translateY(-1px); }

  /* POPUP */
  .popup-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 999;
    align-items: center;
    justify-content: center;
  }

  .popup-overlay.active { display: flex; }

  .popup-box {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 44px 36px;
    text-align: center;
    width: 340px;
    animation: popIn 0.3s ease;
  }

  @keyframes popIn {
    from { transform: scale(0.85); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
  }

  .popup-icon { font-size: 52px; margin-bottom: 14px; }

  .popup-box h3 {
    font-family: 'Syne', sans-serif;
    font-size: 20px;
    color: var(--accent);
    margin-bottom: 8px;
  }

  .popup-box p { font-size: 13px; color: var(--muted); margin-bottom: 6px; }
  .popup-timer { font-size: 11px; color: var(--muted); margin-bottom: 22px; }

  .popup-actions { display: flex; gap: 10px; justify-content: center; }

  .popup-btn {
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
  }

  .popup-btn:hover { opacity: 0.85; }
  .popup-btn.green { background: var(--accent); color: white; }
  .popup-btn.ghost {
    background: var(--surface2);
    color: var(--text);
    border: 1px solid var(--border);
  }

</style>
</head>
<body>

<!-- POPUP -->
<?php if ($show_popup): ?>
<div class="popup-overlay active">
  <div class="popup-box">
    <div class="popup-icon">✅</div>
    <h3>Product Added!</h3>
    <p>Your product has been listed under your shop.</p>
    <p class="popup-timer">Redirecting in <span id="countdown">4</span>s...</p>
    <div class="popup-actions">
      <a href="add_product.php" class="popup-btn ghost">+ Add More</a>
      <a href="my_products.php" class="popup-btn green">My Products →</a>
    </div>
  </div>
</div>
<script>
  let s = 4;
  const el = document.getElementById('countdown');
  const t = setInterval(() => {
    s--; el.textContent = s;
    if (s <= 0) { clearInterval(t); window.location.href = "my_products.php"; }
  }, 1000);
</script>
<?php endif; ?>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="navbar-brand">
    <div class="dot"></div>
    Seller Panel
  </div>
  <a href="index.php" class="back-btn">
    <span class="material-icons-round" style="font-size:18px;">arrow_back</span>
    Back to Dashboard
  </a>
</nav>

<!-- PAGE -->
<div class="page">
  <div class="card">

    <div class="card-header">
      <h1>➕ Add New Product</h1>
      <p>This product will be listed under your shop for customers to see.</p>
      <!-- ✅ Shows seller which shop the product goes to -->
      <div class="shop-badge">
        <span class="material-icons-round" style="font-size:14px;">storefront</span>
        Shop ID: #<?php echo $shop_id; ?>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="error-box">
      <span class="material-icons-round" style="font-size:18px;">error_outline</span>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

      <p class="section-label">Product Info</p>

      <div class="input-group">
        <label>Product Name</label>
        <div class="input-wrap">
          <span class="material-icons-round">inventory_2</span>
          <input type="text" name="name" placeholder="e.g. Basmati Rice 1kg" required>
        </div>
      </div>

      <div class="input-group">
        <label>Category</label>
        <div class="input-wrap">
          <span class="material-icons-round">category</span>
          <input type="text" name="category" placeholder="e.g. Grocery, Medicine" required>
        </div>
      </div>

      <p class="section-label">Pricing & Stock</p>

      <div class="two-col">
        <div class="input-group">
          <label>Price (₹)</label>
          <div class="input-wrap">
            <span class="material-icons-round">currency_rupee</span>
            <input type="number" name="price" placeholder="0.00" step="0.01" min="0" required>
          </div>
        </div>

        <div class="input-group">
          <label>Stock Quantity</label>
          <div class="input-wrap">
            <span class="material-icons-round">numbers</span>
            <input type="number" name="stock" placeholder="0" min="0" required>
          </div>
        </div>
      </div>

      <p class="section-label">Product Image</p>

      <label class="file-label" for="imageInput">
        <span class="material-icons-round">cloud_upload</span>
        <div>
          <strong id="fileName">Click to upload image</strong>
          <span>JPG, PNG or WEBP accepted</span>
        </div>
      </label>
      <input type="file" id="imageInput" name="image"
             accept="image/jpeg,image/png,image/webp" required
             onchange="document.getElementById('fileName').textContent = this.files[0]?.name || 'Click to upload image'">

      <button type="submit" class="submit-btn">
        <span class="material-icons-round" style="font-size:18px;">add_circle</span>
        Add Product to My Shop
      </button>

    </form>
  </div>
</div>

</body>
</html>
