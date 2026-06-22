<?php
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"));
$total_sales    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total FROM orders"));
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"));
$recent_orders  = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC LIMIT 5");
$status_data    = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM orders GROUP BY status");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zomazon Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fb; }

        .nav-link { transition: background .15s, color .15s; }
        .nav-link:hover { background: #1f2937; }
        .nav-link.active { background: #1f2937; border-left: 3px solid #22c55e; }

        .stat-card { transition: transform .2s, box-shadow .2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(0,0,0,0.15); }

        .badge-Pending          { background:#fef9c3; color:#854d0e; }
        .badge-Shipped          { background:#dbeafe; color:#1e40af; }
        .badge-Delivered        { background:#dcfce7; color:#166534; }
        .badge-Cancelled        { background:#fee2e2; color:#991b1b; }
        .badge-OutforDelivery   { background:#ede9fe; color:#5b21b6; }

        tbody tr { transition: background .12s; }
        tbody tr:hover { background: #f8fafc; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    </style>
</head>
<body class="min-h-screen">

<!-- SIDEBAR -->
<aside class="fixed top-0 left-0 h-screen w-56 bg-gray-900 flex flex-col z-40">
    <div class="px-6 py-6 border-b border-gray-800">
        <h1 class="text-white font-bold text-lg tracking-tight">Zomazon</h1>
        <p class="text-gray-500 text-xs mt-0.5">Admin Panel</p>
    </div>

    <nav class="flex-1 py-4 overflow-y-auto">
        <?php
        $links = [
            ['dashboard.php',  'dashboard',    'Dashboard'],
            ['products.php',   'inventory_2',  'Products'],
            ['orders.php',     'receipt_long', 'Orders'],
            ['users.php',      'group',        'Users'],
            ['localshops.php', 'storefront',   'Registered Shops'],
            ['../index.php',   'language',     'View Website'],
        ];
        $current = basename($_SERVER['PHP_SELF']);
        foreach ($links as [$href, $icon, $label]):
            $active = ($current === basename($href)) ? 'active' : '';
        ?>
        <a href="<?php echo $href; ?>"
           class="nav-link <?php echo $active; ?> flex items-center gap-3 px-5 py-3 text-gray-400 hover:text-white text-sm font-medium">
            <span class="material-icons-round text-[19px]"><?php echo $icon; ?></span>
            <?php echo $label; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="p-4 border-t border-gray-800">
        <a href="logout.php"
           class="flex items-center gap-2 text-gray-400 hover:text-red-400 transition-colors text-sm font-medium px-1">
            <span class="material-icons-round text-[18px]">logout</span>
            Logout
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="ml-56 min-h-screen p-8">

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
        <p class="text-sm text-gray-400 mt-0.5">Welcome back, Admin 👋</p>
    </div>

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">

        <div class="stat-card rounded-2xl p-6 text-white shadow-lg"
             style="background: linear-gradient(135deg,#6366f1,#4f46e5)">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-medium opacity-80">Total Orders</p>
                <span class="material-icons-round text-white/60 text-[28px]">receipt_long</span>
            </div>
            <p class="text-4xl font-bold"><?php echo $total_orders['total']; ?></p>
            <p class="text-xs opacity-60 mt-1">All time</p>
        </div>

        <div class="stat-card rounded-2xl p-6 text-white shadow-lg"
             style="background: linear-gradient(135deg,#22c55e,#16a34a)">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-medium opacity-80">Total Sales</p>
                <span class="material-icons-round text-white/60 text-[28px]">payments</span>
            </div>
            <p class="text-4xl font-bold">₹<?php echo number_format($total_sales['total'] ?? 0, 2); ?></p>
            <p class="text-xs opacity-60 mt-1">Revenue earned</p>
        </div>

        <div class="stat-card rounded-2xl p-6 text-white shadow-lg"
             style="background: linear-gradient(135deg,#f59e0b,#d97706)">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-medium opacity-80">Total Products</p>
                <span class="material-icons-round text-white/60 text-[28px]">inventory_2</span>
            </div>
            <p class="text-4xl font-bold"><?php echo $total_products['total']; ?></p>
            <p class="text-xs opacity-60 mt-1">Listed items</p>
        </div>

    </div>

    <!-- BOTTOM ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Order Status Overview -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-5 flex items-center gap-2">
                <span class="material-icons-round text-indigo-500 text-[20px]">donut_large</span>
                Order Status
            </h3>
            <div class="space-y-3">
                <?php
                $status_rows = [];
                while ($row = mysqli_fetch_assoc($status_data)) $status_rows[] = $row;
                $grand_total = array_sum(array_column($status_rows, 'count'));

                $colors = [
                    'Pending'          => ['bg-amber-500',  'text-amber-600',  'bg-amber-50'],
                    'Shipped'          => ['bg-blue-500',   'text-blue-600',   'bg-blue-50'],
                    'Delivered'        => ['bg-green-500',  'text-green-600',  'bg-green-50'],
                    'Cancelled'        => ['bg-red-500',    'text-red-600',    'bg-red-50'],
                    'Out for Delivery' => ['bg-purple-500', 'text-purple-600', 'bg-purple-50'],
                ];

                foreach ($status_rows as $row):
                    $s   = $row['status'];
                    $c   = $row['count'];
                    $pct = $grand_total > 0 ? round($c / $grand_total * 100) : 0;
                    $col = $colors[$s] ?? ['bg-gray-400', 'text-gray-600', 'bg-gray-50'];
                ?>
                <div class="<?php echo $col[2]; ?> rounded-xl px-4 py-3">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-sm font-semibold <?php echo $col[1]; ?>"><?php echo htmlspecialchars($s); ?></span>
                        <span class="text-xs font-bold text-gray-600">
                            <?php echo $c; ?> <span class="text-gray-400 font-normal">(<?php echo $pct; ?>%)</span>
                        </span>
                    </div>
                    <div class="w-full bg-white/60 rounded-full h-1.5">
                        <div class="<?php echo $col[0]; ?> h-1.5 rounded-full transition-all" style="width:<?php echo $pct; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                    <span class="material-icons-round text-green-500 text-[20px]">shopping_bag</span>
                    Recent Orders
                </h3>
                <a href="orders.php" class="text-xs font-semibold text-indigo-500 hover:text-indigo-700 transition-colors">View all →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                            <th class="pb-3 font-semibold">#ID</th>
                            <th class="pb-3 font-semibold">Customer</th>
                            <th class="pb-3 font-semibold">Products</th>
                            <th class="pb-3 font-semibold">Total</th>
                            <th class="pb-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php while ($row = mysqli_fetch_assoc($recent_orders)):
                            $s = htmlspecialchars($row['status']);
                            $badge_key = str_replace(' ', '', $s);
                        ?>
                        <tr>
                            <td class="py-3 text-gray-400 font-medium">#<?php echo $row['id']; ?></td>
                            <td class="py-3">
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($row['phone']); ?></p>
                            </td>
                            <td class="py-3 max-w-[180px]">
                                <?php
                                $decoded = json_decode($row['products'], true);
                                if (is_array($decoded)):
                                ?>
                                <div class="flex flex-col gap-1">
                                    <?php foreach ($decoded as $p):
                                        $name = htmlspecialchars($p['name'] ?? '');
                                        // Support both 'qty' and 'quantity' key names
                                        $qty  = (int) ($p['qty'] ?? $p['quantity'] ?? 1);
                                    ?>
                                    <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-700 rounded-md px-2 py-0.5 max-w-[170px]">
                                        <span class="material-icons-round text-[11px] text-gray-400">fiber_manual_record</span>
                                        <span class="truncate"><?php echo $name; ?></span>
                                        <span class="ml-auto flex-shrink-0 bg-gray-200 text-gray-500 rounded px-1 font-semibold">x<?php echo $qty; ?></span>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-600 text-xs"><?php echo htmlspecialchars($row['products']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 font-bold text-gray-800">₹<?php echo number_format($row['total'], 2); ?></td>
                            <td class="py-3">
                                <span class="badge-<?php echo $badge_key; ?> px-2.5 py-1 rounded-full text-xs font-semibold">
                                    <?php echo $s; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</main>

</body>
</html>