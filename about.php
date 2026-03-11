<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'config/config.php';

include 'includes/header.php';

?>

<section class="mt-12 max-w-6xl mx-auto">

<div class="text-center mb-12">

<h1 class="text-4xl font-serif mb-3">

About <?= htmlspecialchars($system['system_name']) ?>

</h1>

<p class="text-gray-600 max-w-xl mx-auto">

Crafting fresh bakery delights every day with passion, creativity and quality ingredients.

</p>

</div>


<div class="grid md:grid-cols-2 gap-12 items-center">

<img
src="https://images.unsplash.com/photo-1509440159596-0249088772ff"
class="rounded-2xl shadow-lg object-cover">

<div>

<h2 class="text-2xl font-semibold mb-4">

Our Story

</h2>

<p class="text-gray-600 mb-4">

BeriBakes started as a small neighborhood bakery with a mission to bring
freshly baked happiness to our community. From soft breads to delicious
cakes and pastries, every item is baked daily using carefully selected
ingredients.

</p>

<p class="text-gray-600">

Today, BeriBakes continues to serve customers with a mix of traditional
recipes and modern flavors, ensuring that every bite is memorable.

</p>

</div>

</div>


<div class="grid md:grid-cols-3 gap-8 mt-16">

<div class="glass p-6 rounded-xl text-center">

<i class="fa-solid fa-bread-slice text-3xl text-amber-600 mb-3"></i>

<h3 class="font-semibold mb-2">

Fresh Ingredients

</h3>

<p class="text-sm text-gray-600">

We use only the freshest and highest quality ingredients in all our products.

</p>

</div>

<div class="glass p-6 rounded-xl text-center">

<i class="fa-solid fa-cake-candles text-3xl text-amber-600 mb-3"></i>

<h3 class="font-semibold mb-2">

Custom Cakes

</h3>

<p class="text-sm text-gray-600">

Beautiful cakes crafted for birthdays, weddings and special celebrations.

</p>

</div>

<div class="glass p-6 rounded-xl text-center">

<i class="fa-solid fa-truck text-3xl text-amber-600 mb-3"></i>

<h3 class="font-semibold mb-2">

Fast Delivery

</h3>

<p class="text-sm text-gray-600">

Enjoy our bakery products delivered fresh to your doorstep.

</p>

</div>

</div>

</section>

<?php include 'includes/footer.php'; ?>