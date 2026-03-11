<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once '../config/db.php';
require_once '../config/config.php';

include '../includes/header.php';

$cart = $_SESSION['cart'] ?? [];

$total = 0;

?>

<section class="mt-10 grid lg:grid-cols-3 gap-10">

<!-- CART ITEMS -->

<div class="lg:col-span-2">

<h2 class="text-3xl font-serif mb-6 flex items-center gap-2">

<i class="fa-solid fa-cart-shopping text-amber-600"></i>

Your Cart

</h2>

<?php if(empty($cart)): ?>

<div class="glass p-10 rounded-2xl text-center">

<i class="fa-solid fa-basket-shopping text-5xl text-gray-400 mb-4"></i>

<p class="text-lg text-gray-500 mb-4">

Your cart is empty

</p>

<a
href="<?= BASE_URL ?>products/shop.php"
class="bg-amber-600 text-white px-6 py-3 rounded-xl">

Start Shopping

</a>

</div>

<?php else: ?>

<div class="space-y-6">

<?php foreach($cart as $id => $item):

$subtotal = $item['qty'] * $item['price'];
$total += $subtotal;

?>

<div class="glass p-4 rounded-2xl flex flex-col md:flex-row gap-6 items-center">

<!-- IMAGE -->

<img
src="<?= BASE_URL ?>uploads/products/<?= $item['image'] ?>"
class="w-24 h-24 rounded-lg object-cover">

<!-- INFO -->

<div class="flex-1">

<h3 class="font-semibold text-lg">

<?= htmlspecialchars($item['name']) ?>

</h3>

<p class="text-gray-500 text-sm">

<?= $system['currency'] ?> <?= $item['price'] ?>

</p>

</div>

<!-- QUANTITY -->

<div class="flex items-center gap-3">

<form action="<?= BASE_URL ?>cart/update_cart.php" method="POST" class="flex items-center gap-2">

<input type="hidden" name="id" value="<?= $id ?>">

<button
type="submit"
name="action"
value="decrease"
class="w-8 h-8 rounded bg-gray-200">

−

</button>

<input
name="qty"
value="<?= $item['qty'] ?>"
class="w-10 text-center border rounded">

<button
type="submit"
name="action"
value="increase"
class="w-8 h-8 rounded bg-gray-200">

+

</button>

</form>

</div>

<!-- SUBTOTAL -->

<div class="font-semibold text-lg">

<?= $system['currency'] ?> <?= $subtotal ?>

</div>

<!-- REMOVE -->

<a
href="<?= BASE_URL ?>cart/remove_item.php?id=<?= $id ?>"
class="text-red-500 text-sm">

<i class="fa-solid fa-trash"></i>

</a>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>

</div>


<!-- ORDER SUMMARY -->

<div>

<div class="glass p-6 rounded-2xl sticky top-24">

<h3 class="text-xl font-semibold mb-4">

Order Summary

</h3>

<div class="flex justify-between py-2 border-b">

<span>Items</span>

<span><?= count($cart) ?></span>

</div>

<div class="flex justify-between py-2 border-b">

<span>Subtotal</span>

<span>

<?= $system['currency'] ?> <?= $total ?>

</span>

</div>

<div class="flex justify-between py-2 border-b">

<span>Delivery</span>

<span class="text-green-600">

Free

</span>

</div>

<div class="flex justify-between py-3 font-bold text-lg">

<span>Total</span>

<span>

<?= $system['currency'] ?> <?= $total ?>

</span>

</div>

<a
href="<?= BASE_URL ?>checkout.php"
class="block w-full text-center bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-xl mt-4">

Proceed to Checkout

</a>

<a
href="<?= BASE_URL ?>products/shop.php"
class="block w-full text-center border py-3 rounded-xl mt-3">

Continue Shopping

</a>

</div>

</div>

</section>

<?php include '../includes/footer.php'; ?>