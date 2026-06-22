
<!DOCTYPE html>
<html>
<head>

<title>Settings</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>
body{
font-family:'Poppins';
background:#f5f7fb;
padding:20px;
}

.menu{
background:white;
padding:20px;
border-radius:12px;
margin-bottom:15px;
display:flex;
align-items:center;
gap:10px;
cursor:pointer;
text-decoration:none;
color:black;
box-shadow:0 5px 15px rgba(0,0,0,0.08);
}

.menu:hover{
transform:scale(1.02);
}
</style>

</head>

<body>

<h1>⚙️ Settings</h1>

<a href="orders.php" class="menu">
<span class="material-icons">shopping_bag</span>
My Orders
</a>

<a href="recipe.php" class="menu">
<span class="material-icons">restaurant_menu</span>
AI Recipe Suggestor
</a>

<a href="support.php" class="menu">
<span class="material-icons">support_agent</span>
Help & Support
</a>

</body>
</html>