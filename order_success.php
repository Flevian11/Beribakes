<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
include 'includes/header.php';

$order = $_GET['order'] ?? '';

?>

<section class="min-h-[70vh] flex items-center justify-center text-center">

<div class="glass p-10 rounded-2xl max-w-lg">

<i class="fa-solid fa-circle-check text-green-600 text-6xl mb-6"></i>

<h2 class="text-3xl font-serif mb-4">

Order Successful

</h2>

<p class="text-gray-600 mb-4">

Your order has been received and is being prepared.

</p>

<p class="text-sm text-gray-500 mb-6">

Order ID: #<?= htmlspecialchars($order) ?>

</p>

<a
href="<?= BASE_URL ?>products/shop.php"
class="bg-amber-600 text-white px-6 py-3 rounded-xl">

Continue Shopping

</a>

</div>

</section>

<?php include 'includes/footer.php'; ?>