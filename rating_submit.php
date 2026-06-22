<?php
include 'config/db.php';

/* Only accept POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$product_id = (int)($_POST['product_id'] ?? 0);
$stars      = (int)($_POST['stars']      ?? 0);
$review     = trim($_POST['review']      ?? '');
$user_id    = $_SESSION['user_id']       ?? null;

/* ── Guards ── */
if (!$product_id || $stars < 1 || $stars > 5) {
    $_SESSION['rating_msg'] = "Invalid rating. Please try again.";
    header("Location: product.php?id=$product_id");
    exit();
}

if (!$user_id) {
    $_SESSION['rating_msg'] = "Please sign in to leave a review.";
    header("Location: product.php?id=$product_id");
    exit();
}

/* ── Check already rated ── */
$stmt = $conn->prepare("SELECT id FROM ratings WHERE user_id = ? AND product_id = ? LIMIT 1");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $_SESSION['rating_msg'] = "You've already reviewed this product.";
    header("Location: product.php?id=$product_id");
    exit();
}

/* ── Check purchased ── */
$stmt2 = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND product_id = ? LIMIT 1");
if ($stmt2) {
    $stmt2->bind_param("ii", $user_id, $product_id);
    $stmt2->execute();
    if (!$stmt2->get_result()->fetch_assoc()) {
        $_SESSION['rating_msg'] = "You can only review products you've purchased.";
        header("Location: product.php?id=$product_id");
        exit();
    }
}

/* ── Get user's name from session (adjust key to match your login system) ── */
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'Anonymous';
$user_name = mb_substr(strip_tags($user_name), 0, 100);

/* ── Sanitize review text ── */
$review = mb_substr(strip_tags($review), 0, 500);

/* ── Insert rating ── */
$stmt3 = $conn->prepare("INSERT INTO ratings (product_id, user_id, user_name, stars, review, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt3->bind_param("iisis", $product_id, $user_id, $user_name, $stars, $review);

if ($stmt3->execute()) {
    $_SESSION['rating_msg'] = "Thanks for your review! ⭐";
} else {
    $_SESSION['rating_msg'] = "Something went wrong. Please try again.";
}

header("Location: product.php?id=$product_id");
exit();