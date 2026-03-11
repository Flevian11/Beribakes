<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['admin'])){
header("Location: dashboard.php");
exit;
}

?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>BeriBakes Admin Login</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">

<h2 class="text-2xl font-semibold mb-6 text-center">

<i class="fa-solid fa-cake-candles text-amber-600 mr-2"></i>

BeriBakes Admin

</h2>

<?php if(isset($_SESSION['error'])): ?>

<div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">

<?= $_SESSION['error']; unset($_SESSION['error']); ?>

</div>

<?php endif; ?>

<form action="login_process.php" method="POST" class="space-y-4">

<input
type="email"
name="email"
placeholder="Admin Email"
required
class="border p-3 rounded w-full">

<div class="relative">

<input
type="password"
name="password"
id="password"
placeholder="Password"
required
class="border p-3 rounded w-full">

<i onclick="togglePassword()"
class="fa-regular fa-eye absolute right-4 top-4 cursor-pointer text-gray-500"></i>

</div>

<button
class="w-full bg-amber-600 hover:bg-amber-700 text-white py-3 rounded">

Login

</button>

</form>

</div>

<script>

function togglePassword(){

let pass = document.getElementById("password");

pass.type = pass.type === "password" ? "text" : "password";

}

</script>

</body>
</html>