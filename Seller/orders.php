<?php
include "../config/db.php";

if (!isset($_SESSION['shop_id'])) { header("Location: index.php"); exit(); }

$shop_id = intval($_SESSION['shop_id']);

// Handle status update on same page
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['order_id'])) {
    $order_id  = intval($_POST['order_id']);
    $newStatus = $_POST['status'];
    $allowed   = ['Pending','Shipped','Out for Delivery','Delivered','Cancelled'];

    if (in_array($newStatus, $allowed)) {
        $upd = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE id=? AND shop_id=?");
        mysqli_stmt_bind_param($upd, "sii", $newStatus, $order_id, $shop_id);
        mysqli_stmt_execute($upd);
    }
    header("Location: orders.php");
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE shop_id=? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "i", $shop_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total  = mysqli_num_rows($result);

// Count by status
$counts = ['Pending'=>0,'Shipped'=>0,'Out for Delivery'=>0,'Delivered'=>0,'Cancelled'=>0];
$rows   = [];
while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
    if (isset($counts[$r['status']])) $counts[$r['status']]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{display:['Syne','sans-serif']}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
  body { background:#0f1117; color:#f0f2f8; font-family:'DM Sans',sans-serif; }
  .dot { animation:pulse 2s infinite; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
  select { outline:none; }
  select option { background:#22263a; color:#f0f2f8; }
</style>
</head>
<body class="min-h-screen">

<!-- NAVBAR -->
<nav class="sticky top-0 z-50 flex items-center justify-between px-8 h-16 bg-[#1a1d27] border-b border-white/[0.07]">
  <div class="flex items-center gap-3 font-display font-extrabold text-lg">
    <span class="dot w-2.5 h-2.5 rounded-full bg-green-500 shadow-[0_0_8px_#22c55e] inline-block"></span>
    Seller Panel
  </div>
  <a href="dashboard.php" class="flex items-center gap-1.5 text-[#7c829a] text-sm px-3 py-2 rounded-lg bg-[#22263a] border border-white/[0.07] hover:text-white transition">
    <span class="material-icons-round text-base">arrow_back</span> Dashboard
  </a>
</nav>

<div class="max-w-4xl mx-auto px-6 py-9">

  <!-- HEADER -->
  <div class="mb-7">
    <h1 class="font-display font-extrabold text-2xl tracking-tight">🛒 Shop Orders</h1>
    <p class="text-[#7c829a] text-sm mt-1">View and update the status of your customer orders.</p>
  </div>

  <!-- STAT CARDS -->
  <div class="grid grid-cols-5 gap-3 mb-8">
    <?php
      $statStyles = [
        'Pending'         => ['bg-amber-500/10',  'text-amber-400',  'schedule'],
        'Shipped'         => ['bg-blue-500/10',   'text-blue-400',   'local_shipping'],
        'Out for Delivery'=> ['bg-purple-500/10', 'text-purple-400', 'delivery_dining'],
        'Delivered'       => ['bg-green-500/10',  'text-green-400',  'check_circle'],
        'Cancelled'       => ['bg-red-500/10',    'text-red-400',    'cancel'],
      ];
      foreach ($counts as $label => $count):
        [$bg, $text, $icon] = $statStyles[$label];
    ?>
    <div class="bg-[#1a1d27] border border-white/[0.07] rounded-2xl p-4 text-center">
      <div class="w-9 h-9 <?= $bg ?> <?= $text ?> rounded-xl flex items-center justify-center mx-auto mb-2">
        <span class="material-icons-round text-lg"><?= $icon ?></span>
      </div>
      <p class="font-display font-extrabold text-xl"><?= $count ?></p>
      <p class="text-[#7c829a] text-[10px] uppercase tracking-wide mt-0.5"><?= $label ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- FILTER TABS -->
  <div class="flex gap-2 mb-6 flex-wrap">
    <button onclick="filterOrders('all')" id="tab-all"
      class="tab-btn active-tab text-xs font-semibold px-4 py-2 rounded-lg border transition">
      All (<?= $total ?>)
    </button>
    <?php foreach ($counts as $label => $count): ?>
    <button onclick="filterOrders('<?= strtolower(str_replace(' ','-',$label)) ?>')"
            id="tab-<?= strtolower(str_replace(' ','-',$label)) ?>"
      class="tab-btn text-xs font-semibold px-4 py-2 rounded-lg border border-white/[0.07] bg-[#1a1d27] text-[#7c829a] hover:text-white transition">
      <?= $label ?> (<?= $count ?>)
    </button>
    <?php endforeach; ?>
  </div>

  <style>
    .active-tab { background:#22c55e; color:white; border-color:#22c55e; }
    .tab-btn:not(.active-tab) { background:#1a1d27; color:#7c829a; border-color:rgba(255,255,255,0.07); }
  </style>

  <!-- ORDERS LIST -->
  <?php if ($total === 0): ?>
  <div class="text-center py-24">
    <span class="material-icons-round text-6xl text-[#7c829a] opacity-30">receipt_long</span>
    <h3 class="font-display font-extrabold text-xl mt-4 mb-2">No orders yet</h3>
    <p class="text-[#7c829a] text-sm">Orders from customers will appear here.</p>
  </div>

  <?php else: foreach ($rows as $row):
    $status = $row['status'];
    [$statusBg, $statusText] = match($status) {
      'Pending'          => ['bg-amber-500/15',  'text-amber-400'],
      'Shipped'          => ['bg-blue-500/15',   'text-blue-400'],
      'Out for Delivery' => ['bg-purple-500/15', 'text-purple-400'],
      'Delivered'        => ['bg-green-500/15',  'text-green-400'],
      'Cancelled'        => ['bg-red-500/15',    'text-red-400'],
      default            => ['bg-white/10',      'text-white'],
    };
    $slug = strtolower(str_replace(' ', '-', $status));
  ?>

  <div class="order-card bg-[#1a1d27] border border-white/[0.07] rounded-2xl p-5 mb-4 transition hover:border-white/[0.14]"
       data-status="<?= $slug ?>">

    <div class="flex items-start justify-between gap-4 flex-wrap">

      <!-- LEFT -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-[#7c829a] text-xs">#<?= $row['id'] ?></span>
          <span class="<?= $statusBg ?> <?= $statusText ?> text-[11px] font-bold px-3 py-1 rounded-full">
            <?= htmlspecialchars($status) ?>
          </span>
        </div>

        <div class="flex items-start gap-2 mb-2">
          <span class="material-icons-round text-[#7c829a] text-base mt-0.5">shopping_bag</span>
          <p class="text-sm text-[#f0f2f8]"><?= htmlspecialchars($row['products']) ?></p>
        </div>

        <div class="flex items-center gap-2">
          <span class="material-icons-round text-[#7c829a] text-base">currency_rupee</span>
          <p class="text-green-400 font-display font-extrabold text-lg">
            ₹<?= number_format($row['total'], 2) ?>
          </p>
        </div>
      </div>

      <!-- RIGHT — Update Status -->
      <form method="POST" class="flex items-center gap-2 flex-shrink-0">
        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
        <select name="status"
          class="bg-[#22263a] border border-white/[0.07] text-sm text-white px-3 py-2 rounded-xl focus:border-green-500 transition">
          <?php foreach (['Pending','Shipped','Out for Delivery','Delivered','Cancelled'] as $opt): ?>
          <option <?= $status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"
          class="flex items-center gap-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">
          <span class="material-icons-round text-sm">save</span> Update
        </button>
      </form>

    </div>
  </div>

  <?php endforeach; endif; ?>

</div>

<script>
  function filterOrders(status) {
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
    document.getElementById('tab-' + status).classList.add('active-tab');

    // Show/hide cards
    document.querySelectorAll('.order-card').forEach(card => {
      card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
    });
  }
</script>

</body>
</html>