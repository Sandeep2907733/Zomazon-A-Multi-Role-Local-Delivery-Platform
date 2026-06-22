<?php
include "../config/db.php";

if(isset($_POST['login'])){

$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
$result = mysqli_query($conn,$query);

if(mysqli_num_rows($result)>0){

$_SESSION['admin']=$username;
header("Location: dashboard.php");

}else{
$error="Invalid Login";
}

}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Login</title>

<style>
body{
background:#111827;
font-family:Arial;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.login-box{
background:white;
padding:30px;
border-radius:10px;
width:300px;
}

input{
width:100%;
padding:10px;
margin:10px 0;
border-radius:10px;
}

button{
width:100%;
padding:10px;
background:#16a34a;
color:white;
border:none;
cursor:pointer;
}
h2{
display:flex;
justify-content:center;
align-items:center;
}
</style>

</head>

<body>

<div class="login-box">

<h2>Admin Login</h2>

<?php if(isset($error)) echo $error; ?>

<form method="post">

<input type="text" name="username" placeholder="Username">

<input type="password" name="password" placeholder="Password">

<button name="login">Login</button>

</form>

</div>

</body>
</html>