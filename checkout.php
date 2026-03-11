<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'config/config.php';

include 'includes/header.php';

$cart = $_SESSION['cart'] ?? [];

if(empty($cart)){
header("Location: ".BASE_URL."products/shop.php");
exit;
}

$total = 0;

?>

<section class="mt-10 grid lg:grid-cols-3 gap-10">

<!-- CUSTOMER FORM -->

<div class="lg:col-span-2">

<h2 class="text-3xl font-serif mb-6">

Checkout

</h2>

<form action="process_order.php" method="POST" class="space-y-6">

<div class="grid md:grid-cols-2 gap-4">

<input
name="name"
required
placeholder="Full Name"
class="border rounded-xl p-3 w-full">

<input
name="phone"
required
placeholder="Phone Number"
class="border rounded-xl p-3 w-full">

</div>

<input
name="email"
required
placeholder="Email"
class="border rounded-xl p-3 w-full">

<textarea
name="address"
required
placeholder="Delivery Address"
class="border rounded-xl p-3 w-full"></textarea>


<!-- PAYMENT METHOD -->

<div class="mt-6">

<h3 class="font-semibold mb-3">

Payment Method

</h3>

<div class="space-y-3">

<label class="flex items-center gap-3 border p-3 rounded-xl">

<input type="radio" name="payment" value="mpesa" checked>

<img
src="https://upload.wikimedia.org/wikipedia/commons/1/15/M-PESA_LOGO-01.svg"
class="h-6">

<span>Lipa na M-Pesa</span>

</label>

<label class="flex items-center gap-3 border p-3 rounded-xl">

<input type="radio" name="payment" value="cash">

<i class="fa-solid fa-money-bill text-green-600"></i>

<span>Cash on Delivery</span>

</label>

</div>

</div>


<button
class="w-full bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-xl mt-6">

Place Order

</button>

</form>

</div>


<!-- ORDER SUMMARY -->

<div>

<div class="glass p-6 rounded-2xl sticky top-24">

<h3 class="text-xl font-semibold mb-4">

Order Summary

</h3>

<div class="space-y-4">

<?php foreach($cart as $item):

$subtotal = $item['qty'] * $item['price'];
$total += $subtotal;

?>

<div class="flex items-center gap-3">

<img
src="<?= BASE_URL ?>uploads/products/<?= $item['image'] ?>"
class="w-12 h-12 rounded object-cover">

<div class="flex-1 text-sm">

<p>

<?= htmlspecialchars($item['name']) ?>

</p>

<p class="text-gray-500">

<?= $item['qty'] ?> × <?= $system['currency'] ?> <?= $item['price'] ?>

</p>

</div>

</div>

<?php endforeach; ?>

</div>

<hr class="my-4">

<div class="flex justify-between font-semibold">

<span>Total</span>

<span>

<?= $system['currency'] ?> <?= $total ?>

</span>

</div>

</div>

</div>

</section>

<?php include 'includes/footer.php'; ?>