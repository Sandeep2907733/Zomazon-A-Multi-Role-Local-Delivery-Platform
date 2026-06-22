<?php
include "config/db.php";

// ✅ Popular products — ranked by order count
$popular = mysqli_query($conn,
    "SELECT p.*, COUNT(o.id) as order_count
     FROM products p
     LEFT JOIN orders o ON o.products LIKE CONCAT('%', p.name, '%')
     GROUP BY p.id
     ORDER BY order_count DESC
     LIMIT 8"
);

// Fetch local shops
$shops = mysqli_query($conn, "SELECT * FROM local_shops LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zomazon — Fresh Local Delivery</title>
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
  .slide { display:none; }
  .slide.active { display:block; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
  .fade-in { animation:fadeIn .5s ease both; }
  @keyframes shimmer { 0%,100%{opacity:1} 50%{opacity:.6} }
  .hero-badge { animation:shimmer 2.5s ease infinite; }
  .product-card:hover img { transform:scale(1.06); }
  .category-pill:hover { transform:translateY(-3px); }
  input::placeholder { color:#94a3b8; }
  input { outline:none; }

</style>
</head>
<body class="min-h-screen">

<!-- ══════════════ NAVBAR ══════════════ -->
<header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-100 shadow-sm">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between gap-4">
    <a href="index.php" class="flex items-center gap-2 flex-shrink-0">
      <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center">
        <span class="material-icons-round text-white text-lg">local_grocery_store</span>
      </div>
      <span class="font-bold text-xl text-slate-800 tracking-tight">Zomazon</span>
    </a>

    <form action="search.php" method="GET" class="relative flex-1 max-w-lg hidden sm:block">
  <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
  <input 
    type="text" 
    name="query"
    placeholder="Search products, shops, categories..."
    class="w-full pl-10 pr-4 py-2.5 bg-slate-100 rounded-xl text-sm text-slate-700 focus:bg-white focus:ring-2 focus:ring-green-300 transition">
</form>

    <div class="flex items-center gap-1">
      <?php if (isset($_SESSION['user_id'])): ?>
        <span class="hidden md:flex items-center gap-1.5 text-sm text-slate-600 mr-2">
          <span class="material-icons-round text-brand text-base">account_circle</span>
          Hi, <b><?= htmlspecialchars($_SESSION['user_name']) ?></b>
        </span>
        <a href="registration/logout.php"
           class="flex items-center gap-1.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl transition">
          <span class="material-icons-round text-base">logout</span>
          <span class="hidden sm:inline">Logout</span>
        </a>
      <?php else: ?>
        <a href="registration/Login.php"
           class="flex items-center gap-1.5 text-sm font-semibold text-white bg-brand hover:bg-brand2 px-4 py-2 rounded-xl transition">
          <span class="material-icons-round text-base">person</span> Login
        </a>
      <?php endif; ?>
      <a href="cart.php" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500 transition relative">
        <span class="material-icons-round text-xl">shopping_cart</span>
        <span class="absolute top-1 right-1 w-2 h-2 bg-brand rounded-full"></span>
      </a>
      <a href="settings.php" class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-500 transition" title="Settings">
        <span class="material-icons-round text-xl">settings</span>
      </a>
    </div>
  </div>
</header>

<!-- ══════════════ HERO ══════════════ -->
<section class="bg-gradient-to-br from-green-600 via-green-500 to-emerald-400 text-white overflow-hidden">
  <div class="max-w-7xl mx-auto px-6 py-16 flex flex-col md:flex-row items-center gap-10 relative">
    <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
    <div class="absolute bottom-0 left-1/3 w-64 h-64 bg-black/5 rounded-full translate-y-1/2 pointer-events-none"></div>
    <div class="flex-1 relative z-10 fade-in">
      <span class="hero-badge inline-flex items-center gap-1.5 bg-white/20 backdrop-blur text-xs font-semibold px-3 py-1.5 rounded-full mb-5">
        <span class="w-1.5 h-1.5 bg-white rounded-full inline-block"></span>
        Free delivery on orders above ₹299
      </span>
      <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-4">
        Fresh groceries,<br><span class="text-green-100">delivered fast.</span>
      </h1>
      <p class="text-green-100 text-lg mb-8 max-w-md">Shop from local sellers near you. Support your community while getting fresh products at your door.</p>
      <div class="flex gap-3 flex-wrap">
        <a href="Localshops.php" class="flex items-center gap-2 bg-white text-green-700 font-semibold px-6 py-3 rounded-xl hover:shadow-lg transition">
          <span class="material-icons-round text-base">storefront</span> Browse Shops
        </a>
        <a href="recipe.php" class="flex items-center gap-2 bg-white/20 backdrop-blur text-white font-semibold px-6 py-3 rounded-xl hover:bg-white/30 transition">
          <span class="material-icons-round text-base">auto_awesome</span> AI Recipe Suggestor
      </a>
      </div>
    </div>
    <div class="flex-shrink-0 relative z-10">
      <div class="w-72 h-64 md:w-80 md:h-72 rounded-2xl overflow-hidden shadow-2xl ring-4 ring-white/20">
        <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&q=80" class="w-full h-full object-cover">
      </div>
      <div class="absolute -bottom-4 -left-4 bg-white text-slate-800 shadow-xl rounded-2xl px-4 py-3 flex items-center gap-3">
        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
          <span class="material-icons-round text-brand text-xl">delivery_dining</span>
        </div>
        <div>
          <p class="text-xs text-slate-400">Avg delivery</p>
          <p class="font-bold text-sm">3 Days</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ STATS ══════════════ -->
<div class="bg-white border-b border-slate-100">
  <div class="max-w-7xl mx-auto px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
    <?php foreach ([['🏪','100+','Local Shops'],['📦','1000+','Products'],['⚡','3 Days','Avg Delivery'],['⭐','4.8','Customer Rating']] as [$em,$val,$label]): ?>
    <div class="flex flex-col items-center">
      <span class="text-xl mb-0.5"><?= $em ?></span>
      <span class="font-bold text-slate-800 text-lg leading-tight"><?= $val ?></span>
      <span class="text-xs text-slate-400"><?= $label ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══════════════ CATEGORIES ══════════════ -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <h2 class="font-bold text-xl text-slate-800 mb-5">Shop by Category</h2>
  <div class="grid grid-cols-3 sm:grid-cols-8 gap-1">
    <?php foreach ([
    ['Fruits','🍎'],
    ['Vegetable','🥦'],
    ['Dairy','🥛'],
    ['Snacks','🍿'],
    ['Beverage','🥂'],
    ['Household','🏠'],
    ['Guthka','🍀'],
    ['Local Shops','🏪']
  ] as [$name,$emoji]): ?>

  <?php
// 🔥 Only Local Shops goes to different page
$link = ($name === 'Local Shops') 
    ? 'Localshops.php' 
    : 'category.php?category=' . urlencode($name);
?>


    <a href="<?= $link ?>"class="category-pill flex flex-col items-center gap-1 bg-white border border-slate-100 rounded-2xl py-4 px-3 shadow-sm hover:shadow-md transition-all duration-200 text-center">
      <span class="text-2xl"><?= $emoji ?></span>
      <span class="text-xs font-semibold text-slate-600"><?= $name ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══════════════ CAROUSEL ══════════════ -->
<section class="max-w-7xl mx-auto px-6 pb-10">
  <div class="relative rounded-2xl overflow-hidden">
    <a href="recipe.php"><img src="index images/1774190012313.png" class="slide active w-full h-56 md:h-72 object-cover rounded-2xl"></a>
    <a href="Localshops.php"><img src="index images/1774190140872.png" class="slide w-full h-56 md:h-72 object-cover rounded-2xl"></a>
    <a href="Localshops.php"><img src="index images/1774189865528.png" class="slide w-full h-56 md:h-72 object-cover rounded-2xl"></a>
    <div id="dots" class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2"></div>
  </div>
</section>

<!-- ══════════════ LOCAL SHOPS ══════════════ -->
<section class="max-w-7xl mx-auto px-6 pb-10">
  <div class="flex items-center justify-between mb-5">
    <h2 class="font-bold text-xl text-slate-800">🏪 Local Shops Near You</h2>
    <a href="Localshops.php" class="text-brand text-sm font-semibold hover:underline">View all →</a>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
    <?php if ($shops && mysqli_num_rows($shops) > 0):
      while ($shop = mysqli_fetch_assoc($shops)): ?>
    <a href="shop.php?id=<?= $shop['id'] ?>"
       class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 text-center group">
      <img src="Seller/uploads/<?= htmlspecialchars($shop['image']) ?>"
           class="w-full h-24 object-cover group-hover:scale-105 transition-transform duration-300"
           onerror="this.src='https://placehold.co/200x100/f1f5f9/94a3b8?text=Shop'">
      <div class="p-3">
        <p class="font-semibold text-slate-700 text-xs truncate"><?= htmlspecialchars($shop['shop_name']) ?></p>
        <p class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars($shop['area']) ?></p>
      </div>
    </a>
    <?php endwhile; else: ?>
    <div class="col-span-full text-center py-10 text-slate-400 text-sm">No shops available yet.</div>
    <?php endif; ?>
  </div>
</section>

<!-- ══════════════ POPULAR PRODUCTS ══════════════ -->
<section id="products" class="max-w-7xl mx-auto px-6 pb-16">
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="font-bold text-xl text-slate-800">🔥 Popular Products</h2>
      <p class="text-slate-400 text-xs mt-0.5">Ranked by most orders — no ratings needed</p>
    </div>
    <a href="all_products.php" class="text-brand text-sm font-semibold hover:underline">View all →</a>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5">
    <?php
    $rank = 1;
    if ($popular && mysqli_num_rows($popular) > 0):
      while ($p = mysqli_fetch_assoc($popular)):
        $isHot = $rank <= 3; // top 3 get a hot badge
    ?>
    <div class="product-card bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 relative">

      <!-- Rank badge -->
      <div class="absolute top-2 left-2 z-10 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shadow
        <?= $rank === 1 ? 'bg-amber-400 text-white' : ($rank === 2 ? 'bg-slate-300 text-slate-700' : ($rank === 3 ? 'bg-orange-400 text-white' : 'bg-white text-slate-500 border border-slate-200')) ?>">
        <?= $rank ?>
      </div>

      <!-- Hot badge for top 3 -->
      <?php if ($isHot): ?>
      <div class="absolute top-2 right-2 z-10 bg-red-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full flex items-center gap-0.5">
        🔥 HOT
      </div>
      <?php endif; ?>

      <div class="overflow-hidden h-44">
        <img src="images/<?= htmlspecialchars($p['image']) ?>"
             alt="<?= htmlspecialchars($p['name']) ?>"
             class="w-full h-full object-cover transition-transform duration-300"
             onerror="this.src='Seller/uploads/<?= htmlspecialchars($p['image']) ?>'">
      </div>

      <div class="p-4">
        <div class="flex items-center justify-between mb-1">
          <span class="text-[10px] bg-green-50 text-green-600 font-semibold px-2 py-0.5 rounded-full">
            <?= htmlspecialchars($p['category']) ?>
          </span>
          <!-- Order count badge -->
          <?php if ($p['order_count'] > 0): ?>
          <span class="text-[10px] text-slate-400 flex items-center gap-0.5">
            <span class="material-icons-round text-xs">shopping_bag</span>
            <?= $p['order_count'] ?> orders
          </span>
          <?php endif; ?>
        </div>

        <h3 class="font-semibold text-slate-800 mt-2 mb-1 text-sm truncate">
          <?= htmlspecialchars($p['name']) ?>
        </h3>

        <!-- Popularity bar -->
        <?php
          $maxOrders = 20; // adjust based on your data
          $pct = min(100, ($p['order_count'] / $maxOrders) * 100);
        ?>
        <div class="w-full bg-slate-100 rounded-full h-1.5 mb-3">
          <div class="bg-brand h-1.5 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
        </div>

        <div class="flex items-center justify-between">
          <span class="font-bold text-brand text-base">₹<?= number_format($p['price'], 2) ?></span>
          <a href="cart.php?add=<?= $p['id'] ?>"
             class="w-8 h-8 bg-brand hover:bg-brand2 text-white rounded-xl flex items-center justify-center transition">
            <span class="material-icons-round text-sm">add</span>
          </a>
        </div>
      </div>
    </div>
    <?php $rank++; endwhile;
    else: ?>
    <div class="col-span-full text-center py-16 text-slate-400 text-sm">
      <span class="material-icons-round text-5xl opacity-20 block mb-3">inventory_2</span>
      No products yet.
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══════════════ WHY ZOMAZON ══════════════ -->
<section class="bg-white border-t border-slate-100 py-14">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="font-bold text-xl text-slate-800 text-center mb-10">Why choose Zomazon?</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
      <?php foreach ([
        ['local_shipping','bg-green-50 text-green-600','Fast Delivery','Get your order delivered in under 30 minutes from local shops.'],
        ['storefront','bg-blue-50 text-blue-600','Support Local','Every purchase supports a local business owner in your area.'],
        ['verified','bg-amber-50 text-amber-600','Quality Assured','All products are verified and sellers are trusted locals.'],
      ] as [$icon,$style,$title,$desc]): ?>
      <div class="flex flex-col items-center text-center p-6 rounded-2xl bg-slate-50 border border-slate-100">
        <div class="w-14 h-14 <?= $style ?> rounded-2xl flex items-center justify-center mb-4">
          <span class="material-icons-round text-2xl"><?= $icon ?></span>
        </div>
        <h3 class="font-bold text-slate-800 mb-2"><?= $title ?></h3>
        <p class="text-slate-500 text-sm leading-relaxed"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══════════════ FOOTER ══════════════ -->
<footer class="bg-slate-900 text-slate-400 pt-12 pb-6">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
      <div class="col-span-2 md:col-span-1">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-8 h-8 bg-brand rounded-lg flex items-center justify-center">
            <span class="material-icons-round text-white text-lg">local_grocery_store</span>
          </div>
          <span class="font-bold text-white text-lg">Zomazon</span>
        </div>
        <p class="text-sm leading-relaxed">Fresh groceries from local sellers, delivered fast to your door.</p>
      </div>
      <div>
        <p class="text-white font-semibold mb-3 text-sm">Shop</p>
        <ul class="space-y-2 text-sm">
          <li><a href="Fruits.php" class="hover:text-white transition">Fruits</a></li>
          <li><a href="Vegetables.php" class="hover:text-white transition">Vegetables</a></li>
          <li><a href="Dairy.php" class="hover:text-white transition">Dairy</a></li>
          <li><a href="Snacks.php" class="hover:text-white transition">Snacks</a></li>
        </ul>
      </div>
      <div>
        <p class="text-white font-semibold mb-3 text-sm">Account</p>
        <ul class="space-y-2 text-sm">
          <li><a href="registration/Login.php" class="hover:text-white transition">Login</a></li>
          <li><a href="cart.php" class="hover:text-white transition">My Cart</a></li>
          <li><a href="orders.php" class="hover:text-white transition">My Orders</a></li>
        </ul>
      </div>
      <div>
        <p class="text-white font-semibold mb-3 text-sm">Sell on Zomazon</p>
        <ul class="space-y-2 text-sm">
          <li><a href="Seller/register.php" class="hover:text-white transition">Register Shop</a></li>
          <li><a href="Seller/index.php" class="hover:text-white transition">Seller Login</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-slate-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm">
      <p>© 2025 Zomazon. All rights reserved.</p>
      <p>Made with <span class="text-brand">♥</span> for local communities</p>
    </div>
  </div>
</footer>



<script>
  // ── Carousel ──
  const slides = document.querySelectorAll('.slide');
  const dotsEl = document.getElementById('dots');
  let cur = 0;
  slides.forEach((_, i) => {
    const d = document.createElement('button');
    d.className = `w-2 h-2 rounded-full transition-all ${i===0?'bg-white w-5':'bg-white/50'}`;
    d.onclick = () => goTo(i);
    dotsEl.appendChild(d);
  });
  function goTo(n) {
    slides[cur].classList.remove('active');
    dotsEl.children[cur].className='w-2 h-2 rounded-full transition-all bg-white/50';
    cur=n;
    slides[cur].classList.add('active');
    dotsEl.children[cur].className='w-5 h-2 rounded-full transition-all bg-white';
  }
  setInterval(()=>goTo((cur+1)%slides.length),3500);

  // ── AI Modal ──
  function openAI()  { document.getElementById('aiModal').classList.add('open'); }
  function closeAI() { document.getElementById('aiModal').classList.remove('open'); }

  // Close on backdrop click
  document.getElementById('aiModal').addEventListener('click', function(e) {
    if (e.target === this) closeAI();
  });

 
</script>

</body>
</html>