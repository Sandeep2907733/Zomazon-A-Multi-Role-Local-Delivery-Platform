<?php
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

/* ── DELETE SHOP ── */
if (isset($_POST['delete_shop'])) {
    $sid = (int) $_POST['shop_id'];

    // Delete products first (foreign key constraint)
    $stmt = $conn->prepare("DELETE FROM products WHERE shop_id = ?");
    $stmt->bind_param("i", $sid);
    $stmt->execute();

    // Now safe to delete the shop
    $stmt = $conn->prepare("DELETE FROM local_shops WHERE id = ?");
    $stmt->bind_param("i", $sid);
    $stmt->execute();

    header("Location: localshops.php?msg=deleted");
    exit();
}

/* ── UPDATE SHOP ── */
if (isset($_POST['update_shop'])) {
    $sid      = (int)   $_POST['shop_id'];
    $name     = trim($_POST['shop_name']);
    $owner    = trim($_POST['owner_name']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['seller_email']);
    $address  = trim($_POST['address']);
    $area     = trim($_POST['area']);
    $city     = trim($_POST['city']);
    $category = trim($_POST['category']);

    // No 'status' column in local_shops — removed from UPDATE
    $stmt = $conn->prepare("UPDATE local_shops SET shop_name=?, owner_name=?, phone=?, seller_email=?, address=?, area=?, city=?, category=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $name, $owner, $phone, $email, $address, $area, $city, $category, $sid);
    $stmt->execute();
    header("Location: localshops.php?msg=updated");
    exit();
}

/* ── FLASH ── */
$msg = $msg_type = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') { $msg = "Shop deleted successfully.";  $msg_type = "red"; }
    if ($_GET['msg'] === 'updated') { $msg = "Shop updated successfully.";  $msg_type = "green"; }
}

/* ── SEARCH ── */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

/* ── FETCH SHOPS ── */
$where = '';
if ($search !== '') {
    $safe  = $conn->real_escape_string($search);
    $where = "WHERE s.shop_name  LIKE '%$safe%'
               OR s.owner_name   LIKE '%$safe%'
               OR s.category     LIKE '%$safe%'
               OR s.phone        LIKE '%$safe%'";
}

$shops_result = $conn->query("
    SELECT s.*,
           COUNT(DISTINCT p.id) AS total_products
    FROM local_shops s
    LEFT JOIN products p ON p.shop_id = s.id
    $where
    GROUP BY s.id
    ORDER BY s.id DESC
");

$shops = [];
while ($row = $shops_result->fetch_assoc()) $shops[] = $row;

/* ── SUMMARY STATS ── */
$total_shops  = count($shops);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Shops – Zomazon Admin</title>

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
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(0,0,0,.12); }

        .modal-wrap {
            display: none; position: fixed; inset: 0; z-index: 60;
            background: rgba(0,0,0,.45); backdrop-filter: blur(5px);
            align-items: center; justify-content: center; padding: 16px;
        }
        .modal-wrap.open { display: flex; }

        .modal-box {
            background: #fff; border-radius: 20px; padding: 28px;
            width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 24px 60px rgba(0,0,0,.18);
            animation: popIn .25s cubic-bezier(.3,1.1,.5,1);
        }

        @keyframes popIn {
            from { opacity:0; transform:scale(.94) translateY(12px); }
            to   { opacity:1; transform:scale(1)   translateY(0); }
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

        tbody tr { transition: background .12s; }
        tbody tr:hover { background: #f8fafc; }
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

    <div class="mb-7">
        <h2 class="text-2xl font-bold text-gray-800">Registered Shops</h2>
        <p class="text-sm text-gray-400 mt-0.5">Manage local seller shops and their products</p>
    </div>

    <!-- Flash -->
    <?php if ($msg): ?>
    <div class="mb-5 flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium
        <?php echo $msg_type === 'red' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'; ?>">
        <span class="material-icons-round text-[18px]"><?php echo $msg_type === 'red' ? 'delete' : 'check_circle'; ?></span>
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <!-- STAT CARD -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="stat-card rounded-2xl p-5 text-white shadow-md"
             style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-80">Total Shops</p>
                <span class="material-icons-round text-white/60 text-[24px]">storefront</span>
            </div>
            <p class="text-3xl font-bold"><?php echo $total_shops; ?></p>
        </div>
    </div>

    <!-- SHOP LIST -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <span class="material-icons-round text-indigo-500 text-[20px]">store</span>
                All Shops
                <span class="bg-indigo-50 text-indigo-600 text-xs font-bold px-2 py-0.5 rounded-full ml-1"><?php echo $total_shops; ?></span>
            </h3>
            <form method="get" class="flex items-center gap-2">
                <div class="relative">
                    <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[18px]">search</span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search shop, owner, category…"
                           class="pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm w-64 transition-all">
                </div>
                <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors">Search</button>
                <?php if ($search): ?>
                <a href="localshops.php" class="text-sm text-gray-400 hover:text-gray-600 font-medium">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($shops)): ?>
        <div class="text-center py-20 text-gray-400">
            <span class="material-icons-round text-5xl mb-3 block">store_mall_directory</span>
            <p class="font-semibold text-gray-600 mb-1">No shops found</p>
            <p class="text-sm">Try a different search term.</p>
        </div>

        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <th class="pb-3 font-semibold pr-4">#</th>
                        <th class="pb-3 font-semibold pr-4">Shop</th>
                        <th class="pb-3 font-semibold pr-4">Category</th>
                        <th class="pb-3 font-semibold pr-4">Location</th>
                        <th class="pb-3 font-semibold pr-4">Contact</th>
                        <th class="pb-3 font-semibold pr-4 text-center">Products</th>
                        <th class="pb-3 font-semibold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($shops as $s): ?>
                    <tr class="hover:bg-gray-50 transition-colors">

                        <td class="py-3.5 pr-4 text-gray-400 font-medium">#<?php echo $s['id']; ?></td>

                        <!-- Shop -->
                        <td class="py-3.5 pr-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl overflow-hidden bg-indigo-50 flex items-center justify-center shrink-0 border border-gray-100">
                                    <?php if (!empty($s['image'])): ?>
                                        <img src="../images/<?php echo htmlspecialchars($s['image']); ?>"
                                             class="w-full h-full object-cover"
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                        <span class="text-indigo-600 font-bold text-sm hidden">
                                            <?php echo strtoupper(substr($s['shop_name'] ?? 'S', 0, 1)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-indigo-600 font-bold text-sm">
                                            <?php echo strtoupper(substr($s['shop_name'] ?? 'S', 0, 1)); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($s['shop_name']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo htmlspecialchars($s['owner_name'] ?? '—'); ?></p>
                                </div>
                            </div>
                        </td>

                        <!-- Category -->
                        <td class="py-3.5 pr-4">
                            <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2.5 py-1 rounded-full">
                                <?php echo htmlspecialchars($s['category'] ?? '—'); ?>
                            </span>
                        </td>

                        <!-- Location -->
                        <td class="py-3.5 pr-4">
                            <p class="text-gray-700 text-xs font-medium"><?php echo htmlspecialchars($s['area'] ?? '—'); ?></p>
                            <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($s['city'] ?? ''); ?></p>
                        </td>

                        <!-- Contact -->
                        <td class="py-3.5 pr-4">
                            <p class="text-gray-700 text-xs font-medium"><?php echo htmlspecialchars($s['phone'] ?? '—'); ?></p>
                            <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($s['seller_email'] ?? ''); ?></p>
                        </td>

                        <!-- Products -->
                        <td class="py-3.5 pr-4 text-center">
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">
                                <span class="material-icons-round text-[12px]">inventory_2</span>
                                <?php echo $s['total_products']; ?>
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="py-3.5">
                            <div class="flex items-center justify-center gap-2">
                                <!-- View -->
                                <button onclick="viewDetails(<?php echo htmlspecialchars(json_encode($s), ENT_QUOTES); ?>)"
                                        class="w-8 h-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 flex items-center justify-center transition-colors"
                                        title="View details">
                                    <span class="material-icons-round text-[16px]">visibility</span>
                                </button>
                                <!-- Edit -->
                                <button onclick="openEdit(<?php echo htmlspecialchars(json_encode($s), ENT_QUOTES); ?>)"
                                        class="w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 flex items-center justify-center transition-colors"
                                        title="Edit shop">
                                    <span class="material-icons-round text-[16px]">edit</span>
                                </button>
                                <!-- Delete -->
                                <form method="post" onsubmit="return confirm('Delete this shop and ALL its products? This cannot be undone.')">
                                    <input type="hidden" name="shop_id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" name="delete_shop"
                                            class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition-colors"
                                            title="Delete shop">
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

<!-- MODAL: VIEW DETAILS -->
<div id="details-modal" class="modal-wrap" onclick="if(event.target===this)closeDetails()">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="material-icons-round text-indigo-500">storefront</span>
                Shop Details
            </h3>
            <button onclick="closeDetails()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                <span class="material-icons-round text-gray-500 text-[18px]">close</span>
            </button>
        </div>
        <div id="details-body"></div>
    </div>
</div>

<!-- MODAL: EDIT SHOP -->
<div id="edit-modal" class="modal-wrap" onclick="if(event.target===this)closeEdit()">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="material-icons-round text-amber-500">edit</span>
                Edit Shop
            </h3>
            <button onclick="closeEdit()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                <span class="material-icons-round text-gray-500 text-[18px]">close</span>
            </button>
        </div>

        <form method="post" class="space-y-4">
            <input type="hidden" name="shop_id" id="edit-id">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Shop Name</label>
                    <input type="text" name="shop_name" id="edit-shop-name" required
                           class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Owner Name</label>
                    <input type="text" name="owner_name" id="edit-owner"
                           class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Phone</label>
                    <input type="text" name="phone" id="edit-phone"
                           class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="seller_email" id="edit-email"
                           class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Area</label>
                    <input type="text" name="area" id="edit-area"
                           class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">City</label>
                    <input type="text" name="city" id="edit-city"
                           class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Address</label>
                <textarea name="address" id="edit-address" rows="2"
                          class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 transition-all resize-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
                <input type="text" name="category" id="edit-category"
                       class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 transition-all">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" name="update_shop"
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

<script>
function viewDetails(s) {
    document.getElementById('details-body').innerHTML = `
        <div class="flex items-center gap-4 p-4 bg-indigo-50 rounded-2xl mb-5">
            <div class="w-16 h-16 rounded-xl bg-white border border-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600 shrink-0">
                ${(s.shop_name || 'S').charAt(0).toUpperCase()}
            </div>
            <div>
                <p class="font-bold text-gray-800 text-lg leading-tight">${s.shop_name ?? '—'}</p>
                <p class="text-sm text-gray-500">Owner: ${s.owner_name ?? '—'}</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-amber-50 rounded-xl p-3 text-center">
                <p class="text-xs text-amber-600 font-semibold mb-0.5">Products</p>
                <p class="text-lg font-bold text-amber-700">${s.total_products ?? 0}</p>
            </div>
            <div class="bg-indigo-50 rounded-xl p-3 text-center">
                <p class="text-xs text-indigo-600 font-semibold mb-0.5">Category</p>
                <p class="text-sm font-bold text-indigo-700">${s.category ?? '—'}</p>
            </div>
        </div>
        <div class="space-y-3 text-sm">
            ${row('phone',    'Phone',    s.phone)}
            ${row('email',    'Email',    s.seller_email)}
            ${row('location_on', 'Area / City', (s.area ?? '') + (s.city ? ', ' + s.city : ''))}
            ${row('place',    'Address',  s.address)}
            ${row('schedule', 'Joined',   s.created_at ? s.created_at.split(' ')[0] : '—')}
        </div>
    `;
    openModal('details-modal');
}

function row(icon, label, value) {
    return `
        <div class="flex items-start gap-3 bg-gray-50 rounded-xl px-4 py-3">
            <span class="material-icons-round text-gray-400 text-[18px] mt-0.5">${icon}</span>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">${label}</p>
                <p class="text-gray-800 font-medium mt-0.5">${value || '—'}</p>
            </div>
        </div>`;
}

function closeDetails() { closeModal('details-modal'); }

function openEdit(s) {
    document.getElementById('edit-id').value         = s.id;
    document.getElementById('edit-shop-name').value  = s.shop_name    ?? '';
    document.getElementById('edit-owner').value      = s.owner_name   ?? '';
    document.getElementById('edit-phone').value      = s.phone        ?? '';
    document.getElementById('edit-email').value      = s.seller_email ?? '';
    document.getElementById('edit-area').value       = s.area         ?? '';
    document.getElementById('edit-city').value       = s.city         ?? '';
    document.getElementById('edit-address').value    = s.address      ?? '';
    document.getElementById('edit-category').value   = s.category     ?? '';
    openModal('edit-modal');
}
function closeEdit() { closeModal('edit-modal'); }

function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeDetails(); closeEdit(); }
});
</script>

</body>
</html>