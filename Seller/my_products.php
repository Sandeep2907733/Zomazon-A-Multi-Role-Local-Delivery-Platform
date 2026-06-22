<?php
include "../config/db.php";

if (!isset($_SESSION['shop_id'])) { header("Location: index.php"); exit(); }

$shop_id = intval($_SESSION['shop_id']);

// ✅ Handle delete on same page
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);

    $img_stmt = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ? AND shop_id = ?");
    mysqli_stmt_bind_param($img_stmt, "ii", $del_id, $shop_id);
    mysqli_stmt_execute($img_stmt);
    $img_row = mysqli_fetch_assoc(mysqli_stmt_get_result($img_stmt));

    if ($img_row) {
        $img_path = "uploads/" . $img_row['image'];
        if (file_exists($img_path)) unlink($img_path);

        $del = mysqli_prepare($conn, "DELETE FROM products WHERE id = ? AND shop_id = ?");
        mysqli_stmt_bind_param($del, "ii", $del_id, $shop_id);
        mysqli_stmt_execute($del);
    }

    header("Location: my_products.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE shop_id = ?");
mysqli_stmt_bind_param($stmt, "i", $shop_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total  = mysqli_num_rows($result);

$s2 = mysqli_prepare($conn, "SELECT SUM(stock) as ts, AVG(price) as ap FROM products WHERE shop_id = ?");
mysqli_stmt_bind_param($s2, "i", $shop_id);
mysqli_stmt_execute($s2);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($s2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Products</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['DM Sans', 'sans-serif'], display: ['Syne', 'sans-serif'] },
        colors: { surface: '#1a1d27', surface2: '#22263a' }
      }
    }
  }
</script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
  body { background:#0f1117; color:#f0f2f8; font-family:'DM Sans',sans-serif; }
  .dot { animation:pulse 2s infinite; }
  @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }
  .card-hover:hover { transform:translateY(-3px); border-color:rgba(255,255,255,0.14); }
  .card-hover:hover img { transform:scale(1.05); }
  input::placeholder { color:#7c829a; }
  input { outline:none; }
</style>
</head>
<body class="min-h-screen">

<!-- NAVBAR -->
<nav class="sticky top-0 z-50 flex items-center justify-between px-8 h-16 bg-surface border-b border-white/[0.07]">
  <div class="flex items-center gap-3 font-display font-extrabold text-lg">
    <span class="dot w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_8px_#22c55e] inline-block"></span>
    Seller Panel
  </div>
  <div class="flex items-center gap-3">
    <a href="dashboard.php" class="flex items-center gap-1.5 text-[#7c829a] text-sm px-3 py-2 rounded-lg bg-surface2 border border-white/[0.07] hover:text-white transition">
      <span class="material-icons-round text-base">arrow_back</span> Dashboard
    </a>
    <a href="add_product.php" class="flex items-center gap-1.5 text-white text-sm font-semibold px-4 py-2 rounded-lg bg-green-500 hover:bg-green-600 transition">
      <span class="material-icons-round text-base">add</span> Add Product
    </a>
  </div>
</nav>

<!-- PAGE -->
<div class="max-w-6xl mx-auto px-6 py-9">

  <div class="mb-7">
    <h1 class="font-display font-extrabold text-2xl tracking-tight">📦 My Products</h1>
    <p class="text-[#7c829a] text-sm mt-1">Manage all products listed in your shop.</p>
  </div>

  <!-- STAT CARDS -->
  <div class="grid grid-cols-3 gap-4 mb-7">
    <div class="flex items-center gap-4 bg-surface border border-white/[0.07] rounded-2xl px-5 py-5">
      <div class="w-11 h-11 rounded-xl bg-green-500/10 text-green-500 flex items-center justify-center flex-shrink-0">
        <span class="material-icons-round">inventory_2</span>
      </div>
      <div>
        <p class="text-[10px] text-[#7c829a] uppercase tracking-wider mb-1">Total Products</p>
        <p class="font-display font-extrabold text-2xl"><?= $total ?></p>
      </div>
    </div>
    <div class="flex items-center gap-4 bg-surface border border-white/[0.07] rounded-2xl px-5 py-5">
      <div class="w-11 h-11 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center flex-shrink-0">
        <span class="material-icons-round">numbers</span>
      </div>
      <div>
        <p class="text-[10px] text-[#7c829a] uppercase tracking-wider mb-1">Total Stock</p>
        <p class="font-display font-extrabold text-2xl"><?= $stats['ts'] ?? 0 ?></p>
      </div>
    </div>
    <div class="flex items-center gap-4 bg-surface border border-white/[0.07] rounded-2xl px-5 py-5">
      <div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center flex-shrink-0">
        <span class="material-icons-round">currency_rupee</span>
      </div>
      <div>
        <p class="text-[10px] text-[#7c829a] uppercase tracking-wider mb-1">Avg. Price</p>
        <p class="font-display font-extrabold text-2xl">₹<?= number_format($stats['ap'] ?? 0, 0) ?></p>
      </div>
    </div>
  </div>

  <!-- SEARCH -->
  <div class="flex items-center gap-3 mb-6">
    <div class="relative flex-1">
      <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-[#7c829a] text-lg">search</span>
      <input id="searchInput" type="text" placeholder="Search by name or category..."
        class="w-full bg-surface border border-white/[0.07] rounded-xl pl-10 pr-4 py-2.5 text-sm text-white focus:border-green-500 transition">
    </div>
    <span id="countBadge" class="text-[#7c829a] text-sm bg-surface border border-white/[0.07] px-4 py-2.5 rounded-xl whitespace-nowrap">
      <?= $total ?> product<?= $total != 1 ? 's' : '' ?>
    </span>
  </div>

  <!-- GRID -->
  <div id="productGrid" class="grid grid-cols-[repeat(auto-fill,minmax(210px,1fr))] gap-5">

    <?php if ($total === 0): ?>
    <div class="col-span-full text-center py-24">
      <span class="material-icons-round text-6xl text-[#7c829a] opacity-30">inventory_2</span>
      <h3 class="font-display font-extrabold text-xl mt-4 mb-2">No products yet</h3>
      <p class="text-[#7c829a] text-sm mb-6">Add your first product to get started.</p>
      <a href="add_product.php" class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-semibold px-5 py-2.5 rounded-xl transition">
        <span class="material-icons-round text-base">add_circle</span> Add Product
      </a>
    </div>

    <?php else: while ($row = mysqli_fetch_assoc($result)):
      $stock = intval($row['stock']);
      [$badge, $color] = $stock === 0
        ? ['Out of Stock', 'bg-red-500/80']
        : ($stock <= 5 ? ['Low Stock', 'bg-amber-500/80'] : ['In Stock', 'bg-green-500/80']);
    ?>

    <div class="card-hover bg-surface border border-white/[0.07] rounded-2xl overflow-hidden transition-all duration-200"
         data-name="<?= strtolower(htmlspecialchars($row['name'])) ?>"
         data-category="<?= strtolower(htmlspecialchars($row['category'])) ?>">

      <div class="relative overflow-hidden">
        <img src="uploads/<?= htmlspecialchars($row['image']) ?>"
             class="w-full h-40 object-cover transition-transform duration-300"
             onerror="this.src='https://placehold.co/220x160/1a1d27/7c829a?text=No+Image'">
        <span class="absolute top-2 right-2 <?= $color ?> text-white text-[10px] font-bold px-2.5 py-1 rounded-full">
          <?= $badge ?>
        </span>
      </div>

      <div class="p-4">
        <p class="text-[10px] text-green-500 font-bold uppercase tracking-widest mb-1"><?= htmlspecialchars($row['category']) ?></p>
        <p class="font-display font-bold text-[15px] truncate mb-1"><?= htmlspecialchars($row['name']) ?></p>
        <p class="text-green-500 font-display font-extrabold text-lg mb-1">₹<?= number_format($row['price'], 2) ?></p>
        <p class="text-[#7c829a] text-xs mb-4"><?= $stock ?> units in stock</p>

        <div class="grid grid-cols-2 gap-2">
          <a href="edit_product.php?id=<?= $row['id'] ?>"
             class="flex items-center justify-center gap-1 text-blue-400 bg-blue-500/10 border border-blue-500/20 text-xs font-semibold py-2 rounded-lg hover:opacity-80 transition">
            <span class="material-icons-round text-sm">edit</span> Edit
          </a>

          <!-- ✅ Delete form — no page redirect -->
          <form method="POST" onsubmit="return confirm('Delete \'<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>\'?')">
            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
            <button type="submit"
                    class="w-full flex items-center justify-center gap-1 text-red-400 bg-red-500/10 border border-red-500/20 text-xs font-semibold py-2 rounded-lg hover:opacity-80 transition">
              <span class="material-icons-round text-sm">delete</span> Delete
            </button>
          </form>

        </div>
      </div>
    </div>

    <?php endwhile; endif; ?>
  </div>

</div>

<script>
  const input = document.getElementById('searchInput');
  const cards = document.querySelectorAll('#productGrid [data-name]');
  const badge = document.getElementById('countBadge');

  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    let count = 0;
    cards.forEach(c => {
      const show = c.dataset.name.includes(q) || c.dataset.category.includes(q);
      c.style.display = show ? '' : 'none';
      if (show) count++;
    });
    badge.textContent = `${count} product${count !== 1 ? 's' : ''}`;
  });
</script>

</body>
</html>