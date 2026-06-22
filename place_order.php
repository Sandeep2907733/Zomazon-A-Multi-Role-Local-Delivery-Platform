<?php
include 'config/db.php';

// 🚨 LOGIN CHECK
if(!isset($_SESSION['user_id'])){
    header("Location: registration/Login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
$phone = mysqli_real_escape_string($conn, $_POST['phone']);
$address = mysqli_real_escape_string($conn, $_POST['address']);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

// Prepare products & total
$products = [];
$total = 0;

foreach ($_SESSION['cart'] as $item) {
    $products[] = $item['name'] . " x " . $item['qty'];
    $total += $item['price'] * $item['qty'];
}

$products = implode(", ", $products);

// Delivery date
$delivery_date = date('Y-m-d', strtotime('+3 days'));

// Insert order
$query = "
INSERT INTO orders 
(user_id, full_name, phone, address, payment_method, products, total, delivery_date, status)
VALUES 
('$user_id', '$full_name', '$phone', '$address', '$payment_method', '$products', '$total', '$delivery_date', 'Pending')
";

mysqli_query($conn, $query);

// Clear cart
unset($_SESSION['cart']);

// Redirect
header("Location: Success.php");
exit();
?>