<?php
include 'config/db.php';

$id = $_GET['id'];

mysqli_query($conn, "UPDATE orders SET status='Cancelled' WHERE id='$id'");

header("Location: orders.php");
exit();
?>