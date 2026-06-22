<?php
include 'config/db.php';

/* GET CATEGORY */
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

/* ADD TO CART */
if (isset($_POST['add_to_cart'])) {
    $id = (int) $_POST['id'];

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] += 1;
        } else {
            $_SESSION['cart'][$id] = [
                'name'  => $product['name'],
                'price' => $product['price'],
                'qty'   => 1,
                'image' => $product['image']
            ];
        }
        $_SESSION['success'] = "Added to cart!";
    }

    header("Location: category.php?category=" . urlencode($category));
    exit();
}

/* CART COUNT */
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cart_count += $item['qty'];
}

/* FETCH PRODUCTS */
$products_list = [];
if ($category !== '') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $products_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zomazon – <?php echo htmlspecialchars($category); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans:    ['Nunito', 'sans-serif'],
                        display: ['Lora', 'serif'],
                    }
                }
            }
        }
    </script>

    <style>
        * { box-sizing: border-box; }
        body { background: #f8f7f4; font-family: 'Nunito', sans-serif; color: #1a1a1a; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .prod-card {
            animation: fadeUp .3s ease both;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .prod-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.10);
        }

        /* Toast */
        #toast {
            position: fixed; bottom: 28px; left: 50%;
            transform: translateX(-50%) translateY(60px);
            background: #16a34a; color: #fff;
            padding: 10px 22px; border-radius: 99px;
            font-size: 13px; font-weight: 700;
            z-index: 999; opacity: 0;
            transition: transform .32s cubic-bezier(.3,1.1,.5,1), opacity .25s;
            white-space: nowrap;
            box-shadow: 0 4px 20px rgba(22,163,74,.35);
        }
        #toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
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
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?php echo json_encode($_SESSION['success']); unset($_SESSION['success']); ?>));</script>
<?php endif; ?>

<!-- ── BREADCRUMB ── -->
<div class="max-w-7xl mx-auto px-5 pt-5 pb-1 text-xs text-gray-400 flex items-center gap-1">
    <a href="index.php" class="hover:text-green-600 transition-colors">Home</a>
    <span class="material-icons-round text-[13px]">chevron_right</span>
    <span class="text-gray-700 font-semibold"><?php echo htmlspecialchars($category); ?></span>
</div>

<!-- ── PAGE HEADING ── -->
<div class="max-w-7xl mx-auto px-5 pt-2 pb-6">
    <h2 class="font-display text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($category); ?></h2>
    <p class="text-green-600 font-semibold"><?php echo count($products_list); ?> item<?php echo count($products_list) != 1 ? 's' : ''; ?> available</p>
</div>

<!-- ── PRODUCT GRID ── -->
<section class="max-w-7xl mx-auto px-5 pb-20">

    <?php if (empty($products_list)): ?>
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <span class="material-icons-round text-5xl text-gray-300 mb-3">search_off</span>
        <p class="font-display text-xl text-gray-700 mb-1">No products here</p>
        <p class="text-sm text-gray-400">Try browsing a different category.</p>
    </div>

    <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">

        <?php foreach ($products_list as $i => $row): ?>
        <div class="prod-card bg-white rounded-2xl overflow-hidden shadow-sm flex flex-col"
             style="animation-delay: <?php echo min($i * 0.05, 0.4); ?>s">

            <!-- Image — clicks through to product page -->
            <a href="product.php?id=<?php echo $row['id']; ?>" class="block">
                <div class="relative w-full h-44 overflow-hidden bg-gray-50">
                    <img src="images/<?php echo htmlspecialchars($row['image']); ?>"
                         alt="<?php echo htmlspecialchars($row['name']); ?>"
                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                         onerror="this.src='images/placeholder.png'">
                </div>
            </a>

            <!-- Info -->
            <div class="p-3 flex flex-col flex-1">
                <p class="text-[10px] font-bold uppercase tracking-widest text-green-600 mb-1">
                    <?php echo htmlspecialchars($row['category'] ?? $category); ?>
                </p>

                <!-- Name links to product page -->
                <a href="product.php?id=<?php echo $row['id']; ?>"
                   class="text-sm font-bold text-gray-800 line-clamp-2 leading-snug hover:text-green-700 transition-colors">
                    <?php echo htmlspecialchars($row['name']); ?>
                </a>

                <?php if (!empty($row['unit'])): ?>
                <p class="text-xs text-gray-400 mt-0.5"><?php echo htmlspecialchars($row['unit']); ?></p>
                <?php endif; ?>

                <p class="text-green-700 font-extrabold text-base mt-2">₹<?php echo number_format($row['price'], 2); ?></p>

                <!-- Buttons row -->
                <div class="mt-2 flex flex-col gap-1.5">
                    <!-- View Details -->
                    <a href="product.php?id=<?php echo $row['id']; ?>"
                       class="w-full flex items-center justify-center gap-1.5 border border-green-600 text-green-700 hover:bg-green-50 text-xs font-bold py-1.5 rounded-xl transition-all duration-150">
                        <span class="material-icons-round text-[14px]">open_in_full</span>
                        View Details
                    </a>

                    <!-- Add to Cart -->
                    <form method="post">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="add_to_cart"
                                class="w-full flex items-center justify-center gap-1.5 bg-green-600 hover:bg-green-700 active:scale-95 text-white text-xs font-bold py-2 rounded-xl transition-all duration-150">
                            <span class="material-icons-round text-[15px]">add_shopping_cart</span>
                            Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
    <?php endif; ?>
</section>

<!-- ── TOAST ── -->
<div id="toast">
    <span class="material-icons-round text-[14px] align-middle mr-1">check_circle</span>
    <span id="toast-msg">Added to cart!</span>
</div>

<script>
function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2600);
}
</script>

</body>
</html>