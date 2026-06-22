<?php
include "../config/db.php";

$error="";

if($_SERVER['REQUEST_METHOD']=="POST"){

$phone=$_POST['phone'];
$password=$_POST['password'];

$res=mysqli_query($conn,"SELECT * FROM local_shops WHERE phone='$phone'");

if(mysqli_num_rows($res)==1){

$shop=mysqli_fetch_assoc($res);

if(password_verify($password,$shop['PASSWORD'])){
$_SESSION['shop_id']=$shop['id'];
header("Location: dashboard.php");
}else{
$error="Wrong Password";
}

}else{
$error="Shop not found";
}
}
?>

<!DOCTYPE html>
<html>
<head>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

<style>
body{
font-family:Poppins;
background:linear-gradient(135deg,#22c55e,#16a34a);
display:flex;
justify-content:center;
align-items:center;
height:100vh;
margin:0;
}

.box{
background:white;
padding:30px;
border-radius:15px;
width:300px;
box-shadow:0 10px 25px rgba(0,0,0,0.2);
text-align:center;
}

input{
width:100%;
padding:10px;
margin-top:10px;
border-radius:8px;
border:1px solid #ddd;
}

button{
width:100%;
padding:10px;
margin-top:15px;
background:#22c55e;
color:white;
border:none;
border-radius:8px;
}

.error{color:red;}
</style>
</head>

<body>

<div class="box">

<h2>🔐 Shop Login</h2>

<?php if($error) echo "<p class='error'>$error</p>"; ?>

<form method="POST">
<input name="phone" placeholder="Phone">
<input type="password" name="password" placeholder="Password">
<button>Login</button>
<p>Don't have an account?  
    <a href="Registration.php">Register</a>
</form>

</div>

</body>
</html>