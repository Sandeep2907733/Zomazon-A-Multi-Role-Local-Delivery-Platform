<?php
include("../config/db.php");

$token = $_GET['token'];

$sql = "SELECT * FROM users WHERE reset_token='$token' AND reset_expires > NOW()";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0){
    die("Invalid or expired link");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password - Zomazon</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

<style>
body{
    font-family: Poppins;
}
</style>

</head>

<body class="bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center min-h-screen">

<div class="bg-white w-[350px] p-8 rounded-2xl shadow-2xl">

    <h2 class="text-2xl font-bold text-center text-green-600 mb-6">
        Reset Password
    </h2>

    <form action="update-password.php" method="POST" class="space-y-4">

        <input type="hidden" name="token" value="<?php echo $token; ?>">

        <input 
            type="password"
            name="password"
            placeholder="New Password"
            required
            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
        >

        <button 
            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition"
        >
            Update Password
        </button>

    </form>

</div>

</body>
</html>