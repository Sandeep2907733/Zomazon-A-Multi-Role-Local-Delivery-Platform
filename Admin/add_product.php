<?php
include '../config/db.php';

if(isset($_POST['add_product'])){

$name = mysqli_real_escape_string($conn,$_POST['name']);
$price = $_POST['price'];
$stock = $_POST['stock'];
$category = $_POST['category'];

$image = $_FILES['image']['name'];
$temp = $_FILES['image']['tmp_name'];

move_uploaded_file($temp,"../images/".$image);

$query = "INSERT INTO products(name,price,stock,category,image)
VALUES('$name','$price','$stock','$category','$image')";

mysqli_query($conn,$query);

$success = "Product added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Product - Admin</title>

<style>

body{
margin:0;
font-family:Arial;
background:#f3f4f6;
}

/* SIDEBAR */

.sidebar{
width:230px;
height:100vh;
background:#111827;
position:fixed;
padding-top:20px;
}

.sidebar h2{
color:white;
text-align:center;
margin-bottom:30px;
}

.sidebar a{
display:block;
color:#d1d5db;
padding:14px 20px;
text-decoration:none;
}

.sidebar a:hover{
background:#1f2937;
color:white;
}

/* MAIN */

.main{
margin-left:230px;
padding:30px;
}

.header{
font-size:22px;
font-weight:bold;
margin-bottom:20px;
}

/* CARD */

.card{
background:white;
padding:25px;
border-radius:10px;
max-width:500px;
box-shadow:0 3px 10px rgba(0,0,0,0.08);
}

/* FORM */

label{
font-weight:600;
display:block;
margin-top:10px;
}

input, select{
width:100%;
padding:10px;
margin-top:6px;
border:1px solid #ccc;
border-radius:6px;
}

button{
margin-top:15px;
background:#2563eb;
color:white;
border:none;
padding:10px 15px;
border-radius:6px;
cursor:pointer;
}

button:hover{
background:#1d4ed8;
}

.success{
color:green;
margin-bottom:10px;
}

</style>
</head>

<body>

<!-- Sidebar -->

<div class="sidebar">

<h2>Zomazon Admin</h2>

<a href="dashboard.php">📊 Dashboard</a>
<a href="products.php">📦 Products</a>
<a href="orders.php">🧾 Orders</a>
<a href="add_product.php">➕ Add Product</a>

</div>

<!-- Main Content -->

<div class="main">

<div class="header">Add New Product</div>

<div class="card">

<?php
if(isset($success)){
echo "<p class='success'>$success</p>";
}
?>

<form method="POST" enctype="multipart/form-data">

<label>Product Name</label>
<input type="text" name="name" required>

<label>Price</label>
<input type="number" name="price" required>

<label>Stock</label>
<input type="number" name="stock" required>

<label>Category</label>
<select name="category">

<option>Fruits</option>
<option>Vegetables</option>
<option>Dairy</option>
<option>Snacks</option>
<option>Beverages</option>
<option>Household</option>
<option>Guthka</option>

</select>

<label>Product Image</label>
<input type="file" name="image" required>

<button type="submit" name="add_product">Add Product</button>

</form>

</div>

</div>

</body>
</html>