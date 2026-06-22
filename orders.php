<?php
include 'config/db.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit;
}

// JOIN products table to get image and name
$stmt = $conn->prepare("
    SELECT o.*, p.image AS product_image, p.name AS product_name
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders – Zomazon</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        display: ['Syne', 'sans-serif'],
                    },
                    colors: {
                        bg:      '#0f1117',
                        surface: '#161b25',
                        card:    '#1a2030',
                        border:  '#1f2a3a',
                        green:   '#22c55e',
                        muted:   '#4b5563',
                    }
                }
            }
        }
    </script>

    <style>
        body { background-color: #0f1117; font-family: 'DM Sans', sans-serif; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f1117; }
        ::-webkit-scrollbar-thumb { background: #22c55e33; border-radius: 99px; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .order-card { animation: slideUp 0.35s ease both; }
        .order-card:nth-child(1) { animation-delay: 0.05s; }
        .order-card:nth-child(2) { animation-delay: 0.12s; }
        .order-card:nth-child(3) { animation-delay: 0.19s; }
        .order-card:nth-child(4) { animation-delay: 0.26s; }
        .order-card:nth-child(5) { animation-delay: 0.33s; }

        .order-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            padding: 1px;
            background: linear-gradient(135deg, #22c55e22, transparent 60%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        .order-card:hover::before { opacity: 1; }

        .tracker-wrap { position: relative; }
        .tracker-wrap::before {
            content: '';
            position: absolute;
            top: 14px;
            left: 14px;
            right: 14px;
            height: 2px;
            background: #1f2a3a;
            z-index: 0;
        }
    </style>
</head>

<body class="min-h-screen text-white">

    <!-- Top bar -->
    <header class="sticky top-0 z-50 backdrop-blur-md bg-bg/80 border-b border-border">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center gap-3">
            <a href="index.php" class="text-muted hover:text-white transition-colors">
                <span class="material-icons-round text-[22px]">arrow_back</span>
            </a>
            <h1 class="font-display font-bold text-xl text-white tracking-tight">My Orders</h1>
            <span class="ml-auto text-xs text-muted font-medium">
                <?php echo $result->num_rows; ?> order<?php echo $result->num_rows != 1 ? 's' : ''; ?>
            </span>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8 space-y-5">

        <?php if ($result->num_rows === 0): ?>

        <!-- Empty state -->
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-20 h-20 rounded-2xl bg-surface flex items-center justify-center mb-5 border border-border">
                <span class="material-icons-round text-4xl text-muted">inventory_2</span>
            </div>
            <p class="font-display font-bold text-xl text-white mb-2">No orders yet</p>
            <p class="text-sm text-muted mb-6">You haven't placed any orders. Start shopping!</p>
            <a href="index.php" class="bg-green text-black font-semibold px-6 py-2.5 rounded-lg text-sm hover:bg-green/90 transition-colors">
                Browse Products
            </a>
        </div>

        <?php else: ?>

        <?php
        $steps = ['Pending', 'Shipped', 'Out for Delivery', 'Delivered'];

        while ($row = $result->fetch_assoc()):
            $status   = htmlspecialchars($row['status']);
            $order_id = (int) $row['id'];
            $total    = htmlspecialchars($row['total']);
            $products = htmlspecialchars($row['products']);

            // ── Delivery date fix ──────────────────────────────────────────
            // Use stored delivery_date if set, otherwise fall back to
            // created_at + 5 days so something always shows.
            if (!empty($row['delivery_date'])) {
                $delivery = date("d F Y", strtotime($row['delivery_date']));
            } else {
                $delivery = date("d F Y", strtotime($row['created_at'] . ' +5 days'));
            }

            // ── Product image fix ──────────────────────────────────────────
            $img_file = $row['product_image'] ?? null;
            $img_name = !empty($row['product_name'])
                        ? htmlspecialchars($row['product_name'])
                        : $products;

            // Strip trailing .1 / .2 etc (e.g. "banana.png.1" → "banana.png")
            if ($img_file) {
                $img_file = preg_replace('/\.\d+$/', '', $img_file);
            }

            // Images are stored in the images/ folder
            $img_src = null;
            if ($img_file) {
                $img_src = "images/" . htmlspecialchars($img_file);
            }

            $current_step = array_search($status, $steps);

            $badge = match($status) {
                'Pending'          => ['bg-amber-500/15 text-amber-400',   'schedule'],
                'Shipped'          => ['bg-blue-500/15 text-blue-400',     'local_shipping'],
                'Out for Delivery' => ['bg-purple-500/15 text-purple-400', 'directions_bike'],
                'Delivered'        => ['bg-green/15 text-green',           'check_circle'],
                'Cancelled'        => ['bg-red-500/15 text-red-400',       'cancel'],
                default            => ['bg-muted/20 text-muted',           'help_outline'],
            };
        ?>

        <div class="order-card relative bg-card border border-border rounded-2xl p-5 hover:border-green/20 transition-colors duration-300">

            <!-- Header row -->
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <p class="text-xs text-muted font-medium uppercase tracking-widest mb-0.5">Order</p>
                    <p class="font-display font-bold text-white text-lg">#<?php echo $order_id; ?></p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $badge[0]; ?>">
                    <span class="material-icons-round text-[14px]"><?php echo $badge[1]; ?></span>
                    <?php echo $status; ?>
                </span>
            </div>

            <div class="border-t border-border mb-4"></div>

            <!-- Product image + name -->
            <?php if ($img_src): ?>
            <div class="mb-4">
                <p class="text-xs text-muted uppercase tracking-widest font-medium mb-3">Item Ordered</p>
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 rounded-2xl overflow-hidden bg-surface border border-border ring-1 ring-white/5 flex-shrink-0">
                        <img src="<?php echo $img_src; ?>"
                             alt="<?php echo $img_name; ?>"
                             class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                             onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><span class=\'material-icons-round text-3xl text-muted\'>image_not_supported</span></div>'">
                    </div>
                    <div>
                        <p class="text-sm text-white font-semibold leading-snug mb-1"><?php echo $img_name; ?></p>
                        <p class="text-xs text-muted">Qty: 1</p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- No image: just show product name as text -->
            <div class="mb-4">
                <p class="text-xs text-muted uppercase tracking-widest font-medium mb-1">Item Ordered</p>
                <p class="text-sm text-white/80 font-medium"><?php echo $products; ?></p>
            </div>
            <?php endif; ?>

            <!-- Delivery date + total -->
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs text-muted flex items-center gap-1">
                    <span class="material-icons-round text-[14px]">calendar_today</span>
                    Delivery: <?php echo $delivery; ?>
                </p>
                <div class="text-right">
                    <p class="text-xs text-muted mb-0.5">Order Total</p>
                    <p class="font-display font-bold text-green text-lg">₹<?php echo $total; ?></p>
                </div>
            </div>

            <!-- Order tracker -->
            <?php if ($status !== 'Cancelled'): ?>
            <div class="bg-surface rounded-xl px-4 py-4 mb-4">
                <div class="tracker-wrap flex justify-between items-start">
                    <?php foreach ($steps as $i => $step):
                        $is_done   = ($current_step !== false && $i <= $current_step);
                        $is_active = ($step === $status);
                    ?>
                    <div class="flex flex-col items-center gap-1.5 relative z-10 flex-1">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center transition-all duration-300
                            <?php echo $is_done ? 'bg-green' : 'bg-border'; ?>">
                            <?php if ($is_done): ?>
                                <span class="material-icons-round text-black text-[14px]">check</span>
                            <?php else: ?>
                                <div class="w-2 h-2 rounded-full bg-muted"></div>
                            <?php endif; ?>
                        </div>
                        <p class="text-[10px] text-center leading-tight font-medium
                            <?php echo $is_active ? 'text-green' : ($is_done ? 'text-white/70' : 'text-muted'); ?>">
                            <?php echo $step; ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3 mb-4 flex items-center gap-2">
                <span class="material-icons-round text-red-400 text-[18px]">info</span>
                <p class="text-xs text-red-400 font-medium">This order has been cancelled.</p>
            </div>
            <?php endif; ?>

            <!-- Action row -->
            <div class="flex items-center justify-between gap-3">
                <a href="order_detail.php?id=<?php echo $order_id; ?>"
                   class="text-xs text-muted hover:text-white transition-colors flex items-center gap-1">
                    <span class="material-icons-round text-[15px]">receipt_long</span>
                    View Details
                </a>

                <?php if ($status !== 'Delivered' && $status !== 'Cancelled'): ?>
                <a href="cancel.php?id=<?php echo $order_id; ?>"
                   onclick="return confirm('Cancel order #<?php echo $order_id; ?>?')"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 text-xs font-semibold transition-colors border border-red-500/20">
                    <span class="material-icons-round text-[14px]">cancel</span>
                    Cancel Order
                </a>
                <?php elseif ($status === 'Delivered'): ?>
                <a href="reorder.php?id=<?php echo $order_id; ?>"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-green/10 text-green hover:bg-green/20 text-xs font-semibold transition-colors border border-green/20">
                    <span class="material-icons-round text-[14px]">replay</span>
                    Reorder
                </a>
                <?php endif; ?>
            </div>

        </div>

        <?php endwhile; ?>
        <?php endif; ?>

    </main>
</body>
</html>