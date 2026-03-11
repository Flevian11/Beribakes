<?php

require_once '../config/db.php';
include '../includes/header.php';

/* CATEGORY LIST */

$categories = $pdo->query("SELECT * FROM categories");

/* FILTER LOGIC */

$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$sql = "SELECT * FROM products WHERE 1";
$params = [];

if($category){
$sql .= " AND category_id=?";
$params[] = $category;
}

if($search){
$sql .= " AND product_name LIKE ?";
$params[] = "%$search%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll();

?>

<section class="mt-10 grid lg:grid-cols-4 gap-8">

<!-- MOBILE CATEGORY BUTTON -->

<div class="lg:hidden col-span-full">

<button
onclick="toggleCategories()"
class="bg-amber-600 text-white px-5 py-3 rounded-xl flex items-center gap-2">

<i class="fa-solid fa-bars"></i>

Browse Categories

</button>

</div>



<!-- SIDEBAR -->

<aside id="categorySidebar"
class="hidden lg:block glass p-6 rounded-3xl">

<h3 class="font-semibold text-lg mb-4 flex items-center gap-2">

<i class="fa-solid fa-layer-group text-amber-600"></i>

Categories

</h3>

<ul class="space-y-3 text-stone-600">

<?php foreach($categories as $c): ?>

<li>

<a
href="?category=<?= $c['id'] ?>"
class="flex justify-between items-center hover:text-amber-600">

<span><?= $c['category_name'] ?></span>

<i class="fa-solid fa-angle-right text-sm"></i>

</a>

</li>

<?php endforeach; ?>

</ul>


<!-- SEARCH -->

<div class="mt-8">

<form method="GET">

<input
name="search"
placeholder="Search bakery items"
class="w-full border rounded-xl p-3 text-sm">

<button
class="mt-3 w-full bg-amber-600 text-white py-2 rounded-xl">

Search

</button>

</form>

</div>

</aside>



<!-- PRODUCT AREA -->

<div class="lg:col-span-3">

<div class="flex flex-wrap justify-between items-center mb-8 gap-3">

<h2 class="text-3xl font-serif">

Bakery Shop

</h2>

<span class="text-sm text-stone-500">

<?= count($products) ?> items available

</span>

</div>



<!-- PRODUCTS GRID -->

<div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

<?php foreach($products as $p): ?>

<div class="glass p-4 rounded-2xl hover:shadow-xl transition flex flex-col">

<!-- PRODUCT IMAGE -->

<div class="relative">

<img
src="../uploads/products/<?= $p['image'] ?>"
class="rounded-xl h-44 w-full object-cover">

<!-- BADGE -->

<div class="absolute top-3 left-3 bg-amber-600 text-white text-xs px-2 py-1 rounded">

Fresh

</div>

</div>



<!-- PRODUCT INFO -->

<div class="mt-4 flex flex-col flex-1">

<h3 class="font-semibold text-sm">

<?= $p['product_name'] ?>

</h3>

<p class="text-xs text-stone-500 line-clamp-2">

<?= $p['description'] ?>

</p>



<!-- RATING -->

<div class="text-amber-500 text-sm mt-2">

<i class="fa-solid fa-star"></i>
<i class="fa-solid fa-star"></i>
<i class="fa-solid fa-star"></i>
<i class="fa-solid fa-star"></i>
<i class="fa-regular fa-star"></i>

</div>



<!-- STOCK -->

<div class="text-xs text-green-600 mt-1">

<i class="fa-solid fa-check"></i>

In stock

</div>



<!-- PRICE + CART -->

<div class="flex justify-between items-center mt-4">

<span class="font-bold text-lg text-amber-600">

<?= $system['currency'] ?> <?= $p['price'] ?>

</span>


<?php if(isset($_SESSION['customer'])): ?>

<a
href="../cart/add_to_cart.php?id=<?= $p['id'] ?>"
class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-2 rounded-full text-xs">

Add

</a>

<?php else: ?>

<a
href="../auth/login.php"
class="bg-gray-200 px-3 py-2 rounded-full text-xs">

Login

</a>

<?php endif; ?>

</div>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

</section>



<script>

function toggleCategories(){

document
.getElementById("categorySidebar")
.classList.toggle("hidden");

}

</script>

<?php include '../includes/footer.php'; ?>