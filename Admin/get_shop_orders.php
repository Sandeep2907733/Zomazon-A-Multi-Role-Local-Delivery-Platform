<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

$shop_id = isset($_GET['shop_id']) ? (int) $_GET['shop_id'] : 0;

if (!$shop_id) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE shop_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) $orders[] = $row;

header('Content-Type: application/json');
echo json_encode($orders);