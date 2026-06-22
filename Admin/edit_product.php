<?php
include "../config/db.php";

$id = $_GET['id'];

$result = mysqli_query($conn,"SELECT * FROM products WHERE id=$id");
$product = mysqli_fetch_assoc($result);

if(isset($_POST['update'])){

$name = $_POST['name'];
$price = $_POST['price'];
$stock = $_POST['stock'];

mysqli_query($conn,"
UPDATE products 
SET name='$name', price='$price', stock='$stock'
WHERE id=$id
");

header("Location: products.php");
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Product</title>

<style>

body{
font-family:Arial;
background:#f5f7fb;
}

.container{
width:400px;
margin:100px auto;
background:white;
padding:30px;
border-radius:10px;
box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

input{
width:100%;
padding:10px;
margin-bottom:15px;
border:1px solid #ddd;
border-radius:6px;
}

button{
background:#3b82f6;
color:white;
padding:10px;
border:none;
border-radius:6px;
width:100%;
cursor:pointer;
}

</style>

</head>

<body>

<div class="container">

<h2>Edit Product</h2>

<form method="POST">

<label>Product Name</label>
<input type="text" name="name" value="<?php echo $product['name']; ?>" required>

<label>Price</label>
<input type="number" name="price" value="<?php echo $product['price']; ?>" required>

<label>Stock</label>
<input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>

<button name="update">Update Product</button>

</form>

</div>

</body>
</html>