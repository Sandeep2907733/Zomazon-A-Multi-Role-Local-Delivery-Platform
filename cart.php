<?php
include 'config/db.php';

// ── LOGIN GUARD ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Access Denied — Zomazon</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{bg:'#0b0f1a',surface:'#111827',green:'#22c55e'},fontFamily:{syne:['Syne','sans-serif']}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
  @keyframes fill { to { width: 100%; } }
  .bar { animation: fill 3s linear forwards; }
  body { font-family: 'DM Sans', sans-serif; }
</style>
</head>
<body class="bg-[#0b0f1a] min-h-screen flex items-center justify-center">
  <div class="bg-[#111827] border border-white/5 rounded-2xl p-10 text-center max-w-xs w-full shadow-2xl">
    <div class="text-5xl mb-4">🔒</div>
    <h3 class="font-syne font-bold text-white text-xl mb-2">Login Required</h3>
    <p class="text-[#64748b] text-sm mb-6">You need to be logged in to view your cart.</p>
    <div class="bg-white/5 rounded-full h-1.5 overflow-hidden mb-3">
      <div class="bar h-full w-0 bg-[#22c55e] rounded-full"></div>
    </div>
    <p class="text-white/30 text-xs">Redirecting to login in 3 seconds…</p>
  </div>
  <script>setTimeout(() => { window.location.href = "registration/Login.php"; }, 3000);</script>
</body>
</html>
<?php
    exit();
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
// ── ACTIONS ──────────────────────────────────────────────────────────────────

// Buy Now → checkout
if (isset($_POST['buy_now'])) {
    header("Location: checkout.php");
    exit();
}

// Remove single item
if (isset($_GET['remove'])) {
    $rid = intval($_GET['remove']);
    unset($_SESSION['cart'][$rid]);
    header("Location: cart.php");
    exit();
}

// Update quantities
if (isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $id => $qty) {
        $id  = intval($id);
        $qty = intval($qty);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id]['qty'] = $qty;
        }
    }
    header("Location: cart.php?updated=1");
    exit();
}

// ── CALCULATE TOTALS + STOCK CHECK ──────────────────────────────────────────
$total          = 0;
$allow_checkout = true;
$cartItems      = [];

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $item) {
        $ps = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $ps->bind_param("i", $id);
        $ps->execute();
        $row   = $ps->get_result()->fetch_assoc();
        $stock = intval($row['stock'] ?? 0);

        if ($stock <= 0) $allow_checkout = false;

        $subtotal = $item['price'] * $item['qty'];
        $total   += $subtotal;

        $cartItems[$id] = array_merge($item, [
            'stock'    => $stock,
            'subtotal' => $subtotal,
        ]);
    }
}

$cartCount = array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart — Zomazon</title>

<script src="https://cdn.tailwindcss.com"></script>
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
        slideIn: { '0%':{ opacity:'0', transform:'translateX(50px)' }, '100%':{ opacity:'1', transform:'translateX(0)' } },
      },
      animation: {
        'fade-up':  'fadeUp 0.4s ease both',
        'slide-in': 'slideIn 0.3s ease both',
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
    content:''; position:fixed; inset:0; pointer-events:none; z-index:0;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  }
  .cart-row:nth-child(1){animation-delay:.04s}
  .cart-row:nth-child(2){animation-delay:.09s}
  .cart-row:nth-child(3){animation-delay:.14s}
  .cart-row:nth-child(4){animation-delay:.19s}
  .cart-row:nth-child(5){animation-delay:.24s}
  input[type=number]::-webkit-inner-spin-button,
  input[type=number]::-webkit-outer-spin-button { opacity:1; }
</style>
</head>

<body class="bg-bg text-white min-h-screen overflow-x-hidden">

<!-- ── TOAST (after update) ── -->
<?php if (isset($_GET['updated'])): ?>
<div id="toast"
     class="fixed top-5 right-5 z-50 flex items-center gap-3 bg-surface border border-green/30
            text-sm text-white px-5 py-3 rounded-xl shadow-2xl animate-slide-in">
  <span class="material-icons-round text-green text-[18px]">check_circle</span>
  Cart updated!
</div>
<script>setTimeout(()=>document.getElementById('toast')?.remove(), 3000);</script>
<?php endif; ?>

<!-- ── TOAST (item added) ── -->
<?php if (isset($_GET['added'])): ?>
<div id="toast2"
     class="fixed top-5 right-5 z-50 flex items-center gap-3 bg-surface border border-green/30
            text-sm text-white px-5 py-3 rounded-xl shadow-2xl animate-slide-in">
  <span class="material-icons-round text-green text-[18px]">add_shopping_cart</span>
  Item added to cart!
</div>
<script>setTimeout(()=>document.getElementById('toast2')?.remove(), 3000);</script>
<?php endif; ?>

<!-- ════════════════════ NAVBAR ════════════════════ -->
<nav class="sticky top-0 z-30 flex items-center justify-between px-6 md:px-10 h-16
            bg-bg/85 backdrop-blur-xl border-b border-white/5">

  <!-- Logo -->
  <a href="index.php"
     class="flex items-center gap-2 font-syne font-extrabold text-lg no-underline">
    <div class="w-8 h-8 rounded-lg bg-green/10 border border-green/30 grid place-items-center text-base">🛒</div>
    <span class="text-white">Zoma<span class="text-green">zon</span></span>
  </a>

  <!-- Nav links -->
  <div class="flex items-center gap-3">
    <a href="index.php"
       class="hidden sm:flex items-center gap-1.5 text-muted text-sm font-medium
              px-4 py-2 rounded-xl border border-white/5 bg-surface2
              hover:text-white hover:border-green/30 transition-all no-underline">
      <span class="material-icons-round text-[18px]">home</span>Home
    </a>
    <!-- Cart count pill -->
    <div class="flex items-center gap-2 bg-surface2 border border-white/5 rounded-xl px-4 py-2">
      <span class="material-icons-round text-green text-[18px]">shopping_cart</span>
      <span class="font-syne font-bold text-sm"><?= $cartCount ?> item<?= $cartCount !== 1 ? 's' : '' ?></span>
    </div>
  </div>
</nav>

<!-- ════════════════════ MAIN ════════════════════ -->
<div class="relative z-10 max-w-5xl mx-auto px-5 md:px-10 py-10 pb-24">

  <!-- Page title -->
  <div class="mb-8">
    <div class="inline-flex items-center gap-2 bg-green/10 border border-green/25 text-green
                text-xs font-semibold uppercase tracking-wider px-3 py-1.5 rounded-full mb-4">
      <span class="material-icons-round text-[13px]">shopping_cart</span>
      Your Cart
    </div>
    <h1 class="font-syne font-extrabold text-2xl md:text-3xl tracking-tight">
      <?php if (empty($cartItems)): ?>
        Nothing here yet
      <?php else: ?>
        Review your order
      <?php endif; ?>
    </h1>
  </div>

  <?php if (empty($cartItems)): ?>
  <!-- ── EMPTY STATE ── -->
  <div class="flex flex-col items-center justify-center py-28 gap-5 text-muted">
    <div class="w-24 h-24 rounded-2xl bg-surface2 border border-white/5
                flex items-center justify-center">
      <span class="material-icons-round text-5xl opacity-20">shopping_bag</span>
    </div>
    <div class="text-center">
      <p class="font-syne font-bold text-white text-xl mb-1">Your cart is empty</p>
      <p class="text-sm">Browse local shops and add something delicious.</p>
    </div>
    <a href="index.php"
       class="flex items-center gap-2 bg-green text-bg font-semibold px-6 py-3
              rounded-xl hover:bg-green/90 transition-all no-underline mt-2">
      <span class="material-icons-round text-[18px]">storefront</span>
      Browse Shops
    </a>
  </div>

  <?php else: ?>
  <!-- ── CART LAYOUT ── -->
  <form method="POST">

    <div class="grid lg:grid-cols-3 gap-6 items-start">

      <!-- Left: item list (spans 2 cols on lg) -->
      <div class="lg:col-span-2 space-y-4">

        <?php foreach ($cartItems as $id => $item):
          $stock      = $item['stock'];
          $fromSeller = !empty($item['from_seller']);
          $imgSrc     = ($fromSeller ? "Seller/uploads/" : "images/") .htmlspecialchars($item['image'] ?? '');
          $hasImage   = !empty($item['image']);
        ?>
        <div class="cart-row animate-fade-up bg-surface border border-white/5 rounded-2xl
                    overflow-hidden hover:border-white/10 transition-all duration-300">
          <div class="flex gap-4 p-4">

            <!-- Product image -->
            <div class="w-24 h-24 flex-shrink-0 rounded-xl overflow-hidden bg-surface2">
              <?php if ($hasImage): ?>
                <img src="<?= $imgSrc ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>"
                     class="w-full h-full object-cover"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="hidden w-full h-full items-center justify-center">
                  <span class="material-icons-round text-3xl text-white/10">inventory_2</span>
                </div>
              <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                  <span class="material-icons-round text-3xl text-white/10">inventory_2</span>
                </div>
              <?php endif; ?>
            </div>

            <!-- Details -->
            <div class="flex-1 min-w-0">
              <h3 class="font-syne font-bold text-base leading-snug mb-1">
                <?= htmlspecialchars($item['name']) ?>
              </h3>
              <p class="text-muted text-xs mb-2">
                <?= htmlspecialchars($item['shop_name'] ?? '') ?>
              </p>

              <!-- Stock badge -->
              <?php if ($stock <= 0): ?>
                <span class="inline-flex items-center gap-1 text-red-400 text-[11px] font-medium">
                  <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>Out of Stock
                </span>
              <?php elseif ($stock <= 5): ?>
                <span class="inline-flex items-center gap-1 text-orange-400 text-[11px] font-medium">
                  <span class="w-1.5 h-1.5 rounded-full bg-orange-400 inline-block"></span>
                  Only <?= $stock ?> left
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 text-green text-[11px] font-medium">
                  <span class="w-1.5 h-1.5 rounded-full bg-green inline-block"></span>In Stock
                </span>
              <?php endif; ?>

              <!-- Price row -->
              <div class="flex items-center justify-between mt-3 flex-wrap gap-3">
                <div>
                  <span class="text-muted text-xs">Unit price</span>
                  <p class="font-syne font-bold text-green text-base">
                    ₹<?= number_format($item['price'], 2) ?>
                  </p>
                </div>

                <!-- Qty + remove -->
                <div class="flex items-center gap-3">
                  <!-- Qty stepper -->
                  <div class="flex items-center gap-1 bg-surface2 border border-white/5 rounded-xl px-2 py-1">
                    <button type="button"
                            onclick="stepQty(<?= $id ?>, -1)"
                            class="w-6 h-6 rounded-lg text-muted hover:text-white hover:bg-white/5
                                   transition-colors flex items-center justify-center font-bold text-base">−</button>
                    <input type="number"
                           name="qty[<?= $id ?>]"
                           id="qty-<?= $id ?>"
                           value="<?= $item['qty'] ?>"
                           min="0"
                           max="<?= max($stock, $item['qty']) ?>"
                           class="w-10 text-center bg-transparent text-white text-sm font-bold
                                  outline-none border-none [appearance:textfield]">
                    <button type="button"
                            onclick="stepQty(<?= $id ?>, 1)"
                            class="w-6 h-6 rounded-lg text-muted hover:text-white hover:bg-white/5
                                   transition-colors flex items-center justify-center font-bold text-base">+</button>
                  </div>

                  <!-- Remove -->
                  <a href="cart.php?remove=<?= $id ?>"
                     class="flex items-center gap-1 text-muted text-xs hover:text-red-400
                            transition-colors no-underline px-2 py-1 rounded-lg hover:bg-red-400/5">
                    <span class="material-icons-round text-base">delete_outline</span>
                    <span class="hidden sm:inline">Remove</span>
                  </a>
                </div>
              </div>
            </div>

            <!-- Subtotal (right) -->
            <div class="hidden sm:flex flex-col items-end justify-center flex-shrink-0">
              <span class="text-muted text-[10px] uppercase tracking-wider mb-1">Subtotal</span>
              <span class="font-syne font-extrabold text-lg text-white"
                    id="sub-<?= $id ?>">₹<?= number_format($item['subtotal'], 2) ?></span>
            </div>

          </div>
        </div>
        <?php endforeach; ?>

        <!-- Update Cart -->
        <button type="submit" name="update_cart" value="1"
                class="flex items-center gap-2 text-sm text-muted font-medium
                       px-4 py-2.5 rounded-xl border border-white/5 bg-surface2
                       hover:text-white hover:border-green/30 transition-all cursor-pointer">
          <span class="material-icons-round text-[16px]">sync</span>
          Update Cart
        </button>

      </div><!-- /left col -->

      <!-- Right: Order summary -->
      <div class="lg:col-span-1">
        <div class="bg-surface border border-white/5 rounded-2xl p-6 sticky top-24">

          <h2 class="font-syne font-bold text-base mb-5 flex items-center gap-2">
            <span class="material-icons-round text-green text-[18px]">receipt_long</span>
            Order Summary
          </h2>

          <!-- Line items summary -->
          <div class="space-y-3 mb-5">
            <?php foreach ($cartItems as $id => $item): ?>
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted truncate max-w-[140px]">
                <?= htmlspecialchars($item['name']) ?>
                <span class="text-white/30"> × <?= $item['qty'] ?></span>
              </span>
              <span class="font-medium flex-shrink-0 ml-2">₹<?= number_format($item['subtotal'], 2) ?></span>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="border-t border-white/5 pt-4 mb-5">
            <div class="flex items-center justify-between">
              <span class="text-muted text-sm">Total</span>
              <span class="font-syne font-extrabold text-green text-2xl">
                ₹<?= number_format($total, 2) ?>
              </span>
            </div>
          </div>

          <!-- Buy Now / disabled -->
          <?php if ($allow_checkout): ?>
            <button type="submit" name="buy_now" value="1"
                    class="w-full flex items-center justify-center gap-2 bg-green text-bg
                           font-syne font-bold text-base py-3.5 rounded-xl
                           hover:bg-green/90 transition-all hover:scale-[1.02]
                           active:scale-[0.98] cursor-pointer border-0">
              <span class="material-icons-round text-[20px]">payment</span>
              Buy Now
            </button>
          <?php else: ?>
            <button disabled
                    class="w-full flex items-center justify-center gap-2 bg-white/10 text-white/30
                           font-syne font-bold text-base py-3.5 rounded-xl cursor-not-allowed border-0">
              <span class="material-icons-round text-[20px]">block</span>
              Item Out of Stock
            </button>
            <p class="text-center text-red-400 text-xs mt-3">
              Remove out-of-stock items to continue.
            </p>
          <?php endif; ?>

          <!-- Continue shopping -->
          <a href="index.php"
             class="flex items-center justify-center gap-1 mt-4 text-muted text-xs
                    hover:text-white transition-colors no-underline">
            <span class="material-icons-round text-sm">arrow_back</span>
            Continue Shopping
          </a>

        </div>
      </div><!-- /right col -->

    </div>
  </form>
  <?php endif; ?>

</div><!-- /main -->

<!-- ════════════════════ FOOTER ════════════════════ -->
<footer class="border-t border-white/5 py-6 text-center text-muted text-xs">
  © <?= date('Y') ?> Zomazon. All rights reserved.
</footer>

<!-- ════════════════════ JS ════════════════════ -->
<script>
function stepQty(id, delta) {
  const inp = document.getElementById('qty-' + id);
  if (!inp) return;
  let v = parseInt(inp.value) + delta;
  const min = parseInt(inp.min ?? 0);
  const max = parseInt(inp.max ?? 9999);
  v = Math.max(min, Math.min(max, v));
  inp.value = v;
}
</script>

</body>
</html>