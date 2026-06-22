<?php
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode([]);
    exit();
}

$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode([]);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) $orders[] = $row;

header('Content-Type: application/json');
echo json_encode($orders);