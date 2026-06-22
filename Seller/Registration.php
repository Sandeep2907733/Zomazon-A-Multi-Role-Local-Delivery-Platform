<?php
include "../config/db.php";

$success     = "";
$error       = "";
$show_popup  = false;

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $shop     = trim($_POST['shop_name']);
    $owner    = trim($_POST['owner_name']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    $address  = trim($_POST['address']);
    $city     = trim($_POST['city']);
    $area     = trim($_POST['area']);
    $category = ucfirst(strtolower(trim($_POST['category'])));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // --- Check if phone already exists ---
    $check = mysqli_prepare($conn, "SELECT id FROM local_shops WHERE phone = ?");
    mysqli_stmt_bind_param($check, "s", $phone);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        $error = "❌ This phone number is already registered with another shop.";
        mysqli_stmt_close($check);

    } else {
        mysqli_stmt_close($check);

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = "❌ Image upload failed. Make sure the uploads/ folder exists.";
        } else {

            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($_FILES['image']['type'], $allowed)) {
                $error = "❌ Only JPG, PNG, or WEBP images allowed.";
            } else {

                $image = basename($_FILES['image']['name']);

                if (!move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image)) {
                    $error = "❌ Could not save image. Check folder permissions.";
                } else {

                    $stmt = mysqli_prepare($conn,
                        "INSERT INTO local_shops
                        (shop_name, owner_name, phone, seller_email, address, area, city, category, image, password)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($stmt, "ssssssssss",
                        $shop, $owner, $phone, $email,
                        $address, $area, $city, $category, $image, $password
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        $show_popup = true; // ✅ Show popup on success
                    } else {
                        if (mysqli_errno($conn) == 1062) {
                            $error = "❌ This phone number is already registered.";
                        } else {
                            $error = "❌ Registration failed: " . mysqli_error($conn);
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
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
<title>Register Shop</title>
<style>
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: 'Poppins', sans-serif;
    background: #f5f7fb;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .box {
    width: 440px;
    background: white;
    padding: 35px 30px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  }

  .box h2 {
    margin-bottom: 20px;
    font-size: 22px;
    color: #1a1a1a;
  }

  .input-group {
    margin-bottom: 12px;
  }

  .input-group label {
    display: block;
    font-size: 13px;
    color: #555;
    margin-bottom: 4px;
  }

  .input-group input {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    color: #333;
    transition: border 0.2s;
    outline: none;
  }

  .input-group input:focus {
    border-color: #22c55e;
  }

  .input-group input[type="file"] {
    padding: 8px;
    cursor: pointer;
  }

  button[type="submit"] {
    width: 100%;
    padding: 12px;
    background: #22c55e;
    color: white;
    border: none;
    border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 6px;
    transition: background 0.2s;
  }

  button[type="submit"]:hover {
    background: #16a34a;
  }

  .error {
    color: #dc2626;
    background: #fff1f1;
    border: 1px solid #fecaca;
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
  }

  .divider {
    font-size: 11px;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 16px 0 10px;
  }

  /* ===== POPUP STYLES ===== */
  .popup-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 999;
    align-items: center;
    justify-content: center;
  }

  .popup-overlay.active {
    display: flex;
  }

  .popup-box {
    background: white;
    border-radius: 16px;
    padding: 40px 35px;
    text-align: center;
    width: 340px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    animation: popIn 0.3s ease;
  }

  @keyframes popIn {
    from { transform: scale(0.85); opacity: 0; }
    to   { transform: scale(1);    opacity: 1; }
  }

  .popup-icon {
    font-size: 56px;
    margin-bottom: 12px;
  }

  .popup-box h3 {
    font-size: 20px;
    color: #15803d;
    margin-bottom: 8px;
  }

  .popup-box p {
    font-size: 14px;
    color: #555;
    margin-bottom: 24px;
  }

  .popup-box .timer {
    font-size: 12px;
    color: #aaa;
    margin-top: -16px;
    margin-bottom: 20px;
  }

  .popup-btn {
    display: inline-block;
    padding: 11px 28px;
    background: #22c55e;
    color: white;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
  }

  .popup-btn:hover {
    background: #16a34a;
  }
</style>
</head>
<body>

<!-- ===== SUCCESS POPUP ===== -->
<?php if ($show_popup): ?>
<div class="popup-overlay active" id="successPopup">
  <div class="popup-box">
    <div class="popup-icon">🎉</div>
    <h3>Shop Registered!</h3>
    <p>Your shop has been successfully registered.<br>You can now log in to your account.</p>
    <p class="timer">Redirecting in <span id="countdown">4</span> seconds...</p>
    <a href="index.php" class="popup-btn">Go to Login →</a>
  </div>
</div>

<script>
  // Auto redirect countdown
  let seconds = 4;
  const countdownEl = document.getElementById('countdown');

  const timer = setInterval(() => {
    seconds--;
    countdownEl.textContent = seconds;
    if (seconds <= 0) {
      clearInterval(timer);
      window.location.href ="index.php"; // 👈 change path if needed
    }
  }, 1000);
</script>
<?php endif; ?>

<!-- ===== FORM ===== -->
<div class="box">
  <h2>🏪 Register Your Shop</h2>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <p class="divider">Shop Info</p>

    <div class="input-group">
      <label>Shop Name</label>
      <input type="text" name="shop_name" placeholder="e.g. Krishna Medical Store" required>
    </div>

    <div class="input-group">
      <label>Owner Name</label>
      <input type="text" name="owner_name" placeholder="e.g. Sandeep Das" required>
    </div>

    <div class="input-group">
      <label>Category</label>
      <input type="text" name="category" placeholder="e.g. Grocery, Medical, Electronics" required>
    </div>

    <p class="divider">Contact Details</p>

    <div class="input-group">
      <label>Phone Number</label>
      <input type="tel" name="phone" placeholder="e.g. 9876543210" maxlength="10" required>
    </div>

    <div class="input-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="e.g. shop@gmail.com" required>
    </div>

    <p class="divider">Location</p>

    <div class="input-group">
      <label>Address</label>
      <input type="text" name="address" placeholder="e.g. Near Bus Stand" required>
    </div>

    <div class="input-group">
      <label>Area</label>
      <input type="text" name="area" placeholder="e.g. Lahoal" required>
    </div>

    <div class="input-group">
      <label>City</label>
      <input type="text" name="city" placeholder="e.g. Dibrugarh" required>
    </div>

    <p class="divider">Account Setup</p>

    <div class="input-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Create a strong password" required>
    </div>

    <div class="input-group">
      <label>Shop Image</label>
      <input type="file" name="image" accept="image/jpeg, image/png, image/webp" required>
    </div>

    <button type="submit">✅ Register Shop</button>

  </form>
</div>

</body>
</html>