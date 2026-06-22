<?php
include 'config/db.php';

$search_query = '';
if (isset($_GET['query'])) {
    $search_query = trim($_GET['query']);
}

$results = [];
if ($search_query !== '') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE name LIKE ? OR category LIKE ? LIMIT 40");
    $like = '%' . $search_query . '%';
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search — Zomazon</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['Plus Jakarta Sans','sans-serif'] },
        colors: { brand: '#22c55e', brand2: '#16a34a' }
      }
    }
  }
</script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
  body { font-family:'Plus Jakarta Sans',sans-serif; background:#f8fafc; }
  .product-card:hover img { transform:scale(1.06); }
</style>
</head>
<body class="min-h-screen">

<!-- NAVBAR -->
<header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-100 shadow-sm">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between gap-4">
    <a href="index.php" class="flex items-center gap-2 flex-shrink-0">
      <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center">
        <span class="material-icons-round text-white text-lg">local_grocery_store</span>
      </div>
      <span class="font-bold text-xl text-slate-800 tracking-tight">Zomazon</span>
    </a>

    <!-- Search bar — pre-filled with current query -->
    <form action="search.php" method="GET" class="relative flex-1 max-w-lg hidden sm:block">
      <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
      <input 
        type="text" 
        name="query"
        value="<?= htmlspecialchars($search_query) ?>"
        placeholder="Search products, shops, categories..."
        class="w-full pl-10 pr-4 py-2.5 bg-slate-100 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-green-300 transition"
        autofocus>
    </form>

    <div class="flex items-center gap-1">
      <a href="index.php" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500 transition" title="Home">
        <span class="material-icons-round text-xl">home</span>
      </a>
      <a href="cart.php" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500 transition relative">
        <span class="material-icons-round text-xl">shopping_cart</span>
        <span class="absolute top-1 right-1 w-2 h-2 bg-brand rounded-full"></span>
      </a>
    </div>
  </div>
</header>

<!-- RESULTS -->
<main class="max-w-7xl mx-auto px-6 py-10">

  <!-- Heading -->
  <div class="mb-6">
    <?php if ($search_query !== ''): ?>
      <h1 class="font-bold text-2xl text-slate-800">
        Results for <span class="text-brand">"<?= htmlspecialchars($search_query) ?>"</span>
      </h1>
      <p class="text-slate-400 text-sm mt-1"><?= count($results) ?> product<?= count($results) !== 1 ? 's' : '' ?> found</p>
    <?php else: ?>
      <h1 class="font-bold text-2xl text-slate-800">Search for something</h1>
    <?php endif; ?>
  </div>

  <!-- Grid -->
  <?php if ($search_query !== '' && count($results) === 0): ?>
    <div class="flex flex-col items-center justify-center py-24 text-slate-400">
      <span class="material-icons-round text-6xl opacity-20 mb-4">search_off</span>
      <p class="text-lg font-semibold">No products found</p>
      <p class="text-sm mt-1">Try a different keyword or browse by category</p>
      <a href="index.php" class="mt-6 bg-brand text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-brand2 transition">
        Back to Home
      </a>
    </div>

  <?php elseif (count($results) > 0): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
      <?php foreach ($results as $p): ?>
      <div class="product-card bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200">
        <div class="overflow-hidden h-40">
          <img src="images/<?= htmlspecialchars($p['image']) ?>"
               alt="<?= htmlspecialchars($p['name']) ?>"
               class="w-full h-full object-cover transition-transform duration-300"
               onerror="this.src='https://placehold.co/200x160/f1f5f9/94a3b8?text=Product'">
        </div>
        <div class="p-3">
          <span class="text-[10px] bg-green-50 text-green-600 font-semibold px-2 py-0.5 rounded-full">
            <?= htmlspecialchars($p['category']) ?>
          </span>
          <h3 class="font-semibold text-slate-800 mt-2 mb-1 text-sm truncate">
            <?= htmlspecialchars($p['name']) ?>
          </h3>
          <div class="flex items-center justify-between mt-2">
            <span class="font-bold text-brand">₹<?= number_format($p['price'], 2) ?></span>
            <a href="cart.php?add=<?= $p['id'] ?>"
               class="w-8 h-8 bg-brand hover:bg-brand2 text-white rounded-xl flex items-center justify-center transition">
              <span class="material-icons-round text-sm">add</span>
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<!-- FOOTER -->
<footer class="bg-slate-900 text-slate-400 text-sm text-center py-6 mt-10">
  <p>© 2025 Zomazon. All rights reserved. Made with <span class="text-brand">♥</span> for local communities</p>
</footer>

</body>
</html>