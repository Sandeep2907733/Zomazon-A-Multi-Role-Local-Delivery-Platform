<?php

include "../config/db.php";

if(isset($_POST['update_stock'])){

$id = $_POST['product_id'];
$new_stock = $_POST['stock'];

mysqli_query($conn,"UPDATE products SET stock='$new_stock' WHERE id='$id'");

}

if(!isset($_SESSION['admin'])){
header("Location: login.php");
exit();
}

$result = mysqli_query($conn,"SELECT * FROM products ORDER BY id DESC");


if(isset($_POST['delete_id'])){

$id = intval($_POST['delete_id']);

mysqli_query($conn,"DELETE FROM products WHERE id=$id");

header("Location: products.php");
exit();

}
?>

<!DOCTYPE html>
<html>
<head>

<title>Zomazon Products</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
background:#f5f7fb;
}

/* Sidebar */

.sidebar{
width:240px;
height:100vh;
background:#111827;
color:white;
position:fixed;
padding-top:30px;
}

.sidebar h2{
text-align:center;
margin-bottom:40px;
}

.sidebar a{
display:block;
padding:14px 25px;
color:#cbd5e1;
text-decoration:none;
transition:0.3s;
}

.sidebar a:hover{
background:#1f2937;
color:white;
}

/* Main */

.main{
margin-left:240px;
padding:35px;
}

.main h1{
margin-bottom:25px;
}

/* Button */

.add-btn{
background:#22c55e;
color:white;
padding:10px 18px;
border-radius:8px;
text-decoration:none;
margin-bottom:20px;
display:inline-block;
}

/* Table */

.table-box{
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 5px 15px rgba(0,0,0,0.08);
}

table{
width:100%;
border-collapse:collapse;
}

table th{
text-align:left;
padding:14px;
background:#f9fafb;
border-bottom:2px solid #eee;
}

table td{
padding:14px;
border-bottom:1px solid #eee;
}

.action-btn{
padding:6px 12px;
border-radius:6px;
text-decoration:none;
font-size:14px;
color:white;
}

.edit{
background:#3b82f6;
}

.delete{
background:#ef4444;
}

</style>

</head>

<body>

<div class="sidebar">

<h2>Zomazon</h2>

<a href="dashboard.php">📊 Dashboard</a>
<a href="products.php">📦 Products</a>
<a href="orders.php">🛒 Orders</a>
<a href="../index.php">🌐 Website</a>
<a href="logout.php">🚪 Logout</a>

</div>

<div class="main">

<h1>Product Management</h1>

<a href="add_product.php" class="add-btn">➕ Add Product</a>

<div class="table-box">

<table>

<tr>
<th>ID</th>
<th>Product</th>
<th>Price</th>
<th>Stock</th>
<th>Action</th>
</tr>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td>₹<?php echo $row['price']; ?></td>
<td>
<?php

if($row['stock'] == 0){
echo "<span style='color:red'>Out of Stock</span>";
}
elseif($row['stock'] <= 5){
echo "<span style='color:orange'>Low Stock (".$row['stock'].")</span>";
}
else{
echo "<span style='color:green'>".$row['stock']."</span>";
}

?>
</td>

<td>

<a class="action-btn edit" href="edit_product.php?id=<?php echo $row['id']; ?>">Edit</a>

<form method="POST" style="display:inline;">
<input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
<button class="action-btn delete"
onclick="return confirm('Delete this product?');">
Delete
</button>
</form>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>