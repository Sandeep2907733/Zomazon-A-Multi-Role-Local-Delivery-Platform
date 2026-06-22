<?php
include 'config/db.php';

/* ── GET PRODUCT ID ── */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header("Location: index.php"); exit(); }

/* ── FETCH PRODUCT ── */
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) { header("Location: index.php"); exit(); }

/* ── CART COUNT ── */
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cart_count += $item['qty'];
}

/* ── CHECK IF USER PURCHASED (session-based: checks if product was ever in cart + ordered) ──
   If you have an orders table, swap this logic. For now we use a simple session flag. */
$user_id  = $_SESSION['user_id'] ?? null;    // null if no login system
$has_purchased = false;
$already_rated = false;

if ($user_id) {
    // Check purchases
    $stmt2 = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND product_id = ? LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param("ii", $user_id, $id);
        $stmt2->execute();
        $has_purchased = (bool) $stmt2->get_result()->fetch_assoc();
    }
    // Check existing rating
    $stmt3 = $conn->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ? LIMIT 1");
    if ($stmt3) {
        $stmt3->bind_param("ii", $user_id, $id);
        $stmt3->execute();
        $already_rated = (bool) $stmt3->get_result()->fetch_assoc();
    }
}

/* ── FETCH RATINGS ── */
$ratings_data = ['avg' => 0, 'count' => 0, 'dist' => [5=>0,4=>0,3=>0,2=>0,1=>0]];
$r = $conn->query("SELECT AVG(stars) as avg, COUNT(*) as cnt FROM ratings WHERE product_id = $id");
if ($r) {
    $row = $r->fetch_assoc();
    $ratings_data['avg']   = round((float)($row['avg'] ?? 0), 1);
    $ratings_data['count'] = (int)($row['cnt'] ?? 0);
}
$r2 = $conn->query("SELECT stars, COUNT(*) as n FROM ratings WHERE product_id = $id GROUP BY stars");
if ($r2) {
    while ($row = $r2->fetch_assoc()) $ratings_data['dist'][(int)$row['stars']] = (int)$row['n'];
}

/* ── FETCH RECENT REVIEWS ── */
$reviews = [];
$r3 = $conn->query("SELECT r.stars, r.review, r.created_at,
                           COALESCE(r.user_name, 'Anonymous') as user_name
                    FROM ratings r
                    WHERE r.product_id = $id
                    ORDER BY r.created_at DESC LIMIT 8");
if ($r3) { while ($row = $r3->fetch_assoc()) $reviews[] = $row; }

/* ── FETCH SIMILAR PRODUCTS ── */
$similar = [];
$cat = $conn->real_escape_string($product['category']);
$r4  = $conn->query("SELECT * FROM products WHERE category = '$cat' AND id != $id ORDER BY RAND() LIMIT 6");
if ($r4) { while ($row = $r4->fetch_assoc()) $similar[] = $row; }

/* ── ADD TO CART ── */
if (isset($_POST['add_to_cart'])) {
    $pid = (int)$_POST['id'];
    $qty_add = max(1, (int)($_POST['qty'] ?? 1));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    if ($p) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['qty'] += $qty_add;
        } else {
            $_SESSION['cart'][$pid] = ['name'=>$p['name'],'price'=>$p['price'],'qty'=>$qty_add,'image'=>$p['image']];
        }
        $_SESSION['success'] = "Added to cart!";
    }
    header("Location: product.php?id=$id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($product['name']); ?> – Zomazon</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans:    ['Nunito','sans-serif'],
                display: ['Lora','serif'],
            }
        }
    }
}
</script>

<style>
* { box-sizing: border-box; }
body { background:#f8f7f4; font-family:'Nunito',sans-serif; color:#1a1a1a; }

/* Star rating input */
.star-input { display:flex; flex-direction:row-reverse; gap:4px; }
.star-input input { display:none; }
.star-input label {
    font-size:32px; color:#d1d5db; cursor:pointer;
    transition:color .15s, transform .15s;
    line-height:1;
}
.star-input label:hover,
.star-input label:hover ~ label,
.star-input input:checked ~ label {
    color:#f59e0b;
}
.star-input label:hover { transform:scale(1.15); }

/* Static stars display */
.stars-display { display:inline-flex; gap:2px; }
.star-filled  { color:#f59e0b; font-size:16px; }
.star-half    { color:#f59e0b; font-size:16px; }
.star-empty   { color:#d1d5db; font-size:16px; }

/* Toast */
#toast {
    position:fixed; bottom:28px; left:50%;
    transform:translateX(-50%) translateY(60px);
    background:#16a34a; color:#fff;
    padding:10px 22px; border-radius:99px;
    font-size:13px; font-weight:700;
    z-index:999; opacity:0;
    transition:transform .32s cubic-bezier(.3,1.1,.5,1), opacity .25s;
    white-space:nowrap;
    box-shadow:0 4px 20px rgba(22,163,74,.35);
}
#toast.show { transform:translateX(-50%) translateY(0); opacity:1; }

/* Card hover */
.prod-card {
    transition:transform .2s ease, box-shadow .2s ease;
}
.prod-card:hover {
    transform:translateY(-4px);
    box-shadow:0 12px 32px rgba(0,0,0,.10);
}

/* Rating bar */
.rating-bar-fill {
    height:8px; border-radius:99px;
    background:linear-gradient(90deg,#f59e0b,#fbbf24);
    transition:width .6s cubic-bezier(.4,0,.2,1);
}

/* Image zoom */
#main-img { transition:transform .4s ease; }
#main-img:hover { transform:scale(1.04); }

@keyframes fadeUp {
    from { opacity:0; transform:translateY(12px); }
    to   { opacity:1; transform:translateY(0); }
}
.fade-up { animation:fadeUp .4s ease both; }

::-webkit-scrollbar { width:5px; }
::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:99px; }
</style>
</head>
<body class="min-h-screen">

<!-- ── NAVBAR ── -->
<header class="sticky top-0 z-30 bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto flex justify-between items-center px-5 py-3">
        <div>
            <h1 class="font-display font-semibold text-xl text-green-700 leading-none">Zomazon</h1>
            <p class="text-[11px] text-gray-400 mt-0.5">Fresh groceries, fast delivery</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="index.php" class="text-gray-500 hover:text-green-600 transition-colors text-sm font-semibold flex items-center gap-1">
                <span class="material-icons-round text-[18px]">home</span>
                <span class="hidden sm:inline">Home</span>
            </a>
            <a href="cart.php" class="relative flex items-center gap-1.5 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 px-3 py-1.5 rounded-xl text-sm font-bold transition-colors">
                <span class="material-icons-round text-[18px]">shopping_cart</span>
                Cart
                <?php if ($cart_count > 0): ?>
                <span class="absolute -top-1.5 -right-1.5 bg-green-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                    <?php echo $cart_count; ?>
                </span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</header>

<?php if (isset($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast(<?php echo json_encode($_SESSION['success']); unset($_SESSION['success']); ?>));</script>
<?php endif; ?>
<?php if (isset($_SESSION['rating_msg'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast(<?php echo json_encode($_SESSION['rating_msg']); unset($_SESSION['rating_msg']); ?>));</script>
<?php endif; ?>

<!-- ── BREADCRUMB ── -->
<div class="max-w-7xl mx-auto px-5 pt-5 pb-1 text-xs text-gray-400 flex items-center gap-1 flex-wrap">
    <a href="index.php" class="hover:text-green-600 transition-colors">Home</a>
    <span class="material-icons-round text-[13px]">chevron_right</span>
    <a href="category.php?category=<?php echo urlencode($product['category']); ?>" class="hover:text-green-600 transition-colors">
        <?php echo htmlspecialchars($product['category']); ?>
    </a>
    <span class="material-icons-round text-[13px]">chevron_right</span>
    <span class="text-gray-700 font-semibold line-clamp-1"><?php echo htmlspecialchars($product['name']); ?></span>
</div>

<!-- ══════════════════════════════════════
     PRODUCT DETAIL SECTION
══════════════════════════════════════ -->
<main class="max-w-7xl mx-auto px-5 pt-4 pb-10">
<div class="bg-white rounded-3xl shadow-sm overflow-hidden fade-up">
    <div class="grid md:grid-cols-2 gap-0">

        <!-- LEFT: Image -->
        <div class="relative bg-gray-50 flex items-center justify-center min-h-72 p-6 md:min-h-[420px] overflow-hidden">
            <img id="main-img"
                 src="images/<?php echo htmlspecialchars($product['image']); ?>"
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 class="max-h-80 w-full object-contain rounded-2xl"
                 onerror="this.src='images/placeholder.png'">
            <!-- Category badge -->
            <span class="absolute top-4 left-4 bg-green-100 text-green-700 text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                <?php echo htmlspecialchars($product['category']); ?>
            </span>
            <!-- Stock badge -->
            <?php if (isset($product['stock'])): ?>
            <span class="absolute top-4 right-4 text-[11px] font-bold px-3 py-1 rounded-full
                <?php echo ($product['stock'] > 0) ? 'bg-green-600 text-white' : 'bg-red-100 text-red-600'; ?>">
                <?php echo ($product['stock'] > 0) ? 'In Stock' : 'Out of Stock'; ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Details -->
        <div class="p-6 md:p-8 flex flex-col justify-between">
            <div>
                <!-- Name -->
                <h1 class="font-display text-2xl md:text-3xl font-semibold text-gray-900 leading-snug mb-2">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>

                <!-- Rating summary row -->
                <div class="flex items-center gap-2 mb-4">
                    <div class="stars-display">
                        <?php
                        $avg = $ratings_data['avg'];
                        for ($s=1; $s<=5; $s++) {
                            if ($s <= floor($avg)) {
                                echo '<span class="material-icons-round star-filled">star</span>';
                            } elseif ($s - $avg < 1 && $s - $avg > 0) {
                                echo '<span class="material-icons-round star-half">star_half</span>';
                            } else {
                                echo '<span class="material-icons-round star-empty">star_border</span>';
                            }
                        }
                        ?>
                    </div>
                    <span class="text-sm font-bold text-gray-700"><?php echo $ratings_data['avg']; ?></span>
                    <span class="text-xs text-gray-400">(<?php echo $ratings_data['count']; ?> review<?php echo $ratings_data['count']!=1?'s':''; ?>)</span>
                    <?php if ($ratings_data['count'] > 0): ?>
                    <a href="#reviews" class="text-xs text-green-600 font-semibold hover:underline ml-1">See all</a>
                    <?php endif; ?>
                </div>

                <!-- Unit & Price -->
                <?php if (!empty($product['unit'])): ?>
                <p class="text-sm text-gray-400 mb-1"><?php echo htmlspecialchars($product['unit']); ?></p>
                <?php endif; ?>
                <p class="font-display text-3xl font-semibold text-green-700 mb-4">
                    ₹<?php echo number_format($product['price'], 2); ?>
                </p>

                <!-- Description -->
                <?php if (!empty($product['description'])): ?>
                <div class="bg-gray-50 rounded-xl p-4 mb-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">About this product</p>
                    <p class="text-sm text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Stock count -->
                <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                <p class="text-xs text-amber-600 font-semibold mb-4">
                    <span class="material-icons-round text-[14px] align-middle">inventory_2</span>
                    Only <?php echo (int)$product['stock']; ?> left in stock
                </p>
                <?php endif; ?>
            </div>

            <!-- Add to Cart form -->
            <form method="post" class="mt-auto">
                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                <div class="flex items-center gap-3 mb-4">
                    <p class="text-sm font-bold text-gray-600">Quantity:</p>
                    <button type="button" onclick="changeQty(-1)"
                            class="w-9 h-9 rounded-full border-2 border-gray-200 bg-gray-50 hover:bg-green-50 hover:border-green-400 font-bold text-xl flex items-center justify-center transition-all">−</button>
                    <span id="qty-display" class="text-lg font-bold text-gray-800 w-6 text-center">1</span>
                    <button type="button" onclick="changeQty(1)"
                            class="w-9 h-9 rounded-full border-2 border-gray-200 bg-gray-50 hover:bg-green-50 hover:border-green-400 font-bold text-xl flex items-center justify-center transition-all">+</button>
                    <input type="hidden" name="qty" id="qty-input" value="1">
                </div>
                <button type="submit" name="add_to_cart"
                        class="w-full bg-green-600 hover:bg-green-700 active:scale-[.98] text-white font-extrabold py-4 rounded-2xl text-base flex items-center justify-center gap-2 transition-all shadow-md shadow-green-200">
                    <span class="material-icons-round text-[22px]">add_shopping_cart</span>
                    Add to Cart
                </button>
            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════
     RATINGS & REVIEWS SECTION
══════════════════════════════════════ -->
<section id="reviews" class="mt-10 fade-up">
    <h2 class="font-display text-xl font-semibold text-gray-800 mb-5">Ratings & Reviews</h2>

    <div class="grid md:grid-cols-3 gap-6">

        <!-- Rating Overview Card -->
        <div class="bg-white rounded-3xl shadow-sm p-6 flex flex-col items-center justify-center text-center">
            <p class="font-display text-6xl font-semibold text-gray-900"><?php echo $ratings_data['avg'] ?: '–'; ?></p>
            <div class="stars-display my-2">
                <?php
                for ($s=1;$s<=5;$s++) {
                    if ($s <= floor($ratings_data['avg'])) echo '<span class="material-icons-round" style="color:#f59e0b;font-size:20px">star</span>';
                    elseif ($s - $ratings_data['avg'] < 1 && $s > $ratings_data['avg']) echo '<span class="material-icons-round" style="color:#f59e0b;font-size:20px">star_half</span>';
                    else echo '<span class="material-icons-round" style="color:#d1d5db;font-size:20px">star_border</span>';
                }
                ?>
            </div>
            <p class="text-sm text-gray-400"><?php echo $ratings_data['count']; ?> review<?php echo $ratings_data['count']!=1?'s':''; ?></p>

            <!-- Distribution bars -->
            <div class="w-full mt-5 space-y-2">
                <?php foreach ([5,4,3,2,1] as $star):
                    $n = $ratings_data['dist'][$star];
                    $pct = $ratings_data['count'] > 0 ? round($n / $ratings_data['count'] * 100) : 0;
                ?>
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-4 text-right font-bold text-gray-500"><?php echo $star; ?></span>
                    <span class="material-icons-round text-[14px] text-amber-400">star</span>
                    <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="rating-bar-fill" style="width:<?php echo $pct; ?>%"></div>
                    </div>
                    <span class="w-7 text-gray-400"><?php echo $n; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="md:col-span-2 space-y-4">

            <?php if (empty($reviews)): ?>
            <div class="bg-white rounded-3xl shadow-sm p-8 text-center">
                <span class="material-icons-round text-4xl text-gray-200 block mb-2">rate_review</span>
                <p class="font-semibold text-gray-500">No reviews yet. Be the first!</p>
            </div>
            <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
            <div class="bg-white rounded-2xl shadow-sm p-5 fade-up">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center font-bold text-green-700 text-sm uppercase">
                            <?php echo mb_substr($rev['user_name'],0,1); ?>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($rev['user_name']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo date('d M Y', strtotime($rev['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="flex gap-0.5">
                        <?php for ($s=1;$s<=5;$s++) echo '<span class="material-icons-round text-[15px]" style="color:'.($s<=$rev['stars']?'#f59e0b':'#d1d5db').'">star</span>'; ?>
                    </div>
                </div>
                <?php if (!empty($rev['review'])): ?>
                <p class="text-sm text-gray-600 leading-relaxed"><?php echo htmlspecialchars($rev['review']); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════
     WRITE A REVIEW
══════════════════════════════════════ -->
<section class="mt-8 fade-up">
    <?php if (!$user_id): ?>
    <!-- Not logged in -->
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-center gap-3">
        <span class="material-icons-round text-amber-500 text-3xl">lock</span>
        <div>
            <p class="font-bold text-amber-800">Sign in to leave a review</p>
            <p class="text-xs text-amber-600 mt-0.5">Only logged-in customers can rate products.</p>
        </div>
        <a href="login.php" class="ml-auto bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold px-4 py-2 rounded-xl transition-colors">Sign In</a>
    </div>

    <?php elseif ($already_rated): ?>
    <!-- Already rated -->
    <div class="bg-green-50 border border-green-200 rounded-2xl p-5 flex items-center gap-3">
        <span class="material-icons-round text-green-600 text-3xl">check_circle</span>
        <div>
            <p class="font-bold text-green-800">Thanks for your review!</p>
            <p class="text-xs text-green-600 mt-0.5">You've already rated this product.</p>
        </div>
    </div>

    <?php elseif (!$has_purchased): ?>
    <!-- Not purchased -->
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 flex items-center gap-3">
        <span class="material-icons-round text-blue-400 text-3xl">shopping_bag</span>
        <div>
            <p class="font-bold text-blue-800">Purchase required to review</p>
            <p class="text-xs text-blue-600 mt-0.5">Buy this product first to share your experience.</p>
        </div>
    </div>

    <?php else: ?>
    <!-- Write review form -->
    <div class="bg-white rounded-3xl shadow-sm p-6 md:p-8">
        <h3 class="font-display text-lg font-semibold text-gray-800 mb-1">Write a Review</h3>
        <p class="text-sm text-gray-400 mb-5">Share your experience with other shoppers</p>

        <form action="rating_submit.php" method="post">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

            <!-- Star picker -->
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Your Rating</p>
                <div class="star-input" id="star-input">
                    <?php for ($s=5;$s>=1;$s--): ?>
                    <input type="radio" name="stars" id="star<?php echo $s; ?>" value="<?php echo $s; ?>" <?php echo $s==5?'checked':''; ?>>
                    <label for="star<?php echo $s; ?>" title="<?php echo $s; ?> star<?php echo $s>1?'s':''; ?>">★</label>
                    <?php endfor; ?>
                </div>
                <p id="star-label" class="text-xs text-amber-600 font-semibold mt-2 h-4">Excellent</p>
            </div>

            <!-- Review text -->
            <div class="mb-5">
                <label class="text-xs font-bold uppercase tracking-widest text-gray-400 block mb-2">Your Review <span class="text-gray-300 font-normal normal-case">(optional)</span></label>
                <textarea name="review" rows="4" maxlength="500" placeholder="What did you like or dislike? How was the quality?"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-700 resize-none focus:outline-none focus:border-green-400 focus:ring-2 focus:ring-green-100 transition-all placeholder:text-gray-300"></textarea>
                <p class="text-[11px] text-gray-300 text-right mt-1">Max 500 characters</p>
            </div>

            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 active:scale-[.98] text-white font-extrabold py-3.5 px-8 rounded-xl text-sm flex items-center gap-2 transition-all">
                <span class="material-icons-round text-[18px]">send</span>
                Submit Review
            </button>
        </form>
    </div>
    <?php endif; ?>
</section>


<!-- ══════════════════════════════════════
     SIMILAR PRODUCTS
══════════════════════════════════════ -->
<?php if (!empty($similar)): ?>
<section class="mt-12 fade-up">
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-display text-xl font-semibold text-gray-800">More from <?php echo htmlspecialchars($product['category']); ?></h2>
        <a href="category.php?category=<?php echo urlencode($product['category']); ?>"
           class="text-xs font-bold text-green-600 hover:text-green-700 flex items-center gap-0.5 transition-colors">
            See all <span class="material-icons-round text-[15px]">chevron_right</span>
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <?php foreach ($similar as $i => $s): ?>
        <a href="product.php?id=<?php echo $s['id']; ?>"
           class="prod-card bg-white rounded-2xl overflow-hidden shadow-sm block"
           style="animation-delay:<?php echo $i*0.05; ?>s">
            <div class="h-36 bg-gray-50 overflow-hidden">
                <img src="images/<?php echo htmlspecialchars($s['image']); ?>"
                     alt="<?php echo htmlspecialchars($s['name']); ?>"
                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                     onerror="this.src='images/placeholder.png'">
            </div>
            <div class="p-3">
                <h3 class="text-xs font-bold text-gray-800 line-clamp-2 leading-snug mb-1">
                    <?php echo htmlspecialchars($s['name']); ?>
                </h3>
                <?php if (!empty($s['unit'])): ?>
                <p class="text-[10px] text-gray-400"><?php echo htmlspecialchars($s['unit']); ?></p>
                <?php endif; ?>
                <p class="text-green-700 font-extrabold text-sm mt-1">₹<?php echo number_format($s['price'],2); ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

</main><!-- /main -->

<!-- ── TOAST ── -->
<div id="toast">
    <span class="material-icons-round text-[14px] align-middle mr-1">check_circle</span>
    <span id="toast-msg"></span>
</div>

<script>
/* Qty stepper */
let qty = 1;
function changeQty(dir) {
    qty = Math.max(1, qty + dir);
    document.getElementById('qty-display').textContent = qty;
    document.getElementById('qty-input').value = qty;
}

/* Toast */
function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

/* Star label */
const starLabels = {5:'Excellent',4:'Good',3:'Okay',2:'Poor',1:'Terrible'};
document.querySelectorAll('.star-input input').forEach(inp => {
    inp.addEventListener('change', () => {
        const lbl = document.getElementById('star-label');
        if (lbl) lbl.textContent = starLabels[inp.value] || '';
    });
});
</script>

</body>
</html>