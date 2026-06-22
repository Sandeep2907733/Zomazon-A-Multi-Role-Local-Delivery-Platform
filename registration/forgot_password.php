<!DOCTYPE html>
<html>
<head>
<title>Forgot Password - Zomazon</title>

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
        Forgot Password
    </h2>

    <p class="text-sm text-gray-500 text-center mb-4">
        Enter your email and we’ll send you a reset link
    </p>

    <form action="send-reset-link.php" method="POST" class="space-y-4">

        <input 
            type="email"
            name="email"
            placeholder="Enter your email"
            required
            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
        >

        <button 
            class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition"
        >
            Send Reset Link
        </button>

    </form>

    <div class="mt-5 text-center text-sm">
        <a href="login.php" class="text-green-600 hover:underline">
            Back to Login
        </a>
    </div>

</div>

</body>
</html>