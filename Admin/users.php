<?php
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$msg = '';
$msg_type = '';

/* ── DELETE USER ── */
if (isset($_POST['delete_user'])) {
    $uid = (int) $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE p_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $msg = "User deleted successfully.";
    $msg_type = "error";
    header("Location: users.php?msg=deleted");
    exit();
}

/* ── UPDATE USER ── */
if (isset($_POST['update_user'])) {
    $uid   = (int)   $_POST['user_id'];
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE p_id=?");
    $stmt->bind_param("sssi", $name, $email, $phone, $uid);
    $stmt->execute();
    header("Location: users.php?msg=updated");
    exit();
}

/* ── FLASH MESSAGES ── */
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') { $msg = "User deleted successfully.";  $msg_type = "red"; }
    if ($_GET['msg'] === 'updated') { $msg = "User updated successfully.";  $msg_type = "green"; }
}

/* ── SEARCH ── */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

/* ── FETCH USERS with order stats ── */
if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $users_result = $conn->query("
        SELECT u.*,
               COUNT(o.id)   AS order_count,
               COALESCE(SUM(o.total), 0) AS total_spent
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.p_id
        WHERE u.name LIKE '%$safe%' OR u.email LIKE '%$safe%' OR u.phone LIKE '%$safe%'
        GROUP BY u.p_id
        ORDER BY u.p_id DESC
    ");
} else {
    $users_result = $conn->query("
        SELECT u.*,
               COUNT(o.id)   AS order_count,
               COALESCE(SUM(o.total), 0) AS total_spent
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.p_id
        GROUP BY u.p_id
        ORDER BY u.p_id DESC
    ");
}

$users = [];
while ($row = $users_result->fetch_assoc()) $users[] = $row;

$total_users   = count($users);
$active_users  = count(array_filter($users, fn($u) => $u['order_count'] > 0));
$total_revenue = array_sum(array_column($users, 'total_spent'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users – Zomazon Admin</title>

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

        /* Modal */
        #edit-modal, #orders-modal {
            display: none;
            position: fixed; inset: 0; z-index: 60;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        #edit-modal.open, #orders-modal.open { display: flex; }

        .modal-box {
            background: white;
            border-radius: 20px;
            padding: 28px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.15);
            animation: popIn .25s cubic-bezier(.3,1.1,.5,1);
        }

        .orders-modal-box {
            background: white;
            border-radius: 20px;
            padding: 28px;
            width: 100%;
            max-width: 680px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 24px 60px rgba(0,0,0,0.15);
            animation: popIn .25s cubic-bezier(.3,1.1,.5,1);
        }

        @keyframes popIn {
            from { opacity:0; transform: scale(.94) translateY(12px); }
            to   { opacity:1; transform: scale(1) translateY(0); }
        }

        tbody tr { transition: background .1s; }
        tbody tr:hover { background: #f8fafc; }

        .stat-card { transition: transform .2s, box-shadow .2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(0,0,0,0.12); }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

        input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); }

        .badge-Pending   { background:#fef9c3; color:#854d0e; }
        .badge-Shipped   { background:#dbeafe; color:#1e40af; }
        .badge-Delivered { background:#dcfce7; color:#166534; }
        .badge-Cancelled { background:#fee2e2; color:#991b1b; }
        .badge-OutforDelivery { background:#ede9fe; color:#5b21b6; }
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

    <!-- Header -->
    <div class="mb-7">
        <h2 class="text-2xl font-bold text-gray-800">User Management</h2>
        <p class="text-sm text-gray-400 mt-0.5">View, edit, delete users and track their orders</p>
    </div>

    <!-- Flash message -->
    <?php if ($msg): ?>
    <div class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium
        <?php echo $msg_type === 'red' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'; ?>">
        <span class="material-icons-round text-[18px]"><?php echo $msg_type === 'red' ? 'delete' : 'check_circle'; ?></span>
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-7">

        <div class="stat-card rounded-2xl p-5 text-white shadow-md"
             style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-80">Total Users</p>
                <span class="material-icons-round text-white/60 text-[26px]">group</span>
            </div>
            <p class="text-3xl font-bold"><?php echo $total_users; ?></p>
        </div>

        <div class="stat-card rounded-2xl p-5 text-white shadow-md"
             style="background:linear-gradient(135deg,#22c55e,#16a34a)">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-80">Active Buyers</p>
                <span class="material-icons-round text-white/60 text-[26px]">shopping_bag</span>
            </div>
            <p class="text-3xl font-bold"><?php echo $active_users; ?></p>
            <p class="text-xs opacity-60 mt-0.5">Users with orders</p>
        </div>

        <div class="stat-card rounded-2xl p-5 text-white shadow-md"
             style="background:linear-gradient(135deg,#f59e0b,#d97706)">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-80">Total User Revenue</p>
                <span class="material-icons-round text-white/60 text-[26px]">payments</span>
            </div>
            <p class="text-3xl font-bold">₹<?php echo number_format($total_revenue, 0); ?></p>
        </div>

    </div>

    <!-- Search + table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

        <!-- Search bar -->
        <div class="flex items-center justify-between gap-4 mb-5">
            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <span class="material-icons-round text-indigo-500 text-[20px]">manage_accounts</span>
                All Users
                <span class="bg-indigo-50 text-indigo-600 text-xs font-bold px-2 py-0.5 rounded-full ml-1"><?php echo $total_users; ?></span>
            </h3>
            <form method="get" class="flex items-center gap-2">
                <div class="relative">
                    <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search name, email, phone…"
                           class="pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm w-64 transition-all">
                </div>
                <button type="submit"
                        class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors">
                    Search
                </button>
                <?php if ($search): ?>
                <a href="users.php" class="text-sm text-gray-400 hover:text-gray-600 transition-colors font-medium">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <?php if (empty($users)): ?>
        <div class="text-center py-16 text-gray-400">
            <span class="material-icons-round text-5xl mb-2 block">person_off</span>
            <p class="font-medium">No users found.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <th class="pb-3 font-semibold pr-4">#</th>
                        <th class="pb-3 font-semibold pr-4">User</th>
                        <th class="pb-3 font-semibold pr-4">Phone</th>
                        <th class="pb-3 font-semibold pr-4 text-center">Orders</th>
                        <th class="pb-3 font-semibold pr-4 text-right">Total Spent</th>
                        <th class="pb-3 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <!-- ID -->
                        <td class="py-3.5 pr-4 text-gray-400 font-medium">#<?php echo $u['p_id']; ?></td>

                        <!-- User info -->
                        <td class="py-3.5 pr-4">
                            <div class="flex items-center gap-3">
                                <!-- Avatar initials -->
                                <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm shrink-0">
                                    <?php echo strtoupper(substr($u['name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($u['name']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo htmlspecialchars($u['email']); ?></p>
                                </div>
                            </div>
                        </td>

                        <!-- Phone -->
                        <td class="py-3.5 pr-4 text-gray-600"><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>

                        <!-- Orders count -->
                        <td class="py-3.5 pr-4 text-center">
                            <?php if ($u['order_count'] > 0): ?>
                            <button onclick="viewOrders(<?php echo $u['p_id']; ?>, <?php echo htmlspecialchars(json_encode($u['name']), ENT_QUOTES); ?>)"
                                    class="inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 px-3 py-1 rounded-full text-xs font-bold transition-colors">
                                <span class="material-icons-round text-[13px]">receipt_long</span>
                                <?php echo $u['order_count']; ?> order<?php echo $u['order_count'] > 1 ? 's' : ''; ?>
                            </button>
                            <?php else: ?>
                            <span class="text-gray-300 text-xs">No orders</span>
                            <?php endif; ?>
                        </td>

                        <!-- Total spent -->
                        <td class="py-3.5 pr-4 text-right font-bold <?php echo $u['total_spent'] > 0 ? 'text-green-600' : 'text-gray-300'; ?>">
                            ₹<?php echo number_format($u['total_spent'], 2); ?>
                        </td>

                        <!-- Actions -->
                        <td class="py-3.5">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Edit -->
                                <button onclick="openEdit(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES); ?>)"
                                        class="w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 flex items-center justify-center transition-colors"
                                        title="Edit user">
                                    <span class="material-icons-round text-[16px]">edit</span>
                                </button>

                                <!-- Delete -->
                                <form method="post" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                    <input type="hidden" name="user_id" value="<?php echo $u['p_id']; ?>">
                                    <button type="submit" name="delete_user"
                                            class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition-colors"
                                            title="Delete user">
                                        <span class="material-icons-round text-[16px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</main>

<!-- ── EDIT MODAL ── -->
<div id="edit-modal">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="material-icons-round text-amber-500">edit</span>
                Edit User
            </h3>
            <button onclick="closeEdit()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                <span class="material-icons-round text-gray-500 text-[18px]">close</span>
            </button>
        </div>

        <form method="post" class="space-y-4">
            <input type="hidden" name="user_id" id="edit-id">

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Full Name</label>
                <input type="text" name="name" id="edit-name" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 transition-all">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                <input type="email" name="email" id="edit-email" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 transition-all">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Phone</label>
                <input type="text" name="phone" id="edit-phone"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 transition-all">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" name="update_user"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">
                    Save Changes
                </button>
                <button type="button" onclick="closeEdit()"
                        class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── ORDERS MODAL ── -->
<div id="orders-modal">
    <div class="orders-modal-box">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="material-icons-round text-indigo-500">receipt_long</span>
                <span id="orders-modal-title">Order History</span>
            </h3>
            <button onclick="closeOrders()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                <span class="material-icons-round text-gray-500 text-[18px]">close</span>
            </button>
        </div>

        <div id="orders-modal-body">
            <div class="flex items-center justify-center py-12 text-gray-400">
                <span class="material-icons-round animate-spin text-3xl">refresh</span>
            </div>
        </div>
    </div>
</div>

<script>
/* ── Edit modal ── */
function openEdit(u) {
    document.getElementById('edit-id').value    = u.p_id;
    document.getElementById('edit-name').value  = u.name  ?? '';
    document.getElementById('edit-email').value = u.email ?? '';
    document.getElementById('edit-phone').value = u.phone ?? '';
    document.getElementById('edit-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeEdit() {
    document.getElementById('edit-modal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});

/* ── Orders modal ── */
function viewOrders(userId, userName) {
    document.getElementById('orders-modal-title').textContent = userName + "'s Orders";
    document.getElementById('orders-modal-body').innerHTML =
        '<div class="flex items-center justify-center py-12 text-gray-400"><span class="material-icons-round animate-spin text-3xl">refresh</span></div>';
    document.getElementById('orders-modal').classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('get_user_orders.php?user_id=' + userId)
        .then(r => r.json())
        .then(orders => {
            if (!orders.length) {
                document.getElementById('orders-modal-body').innerHTML =
                    '<div class="text-center py-10 text-gray-400"><span class="material-icons-round text-4xl block mb-2">inbox</span>No orders found.</div>';
                return;
            }

            const statusColors = {
                'Pending':          'bg-yellow-100 text-yellow-700',
                'Shipped':          'bg-blue-100 text-blue-700',
                'Delivered':        'bg-green-100 text-green-700',
                'Cancelled':        'bg-red-100 text-red-700',
                'Out for Delivery': 'bg-purple-100 text-purple-700',
            };

            let totalSpent = orders.reduce((s, o) => s + parseFloat(o.total || 0), 0);

            let html = `
                <div class="flex items-center justify-between bg-indigo-50 rounded-xl px-4 py-3 mb-4">
                    <div class="text-sm text-indigo-700 font-semibold">${orders.length} order${orders.length > 1 ? 's' : ''} placed</div>
                    <div class="text-sm font-bold text-green-600">Total spent: ₹${totalSpent.toFixed(2)}</div>
                </div>
                <div class="space-y-3">
            `;

            orders.forEach(o => {
                const badge = statusColors[o.status] || 'bg-gray-100 text-gray-600';
                html += `
                    <div class="border border-gray-100 rounded-xl p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">Order #${o.id}</p>
                                <p class="text-xs text-gray-400 mt-0.5">${o.products ?? '—'}</p>
                                ${o.delivery_date ? `<p class="text-xs text-gray-400 mt-0.5">📅 Delivery: ${o.delivery_date}</p>` : ''}
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-green-600 text-base">₹${parseFloat(o.total).toFixed(2)}</p>
                                <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold ${badge}">${o.status}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            document.getElementById('orders-modal-body').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('orders-modal-body').innerHTML =
                '<div class="text-center py-10 text-red-400">Failed to load orders. Make sure get_user_orders.php exists.</div>';
        });
}

function closeOrders() {
    document.getElementById('orders-modal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('orders-modal').addEventListener('click', function(e) {
    if (e.target === this) closeOrders();
});

/* Close on Escape */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeEdit(); closeOrders(); }
});
</script>

</body>
</html>