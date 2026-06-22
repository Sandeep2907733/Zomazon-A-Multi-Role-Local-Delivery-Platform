<?php
include "config/db.php";

$city     = htmlspecialchars($_GET['city']     ?? "");
$area     = htmlspecialchars($_GET['area']     ?? "");
$category = htmlspecialchars($_GET['category'] ?? "");

// Build query dynamically based on active filters
$conditions = [];
$params     = [];
$types      = "";

if ($city)     { $conditions[] = "city=?";     $params[] = $city;     $types .= "s"; }
if ($area)     { $conditions[] = "area=?";     $params[] = $area;     $types .= "s"; }
if ($category) { $conditions[] = "category=?"; $params[] = $category; $types .= "s"; }

$sql  = "SELECT * FROM local_shops" . ($conditions ? " WHERE " . implode(" AND ", $conditions) : "");
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$shops = $stmt->get_result();

// Fetch dropdown options
$cities_result     = $conn->query("SELECT DISTINCT city     FROM local_shops ORDER BY city");
$categories_result = $conn->query("SELECT DISTINCT category FROM local_shops WHERE category IS NOT NULL AND category != '' ORDER BY category");

$areas_result = null;
if ($city) {
    $stmt2 = $conn->prepare("SELECT DISTINCT area FROM local_shops WHERE city=? ORDER BY area");
    $stmt2->bind_param("s", $city);
    $stmt2->execute();
    $areas_result = $stmt2->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Local Goods — Discover Local Shops</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:          #0b0f1a;
  --surface:     #111827;
  --surface2:    #1a2235;
  --border:      rgba(255,255,255,0.07);
  --green:       #22c55e;
  --green-dim:   rgba(34,197,94,0.12);
  --text:        #f1f5f9;
  --muted:       #64748b;
  --card-shadow: 0 8px 32px rgba(0,0,0,0.4);
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}
body::before {
  content: '';
  position: fixed; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none; z-index: 0;
}
.glow-blob {
  position: fixed; width: 600px; height: 600px; border-radius: 50%;
  background: radial-gradient(circle, rgba(34,197,94,0.08) 0%, transparent 70%);
  top: -150px; right: -150px; pointer-events: none; z-index: 0;
}

/* ── Navbar ── */
.navbar {
  position: sticky; top: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 40px; height: 68px;
  background: rgba(11,15,26,0.88); backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  gap: 16px;
}
.logo {
  font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800;
  color: var(--text); letter-spacing: -0.5px;
  display: flex; align-items: center; gap: 8px; white-space: nowrap;
}
.logo span { color: var(--green); }
.logo-icon {
  width: 32px; height: 32px; background: var(--green-dim);
  border: 1px solid rgba(34,197,94,0.3); border-radius: 8px;
  display: grid; place-items: center; font-size: 16px;
}
.filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.select-wrap { position: relative; }
.select-wrap .material-icons-round {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  font-size: 18px; color: var(--muted); pointer-events: none;
}
.filters select {
  appearance: none; background: var(--surface2); border: 1px solid var(--border);
  color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 14px;
  padding: 9px 36px 9px 14px; border-radius: 10px; cursor: pointer;
  transition: border-color 0.2s, background 0.2s; outline: none; min-width: 140px;
}
.filters select:hover, .filters select:focus {
  border-color: rgba(34,197,94,0.4); background: var(--surface);
}
.filters select option { background: var(--surface); }

/* ── Hero ── */
.hero {
  position: relative; z-index: 1;
  padding: 48px 40px 28px; max-width: 1400px; margin: 0 auto;
}
.hero-tag {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--green-dim); border: 1px solid rgba(34,197,94,0.25);
  color: var(--green); font-size: 12px; font-weight: 500;
  padding: 4px 12px; border-radius: 100px; margin-bottom: 16px;
  letter-spacing: 0.5px; text-transform: uppercase;
}
.hero h1 {
  font-family: 'Syne', sans-serif; font-size: clamp(26px, 4vw, 46px);
  font-weight: 800; line-height: 1.1; letter-spacing: -1px; margin-bottom: 8px;
}
.hero h1 em { font-style: normal; color: var(--green); }
.hero-sub { color: var(--muted); font-size: 15px; font-weight: 300; }

/* ── Container & Grid ── */
.container {
  position: relative; z-index: 1;
  max-width: 1400px; margin: 0 auto; padding: 0 40px 60px;
}
.stats-bar {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border);
}
.stats-label { font-size: 13px; color: var(--muted); }
.stats-count {
  font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700;
  color: var(--green); background: var(--green-dim); padding: 3px 10px; border-radius: 100px;
}
.grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;
}

/* ── Card ── */
.card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 16px; overflow: hidden;
  transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
  animation: fadeUp 0.5s ease both;
}
.card:hover {
  transform: translateY(-6px);
  box-shadow: var(--card-shadow), 0 0 0 1px rgba(34,197,94,0.15);
  border-color: rgba(34,197,94,0.2);
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
.card:nth-child(1){ animation-delay:.05s } .card:nth-child(2){ animation-delay:.10s }
.card:nth-child(3){ animation-delay:.15s } .card:nth-child(4){ animation-delay:.20s }
.card:nth-child(5){ animation-delay:.25s } .card:nth-child(6){ animation-delay:.30s }

/* ── Card image ── */
.card-img {
  position: relative; height: 180px; overflow: hidden; background: var(--surface2);
}
.card-img img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform 0.5s ease; display: block;
}
.card:hover .card-img img { transform: scale(1.06); }

/*
 * .img-fallback is hidden by default (display:none).
 * The <img> onerror sets this sibling to display:flex
 * so we get a centred icon + text instead of a broken image.
 * It must be position:absolute so it fills the same .card-img area.
 */
.img-fallback {
  display: none;
  position: absolute; inset: 0;
  flex-direction: column; align-items: center; justify-content: center; gap: 8px;
  background: var(--surface2); color: var(--muted); font-size: 13px;
}
.img-fallback .material-icons-round { font-size: 38px; color: rgba(255,255,255,0.1); }

.card-badge {
  position: absolute; top: 12px; left: 12px;
  background: rgba(11,15,26,0.82); backdrop-filter: blur(8px);
  border: 1px solid var(--border); border-radius: 6px;
  font-size: 11px; font-weight: 500; color: var(--muted);
  padding: 3px 8px; letter-spacing: 0.4px; text-transform: uppercase;
}
.cat-tag {
  position: absolute; top: 12px; right: 12px;
  background: rgba(34,197,94,0.15); backdrop-filter: blur(8px);
  border: 1px solid rgba(34,197,94,0.3); border-radius: 6px;
  font-size: 11px; font-weight: 600; color: var(--green);
  padding: 3px 8px; text-transform: uppercase; letter-spacing: 0.4px;
}

.card-body { padding: 18px 20px 20px; }
.shop-name {
  font-family: 'Syne', sans-serif; font-size: 17px; font-weight: 700;
  margin-bottom: 12px; color: var(--text); line-height: 1.2;
}
.meta { display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
.meta-row { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 13px; }
.meta-row .material-icons-round { font-size: 16px; color: var(--green); flex-shrink: 0; }

.btn {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  width: 100%; padding: 11px; background: var(--green); color: #0b0f1a;
  font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600;
  border-radius: 10px; text-decoration: none;
  transition: background 0.2s, transform 0.15s; letter-spacing: 0.2px;
}
.btn:hover { background: #16a34a; transform: scale(1.02); }
.btn .material-icons-round { font-size: 18px; }

.empty {
  grid-column: 1/-1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 80px 20px; text-align: center; color: var(--muted);
}
.empty .material-icons-round { font-size: 56px; margin-bottom: 16px; color: rgba(255,255,255,0.07); }
.empty h3 { font-family:'Syne',sans-serif; font-size:20px; color:var(--text); margin-bottom:6px; }

@media(max-width:900px){
  .navbar { padding: 12px 20px; height: auto; flex-wrap: wrap; }
  .hero, .container { padding-left: 20px; padding-right: 20px; }
  .filters select { min-width: 120px; }
}
</style>
</head>
<body>
<div class="glow-blob"></div>

<!-- ── Navbar ── -->
<nav class="navbar">
  <div class="logo">
    <div class="logo-icon">🌿</div>
    Local<span>Goods</span>
  </div>

  <form method="GET" class="filters">

    <!-- City -->
    <div class="select-wrap">
      <select name="city" onchange="this.form.submit()">
        <option value="">All Cities</option>
        <?php while ($c = $cities_result->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($c['city']) ?>"
            <?= $city === $c['city'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['city']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <span class="material-icons-round">expand_more</span>
    </div>

    <!-- Area (only shows options when a city is selected) -->
    <div class="select-wrap">
      <select name="area" onchange="this.form.submit()">
        <option value="">All Areas</option>
        <?php if ($areas_result): while ($a = $areas_result->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($a['area']) ?>"
            <?= $area === $a['area'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['area']) ?>
          </option>
        <?php endwhile; endif; ?>
      </select>
      <span class="material-icons-round">expand_more</span>
    </div>

    <!-- ── Category ── pulled from the `category` column in local_shops -->
    <div class="select-wrap">
      <select name="category" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php while ($cat = $categories_result->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($cat['category']) ?>"
            <?= $category === $cat['category'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['category']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <span class="material-icons-round">expand_more</span>
    </div>

  </form>
</nav>

<!-- ── Hero ── -->
<section class="hero">
  <div class="hero-tag">
    <span class="material-icons-round" style="font-size:13px">storefront</span>
    Discover Local
  </div>
  <h1>
    <?php if ($category && ($city || $area)): ?>
      <em><?= htmlspecialchars($category) ?></em> in <?= htmlspecialchars($area ?: $city) ?>
    <?php elseif ($category): ?>
      <em><?= htmlspecialchars($category) ?></em> Shops
    <?php elseif ($city && $area): ?>
      Shops in <em><?= htmlspecialchars($area) ?></em>
    <?php elseif ($city): ?>
      Shops in <em><?= htmlspecialchars($city) ?></em>
    <?php else: ?>
      All <em>Local Shops</em>
    <?php endif; ?>
  </h1>
  <p class="hero-sub">Support your neighbourhood — shop local, shop fresh.</p>
</section>

<!-- ── Shop Grid ── -->
<div class="container">
  <?php $count = $shops->num_rows; ?>
  <div class="stats-bar">
    <span class="stats-label">Showing results</span>
    <span class="stats-count"><?= $count ?> shop<?= $count !== 1 ? 's' : '' ?></span>
  </div>

  <div class="grid">
    <?php if ($count > 0): while ($row = $shops->fetch_assoc()):
      /*
       * ── IMAGE PATH ──────────────────────────────────────────────
       * The seller panel saves shop images to  Seller/uploads/
       * So the src becomes:  Seller/uploads/<filename from DB>
       *
       * If the image column is empty OR the file is missing/broken,
       * onerror fires: it hides the <img> and shows .img-fallback
       * ─────────────────────────────────────────────────────────────
       */
      $imgFile = htmlspecialchars($row['image'] ?? '');
      $imgSrc  = $imgFile ? "Seller/uploads/" . $imgFile : "";
    ?>
      <div class="card">
        <div class="card-img">

          <?php if ($imgSrc): ?>
            <img
              src="<?= $imgSrc ?>"
              alt="<?= htmlspecialchars($row['shop_name']) ?>"
              onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
            >
          <?php endif; ?>

          <!-- Shown when image is empty OR fails to load -->
          <div class="img-fallback" style="<?= $imgSrc ? '' : 'display:flex;' ?>">
            <span class="material-icons-round">storefront</span>
            No Image
          </div>

          <div class="card-badge"><?= htmlspecialchars($row['area']) ?></div>

          <?php if (!empty($row['category'])): ?>
            <div class="cat-tag"><?= htmlspecialchars($row['category']) ?></div>
          <?php endif; ?>

        </div>

        <div class="card-body">
          <div class="shop-name"><?= htmlspecialchars($row['shop_name']) ?></div>
          <div class="meta">
            <div class="meta-row">
              <span class="material-icons-round">person</span>
              <?= htmlspecialchars($row['owner_name']) ?>
            </div>
            <div class="meta-row">
              <span class="material-icons-round">location_on</span>
              <?= htmlspecialchars($row['area']) ?>, <?= htmlspecialchars($row['city']) ?>
            </div>
            <div class="meta-row">
              <span class="material-icons-round">call</span>
              <?= htmlspecialchars($row['phone']) ?>
            </div>
          </div>
          <a href="shop.php?id=<?= intval($row['id']) ?>" class="btn">
            <span class="material-icons-round">shopping_bag</span>
            View Shop
          </a>
        </div>
      </div>
    <?php endwhile; else: ?>
      <div class="empty">
        <span class="material-icons-round">search_off</span>
        <h3>No shops found</h3>
        <p>Try a different city, area, or category.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>