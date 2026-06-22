<?php
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

/* ── STATS ── */
$total_orders     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders"))['c'];
$pending_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='Pending'"))['c'];
$delivered_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status='Delivered'"))['c'];
$total_revenue    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) AS c FROM orders WHERE status='Delivered'"))['c'];

/* ── SEARCH / FILTER ── */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';

$where_parts = [];
if ($search !== '') {
    $safe = mysqli_real_escape_string($conn, $search);
    $where_parts[] = "(full_name LIKE '%$safe%' OR phone LIKE '%$safe%' OR products LIKE '%$safe%' OR id LIKE '%$safe%')";
}
if ($filter !== '') {
    $safe_f = mysqli_real_escape_string($conn, $filter);
    $where_parts[] = "status = '$safe_f'";
}
$where_sql = count($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$result = mysqli_query($conn, "SELECT * FROM orders $where_sql ORDER BY id DESC");
$count  = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders – Zomazon Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Poppins', 'sans-serif'] } } }
        }
    </script>

    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fb; }

        .nav-link { transition: background .15s, color .15s; }
        .nav-link:hover { background: #1f2937; }
        .nav-link.active { background: #1f2937; border-left: 3px solid #22c55e; }

        .stat-card { transition: transform .2s, box-shadow .2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(0,0,0,.13); }

        tbody tr { transition: background .1s; }
        tbody tr:hover { background: #f8fafc; }

        /* Detail drawer */
        #detail-drawer {
            position: fixed; inset: 0; z-index: 60;
            display: none;
            background: rgba(0,0,0,.45);
            backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 16px;
        }
        #detail-drawer.open { display: flex; }

        .drawer-box {
            background: #fff; border-radius: 20px; padding: 28px;
            width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 24px 60px rgba(0,0,0,.18);
            animation: popIn .25s cubic-bezier(.3,1.1,.5,1);
        }

        @keyframes popIn {
            from { opacity:0; transform:scale(.94) translateY(10px); }
            to   { opacity:1; transform:scale(1)   translateY(0); }
        }

        select:focus { outline: none; border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.15); }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

        /* Status badges */
        .b-Pending          { background:#fef9c3; color:#854d0e; }
        .b-Shipped          { background:#dbeafe; color:#1e40af; }
        .b-OutforDelivery   { background:#ede9fe; color:#5b21b6; }
        .b-Delivered        { background:#dcfce7; color:#166534; }
        .b-Cancelled        { background:#fee2e2; color:#991b1b; }

        /* Filter pill active */
        .filter-pill.active { background: #1f2937; color: #fff; }
    </style>
</head>
<body class="min-h-screen">

<!-- ── SIDEBAR ── -->
<aside class="fixed top-0 left-0 h-screen w-56 bg-gray-900 flex flex-col z-40">
    <div class="px-6 py-6 border-b border-gray-800">
        <h1 class="text-white font-bold text-lg tracking-tight">Zomazon</h1>
        <p class="text-gray-500 text-xs mt-0.5">Admin Panel</p>
    </div>
    <nav class="flex-1 py-4 overflow-y-auto">
        <?php
        $links = [
            ['dashboard.php',  'dashboard',   'Dashboard'],
            ['products.php',   'inventory_2', 'Products'],
            ['orders.php',     'receipt_long','Orders'],
            ['users.php',      'group',       'Users'],
            ['localshops.php', 'storefront',  'Registered Shops'],
            ['../index.php',   'language',    'View Website'],
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
        <a href="logout.php" class="flex items-center gap-2 text-gray-400 hover:text-red-400 transition-colors text-sm font-medium px-1">
            <span class="material-icons-round text-[18px]">logout</span>
            Logout
        </a>
    </div>
</aside>

<!-- ── MAIN ── -->
<main class="ml-56 min-h-screen p-8">

    <!-- Page header -->
    <div class="mb-7">
        <h2 class="text-2xl font-bold text-gray-800">Orders Management</h2>
        <p class="text-sm text-gray-400 mt-0.5">View, filter and update all customer orders</p>
    </div>

    <!-- ── STAT CARDS ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

        <div class="stat-card rounded-2xl p-5 text-white shadow-md"
             style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-80">Total Orders</p>
                <span class="material-icons-round text-white/60 text-[24px]">receipt_long</span>
            </div>
            <p class="text-3xl font-bold"><?php echo $total_orders; ?></p>
        </div>

        <div class="stat-card rounded-2xl p-5 text-white shadow-md"
             style="background:linear-gradient(135deg,#f59e0b,#d97706)">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-80">Pending</p>
                <span class="material-icons-round text-white/60 text-[24px]">schedule</span>
            </div>
            <p class="text-3xl font-bold"><?php echo $pending_orders; ?></p>
            <p class="text-xs opacity-60 mt-0.5">Awaiting action</p>
        </div>

        <div class="stat-card rounded-2xl p-5 text-white shadow-md"
             style="background:linear-gradient(135deg,#22c55e,#16a34a)">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-80">Delivered</p>
                <span class="material-icons-round text-white/60 text-[24px]">check_circle</span>
            </div>
            <p class="text-3xl font-bold"><?php echo $delivered_orders; ?></p>
        </div>

        <div class="stat-card rounded-2xl p-5 text-white shadow-md"
             style="background:linear-gradient(135deg,#ec4899,#db2777)">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-80">Revenue Earned</p>
                <span class="material-icons-round text-white/60 text-[24px]">payments</span>
            </div>
            <p class="text-3xl font-bold">₹<?php echo number_format($total_revenue, 0); ?></p>
            <p class="text-xs opacity-60 mt-0.5">Delivered orders only</p>
        </div>

    </div>

    <!-- ── TABLE BOX ── -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">

            <!-- Filter pills -->
            <div class="flex flex-wrap gap-2">
                <?php
                $statuses = ['', 'Pending', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
                $labels   = ['All', 'Pending', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
                foreach ($statuses as $i => $s):
                    $is_active = ($filter === $s) ? 'active' : '';
                    $params = http_build_query(['search' => $search, 'filter' => $s]);
                ?>
                <a href="orders.php?<?php echo $params; ?>"
                   class="filter-pill <?php echo $is_active; ?> px-3 py-1.5 rounded-full text-xs font-semibold border border-gray-200 text-gray-600 hover:bg-gray-100 transition-colors">
                    <?php echo $labels[$i]; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Search -->
            <form method="get" class="flex items-center gap-2">
                <?php if ($filter): ?>
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <?php endif; ?>
                <div class="relative">
                    <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[17px]">search</span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search name, phone, product…"
                           class="pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm w-56 focus:outline-none focus:border-green-400 transition-all">
                </div>
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors">Go</button>
                <?php if ($search || $filter): ?>
                <a href="orders.php" class="text-xs text-gray-400 hover:text-gray-600 font-medium">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Result count -->
        <p class="text-xs text-gray-400 mb-4 font-medium">
            Showing <span class="text-gray-700 font-bold"><?php echo $count; ?></span> order<?php echo $count != 1 ? 's' : ''; ?>
            <?php if ($filter) echo ' · <span class="text-indigo-500">' . htmlspecialchars($filter) . '</span>'; ?>
            <?php if ($search) echo ' matching "<span class="text-gray-700">' . htmlspecialchars($search) . '</span>"'; ?>
        </p>

        <!-- Empty state -->
        <?php if ($count === 0): ?>
        <div class="text-center py-20 text-gray-400">
            <span class="material-icons-round text-5xl mb-3 block">inbox</span>
            <p class="font-semibold text-gray-600 mb-1">No orders found</p>
            <p class="text-sm">Try a different search or filter.</p>
        </div>

        <?php else: ?>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <th class="pb-3 font-semibold pr-4">#ID</th>
                        <th class="pb-3 font-semibold pr-4">Customer</th>
                        <th class="pb-3 font-semibold pr-4">Address</th>
                        <th class="pb-3 font-semibold pr-4">Products</th>
                        <th class="pb-3 font-semibold pr-4">Total</th>
                        <th class="pb-3 font-semibold pr-4">Status</th>
                        <th class="pb-3 font-semibold pr-4">Delivery</th>
                        <th class="pb-3 font-semibold text-center">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php while ($row = mysqli_fetch_assoc($result)):
                        $status    = htmlspecialchars($row['status']);
                        $badge_key = 'b-' . str_replace(' ', '', $status);
                    ?>
                    <tr>
                        <!-- ID -->
                        <td class="py-3.5 pr-4">
                            <button onclick="openDetail(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>)"
                                    class="font-bold text-indigo-500 hover:text-indigo-700 transition-colors">
                                #<?php echo $row['id']; ?>
                            </button>
                        </td>

                        <!-- Customer -->
                        <td class="py-3.5 pr-4">
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['full_name']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo htmlspecialchars($row['phone']); ?></p>
                        </td>

                        <!-- Address -->
                        <td class="py-3.5 pr-4 text-gray-500 text-xs max-w-[130px]">
                            <span class="line-clamp-2"><?php echo htmlspecialchars($row['address']); ?></span>
                        </td>

                        <!-- Products -->
                        <td class="py-3.5 pr-4 text-gray-600 max-w-[160px]">
                            <p class="truncate text-xs"><?php echo htmlspecialchars($row['products']); ?></p>
                        </td>

                        <!-- Total -->
                        <td class="py-3.5 pr-4 font-bold text-gray-800">
                            ₹<?php echo number_format($row['total'], 2); ?>
                        </td>

                        <!-- Status badge -->
                        <td class="py-3.5 pr-4">
                            <span class="<?php echo $badge_key; ?> px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap">
                                <?php echo $status; ?>
                            </span>
                        </td>

                        <!-- Delivery date -->
                        <td class="py-3.5 pr-4 text-xs text-gray-400">
                            <?php echo !empty($row['delivery_date']) ? htmlspecialchars($row['delivery_date']) : '—'; ?>
                        </td>

                        <!-- Update status -->
                        <td class="py-3.5">
                            <form method="POST" action="update_status.php" class="flex items-center gap-1.5">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <select name="status"
                                        class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-gray-700 bg-white focus:outline-none focus:border-green-400 cursor-pointer">
                                    <?php
                                    $opts = ['Pending','Shipped','Out for Delivery','Delivered','Cancelled'];
                                    foreach ($opts as $opt):
                                        $sel = ($row['status'] === $opt) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $opt; ?>" <?php echo $sel; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit"
                                        class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</main>

<!-- ── ORDER DETAIL MODAL ── -->
<div id="detail-drawer" onclick="if(event.target===this)closeDetail()">
    <div class="drawer-box">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="material-icons-round text-indigo-500">receipt_long</span>
                Order Details
            </h3>
            <button onclick="closeDetail()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                <span class="material-icons-round text-gray-500 text-[18px]">close</span>
            </button>
        </div>
        <div id="detail-body"></div>
    </div>
</div>

<script>
const statusColors = {
    'Pending':          'b-Pending',
    'Shipped':          'b-Shipped',
    'Out for Delivery': 'b-OutforDelivery',
    'Delivered':        'b-Delivered',
    'Cancelled':        'b-Cancelled',
};

function openDetail(o) {
    const badge = statusColors[o.status] || 'bg-gray-100 text-gray-600';

    document.getElementById('detail-body').innerHTML = `

        <!-- Order ID + status -->
        <div class="flex items-center justify-between mb-5 p-4 bg-indigo-50 rounded-2xl">
            <div>
                <p class="text-xs text-indigo-400 font-semibold uppercase tracking-wider">Order ID</p>
                <p class="text-2xl font-bold text-indigo-700">#${o.id}</p>
            </div>
            <span class="${badge} px-3 py-1.5 rounded-full text-xs font-bold">${o.status}</span>
        </div>

        <!-- Customer info -->
        <div class="space-y-3 mb-5">
            ${drow('person',        'Customer',       o.full_name)}
            ${drow('call',          'Phone',          o.phone)}
            ${drow('place',         'Address',        o.address)}
            ${drow('inventory_2',   'Products',       o.products)}
            ${drow('calendar_today','Delivery Date',  o.delivery_date || '—')}
        </div>

        <!-- Divider -->
        <div class="border-t border-gray-100 mb-4"></div>

        <!-- Total -->
        <div class="flex items-center justify-between bg-green-50 rounded-xl px-4 py-3">
            <p class="text-sm font-semibold text-gray-600">Order Total</p>
            <p class="text-xl font-bold text-green-600">₹${parseFloat(o.total || 0).toFixed(2)}</p>
        </div>

        <!-- Quick update from modal -->
        <form method="POST" action="update_status.php" class="mt-4 flex gap-2">
            <input type="hidden" name="id" value="${o.id}">
            <select name="status" class="flex-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-gray-700 bg-white focus:outline-none focus:border-green-400">
                ${['Pending','Shipped','Out for Delivery','Delivered','Cancelled']
                    .map(s => `<option value="${s}" ${s===o.status?'selected':''}>${s}</option>`).join('')}
            </select>
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition-colors">
                Update
            </button>
        </form>
    `;

    document.getElementById('detail-drawer').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function drow(icon, label, value) {
    return `
        <div class="flex items-start gap-3 bg-gray-50 rounded-xl px-4 py-3">
            <span class="material-icons-round text-gray-400 text-[18px] mt-0.5">${icon}</span>
            <div>
                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">${label}</p>
                <p class="text-sm text-gray-800 font-medium mt-0.5">${value || '—'}</p>
            </div>
        </div>`;
}

function closeDetail() {
    document.getElementById('detail-drawer').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetail(); });
</script>

</body>
</html>