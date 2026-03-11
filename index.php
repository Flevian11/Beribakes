<?php include 'includes/header.php'; ?>

<!-- HERO SECTION -->

<section class="mt-10 grid lg:grid-cols-2 gap-12 items-center">

<!-- LEFT CONTENT -->

<div>

<p class="text-amber-600 font-semibold mb-3">

Fresh Bakery • Artisan Quality

</p>

<h1 class="text-5xl lg:text-6xl font-serif leading-tight">

Sweet moments  
<span class="text-amber-600">baked daily</span>

</h1>

<p class="text-stone-600 mt-5 max-w-lg">

Discover handcrafted breads, cakes and pastries made with natural ingredients and baked fresh every morning.

</p>

<div class="flex flex-wrap gap-4 mt-8">

<a href="products/shop.php"
class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-full shadow-lg">

Shop Bakery

</a>

<a href="about.php"
class="glass px-8 py-3 rounded-full">

Our Story

</a>

</div>

<!-- STATS -->

<div class="grid grid-cols-3 gap-6 mt-12">

<div>

<p class="text-3xl font-bold text-amber-600">120+</p>

<p class="text-sm text-stone-500">Products</p>

</div>

<div>

<p class="text-3xl font-bold text-amber-600">5k+</p>

<p class="text-sm text-stone-500">Customers</p>

</div>

<div>

<p class="text-3xl font-bold text-amber-600">4.9</p>

<p class="text-sm text-stone-500">Rating</p>

</div>

</div>

</div>


<!-- RIGHT GRAPHIC -->

<div class="relative flex justify-center">

<div class="absolute w-72 h-72 bg-amber-200 rounded-full blur-3xl opacity-40"></div>

<div class="grid grid-cols-2 gap-6 relative z-10">

<div class="glass p-6 rounded-3xl shadow float">

<i class="fa-solid fa-bread-slice text-5xl text-amber-600"></i>

</div>

<div class="glass p-6 rounded-3xl shadow float">

<i class="fa-solid fa-cake-candles text-5xl text-amber-600"></i>

</div>

<div class="glass p-6 rounded-3xl shadow float">

<i class="fa-solid fa-cookie-bite text-5xl text-amber-600"></i>

</div>

<div class="glass p-6 rounded-3xl shadow float">

<i class="fa-solid fa-mug-hot text-5xl text-amber-600"></i>

</div>

</div>

</div>

</section>


<!-- PRODUCTS -->

<section class="mt-20">

<div class="flex justify-between items-center mb-10">

<h2 class="text-3xl font-serif">

Fresh from the Oven

</h2>

<a href="products/shop.php" class="text-amber-600">

View All →

</a>

</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">

<?php

$stmt = $pdo->query("SELECT * FROM products LIMIT 8");

foreach($stmt as $p):

?>

<div class="glass p-4 rounded-3xl shadow hover:shadow-xl transition">

<img
src="uploads/products/<?= $p['image'] ?>"
class="rounded-xl h-52 w-full object-cover">

<div class="mt-4">

<h3 class="font-semibold text-lg">

<?= $p['product_name'] ?>

</h3>

<p class="text-sm text-stone-500">

<?= $p['description'] ?>

</p>

<div class="flex justify-between items-center mt-4">

<span class="font-bold text-amber-600 text-lg">

<?= $system['currency'] ?> <?= $p['price'] ?>

</span>

<?php if(isset($_SESSION['customer'])): ?>

<a
href="cart/add_to_cart.php?id=<?= $p['id'] ?>"
class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-full text-sm">

Add

</a>

<?php else: ?>

<a
href="auth/login.php"
class="bg-gray-200 px-4 py-2 rounded-full text-sm">

Login

</a>

<?php endif; ?>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</section>

<?php include 'includes/footer.php'; ?>