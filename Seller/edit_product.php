<?php
include "../config/db.php";

if (!isset($_SESSION['shop_id'])) {
    header("Location: shop_login.php");
    exit();
}

$shop_id = intval($_SESSION['shop_id']);
$id      = intval($_GET['id']);

// Security: make sure this product belongs to this shop
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND shop_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $shop_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// If product not found or doesn't belong to this shop
if (!$product) {
    header("Location: my_products.php");
    exit();
}

$error      = "";
$show_popup = false;

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $name     = trim($_POST['name']);
    $price    = floatval($_POST['price']);
    $stock    = intval($_POST['stock']);
    $category = ucfirst(strtolower(trim($_POST['category'])));
    $image    = $product['image']; // keep old image by default

    // If new image uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['image']['type'], $allowed)) {
            $error = "❌ Only JPG, PNG or WEBP allowed.";
        } else {
            $image = basename($_FILES['image']['name']);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image)) {
                $error = "❌ Could not save image. Check uploads/ permissions.";
            }
        }
    }

    if (!$error) {
        $upd = mysqli_prepare($conn,
            "UPDATE products SET name=?, price=?, stock=?, category=?, image=? WHERE id=? AND shop_id=?"
        );
        mysqli_stmt_bind_param($upd, "sdissii",
            $name, $price, $stock, $category, $image, $id, $shop_id
        );
        if (mysqli_stmt_execute($upd)) {
            $show_popup = true;
            // Refresh product data
            $stmt2 = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
            mysqli_stmt_bind_param($stmt2, "i", $id);
            mysqli_stmt_execute($stmt2);
            $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
        } else {
            $error = "❌ Update failed: " . mysqli_error($conn);
        }
        mysqli_stmt_close($upd);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Product</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['DM Sans','sans-serif'], display: ['Syne','sans-serif'] },
      }
    }
  }
</script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
  body { background:#0f1117; color:#f0f2f8; font-family:'DM Sans',sans-serif; }
  input,select { outline:none; }
  input::placeholder { color:#7c829a; }
  .dot { animation: pulse 2s infinite; }
  @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }
</style>
</head>
<body class="min-h-screen flex flex-col">

<!-- POPUP -->
<?php if ($show_popup): ?>
<div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center">
  <div class="bg-[#1a1d27] border border-white/[0.07] rounded-2xl p-10 text-center w-80" style="animation:popIn .3s ease">
    <div class="text-5xl mb-4">✅</div>
    <h3 class="font-display font-extrabold text-xl text-green-500 mb-2">Product Updated!</h3>
    <p class="text-[#7c829a] text-sm mb-2">Changes saved successfully.</p>
    <p class="text-[#7c829a] text-xs mb-6">Redirecting in <span id="countdown">3</span>s...</p>
    <div class="flex gap-2 justify-center">
      <a href="edit_product.php?id=<?= $id ?>" class="text-sm font-semibold px-4 py-2 rounded-lg bg-[#22263a] border border-white/[0.07] text-white hover:opacity-80 transition">
        Stay Here
      </a>
      <a href="my_products.php" class="text-sm font-semibold px-4 py-2 rounded-lg bg-green-500 text-white hover:bg-green-600 transition">
        My Products →
      </a>
    </div>
  </div>
</div>
<style>@keyframes popIn{from{transform:scale(.85);opacity:0}to{transform:scale(1);opacity:1}}</style>
<script>
  let s = 3;
  const el = document.getElementById('countdown');
  const t = setInterval(() => { s--; el.textContent = s; if(s<=0){clearInterval(t);window.location.href="my_products.php";} }, 1000);
</script>
<?php endif; ?>

<!-- NAVBAR -->
<nav class="sticky top-0 z-40 flex items-center justify-between px-8 h-16 bg-[#1a1d27] border-b border-white/[0.07]">
  <div class="flex items-center gap-3 font-display font-extrabold text-lg">
    <span class="dot w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_8px_#22c55e] inline-block"></span>
    Seller Panel
  </div>
  <a href="my_products.php" class="flex items-center gap-1.5 text-[#7c829a] text-sm px-3 py-2 rounded-lg bg-[#22263a] border border-white/[0.07] hover:text-white transition">
    <span class="material-icons-round text-base">arrow_back</span> My Products
  </a>
</nav>

<!-- PAGE -->
<div class="flex-1 flex items-start justify-center px-6 py-10">
  <div class="w-full max-w-xl">

    <!-- HEADER -->
    <div class="mb-7">
      <h1 class="font-display font-extrabold text-2xl tracking-tight">✏️ Edit Product</h1>
      <p class="text-[#7c829a] text-sm mt-1">Update the details for this product.</p>
    </div>

    <!-- CURRENT IMAGE PREVIEW -->
    <div class="bg-[#1a1d27] border border-white/[0.07] rounded-2xl overflow-hidden mb-5">
      <img src="uploads/<?= htmlspecialchars($product['image']) ?>"
           id="previewImg"
           alt="Product Image"
           class="w-full h-48 object-cover"
           onerror="this.src='https://placehold.co/600x200/1a1d27/7c829a?text=No+Image'">
      <div class="px-4 py-3 flex items-center gap-2 text-[#7c829a] text-xs">
        <span class="material-icons-round text-sm">image</span>
        Current image — upload a new one below to replace it
      </div>
    </div>

    <!-- ERROR -->
    <?php if ($error): ?>
    <div class="flex items-center gap-2 bg-red-500/10 border border-red-500/25 text-red-400 px-4 py-3 rounded-xl text-sm mb-5">
      <span class="material-icons-round text-base">error_outline</span>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="bg-[#1a1d27] border border-white/[0.07] rounded-2xl p-7">
      <form method="POST" enctype="multipart/form-data" class="space-y-5">

        <!-- Product Name -->
        <div>
          <label class="block text-xs text-[#7c829a] uppercase tracking-wider mb-2">Product Name</label>
          <div class="relative">
            <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-[#7c829a] text-lg">inventory_2</span>
            <input type="text" name="name"
                   value="<?= htmlspecialchars($product['name']) ?>"
                   class="w-full bg-[#22263a] border border-white/[0.07] rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:border-green-500 transition"
                   required>
          </div>
        </div>

        <!-- Category -->
        <div>
          <label class="block text-xs text-[#7c829a] uppercase tracking-wider mb-2">Category</label>
          <div class="relative">
            <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-[#7c829a] text-lg">category</span>
            <input type="text" name="category"
                   value="<?= htmlspecialchars($product['category']) ?>"
                   class="w-full bg-[#22263a] border border-white/[0.07] rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:border-green-500 transition"
                   required>
          </div>
        </div>

        <!-- Price + Stock -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-[#7c829a] uppercase tracking-wider mb-2">Price (₹)</label>
            <div class="relative">
              <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-[#7c829a] text-lg">currency_rupee</span>
              <input type="number" name="price" step="0.01" min="0"
                     value="<?= $product['price'] ?>"
                     class="w-full bg-[#22263a] border border-white/[0.07] rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:border-green-500 transition"
                     required>
            </div>
          </div>
          <div>
            <label class="block text-xs text-[#7c829a] uppercase tracking-wider mb-2">Stock Qty</label>
            <div class="relative">
              <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-[#7c829a] text-lg">numbers</span>
              <input type="number" name="stock" min="0"
                     value="<?= $product['stock'] ?>"
                     class="w-full bg-[#22263a] border border-white/[0.07] rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:border-green-500 transition"
                     required>
            </div>
          </div>
        </div>

        <!-- Image Upload -->
        <div>
          <label class="block text-xs text-[#7c829a] uppercase tracking-wider mb-2">Replace Image <span class="normal-case">(optional)</span></label>
          <label for="imageInput" class="flex items-center gap-3 bg-[#22263a] border border-dashed border-white/[0.15] rounded-xl px-4 py-3.5 cursor-pointer hover:border-green-500 transition">
            <span class="material-icons-round text-green-500">cloud_upload</span>
            <div>
              <p id="fileName" class="text-sm text-white font-medium">Click to upload new image</p>
              <p class="text-xs text-[#7c829a]">JPG, PNG or WEBP — leave blank to keep current</p>
            </div>
          </label>
          <input type="file" id="imageInput" name="image" accept="image/jpeg,image/png,image/webp" class="hidden"
                 onchange="
                   document.getElementById('fileName').textContent = this.files[0]?.name || 'Click to upload new image';
                   if(this.files[0]) document.getElementById('previewImg').src = URL.createObjectURL(this.files[0]);
                 ">
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-xl transition text-sm mt-2">
          <span class="material-icons-round text-base">save</span>
          Save Changes
        </button>

      </form>
    </div>

  </div>
</div>

</body>
</html>