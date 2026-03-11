<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* SAFE SYSTEM INFO FETCH */

$system = [
    "system_name" => "BeriBakes",
    "system_tagline" => "Fresh bakery everyday",
    "currency" => "KES"
];

try {

    $stmt = $pdo->query("SELECT * FROM system_info LIMIT 1");

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $system = $row;
    }
} catch (Exception $e) {
    /* fail silently */
}


/* CART COUNT */

$cartCount = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $item) {

        if (isset($item['qty'])) {
            $cartCount += $item['qty'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($system['system_name']) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

        <link rel="icon" type="image/x-icon" href="https://cdn.jsdelivr.net/npm/emoji-datasource-apple/img/apple/64/1f950.png">


    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(145deg, #fff7ef, #fef2e6);
            font-family: Inter, system-ui;
        }

        .glass {
            background: rgba(255, 255, 255, .65);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, .4);
        }

        #toast {
            transition: all .4s ease;
        }
    </style>

</head>

<body class="text-stone-800">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <header class="sticky top-0 z-50 bg-white/70 backdrop-blur-lg border-b border-amber-100">

            <div class="flex items-center justify-between py-4">

                <!-- LOGO -->

                <a href="<?= BASE_URL ?>index.php" class="flex items-center gap-3">

                    <i class="fa-solid fa-cake-candles text-3xl text-amber-600"></i>

                    <span class="text-xl font-serif">

                        <?= htmlspecialchars($system['system_name']) ?>

                    </span>

                </a>


                <!-- DESKTOP NAV -->

                <nav class="hidden md:flex items-center gap-8 font-medium text-sm">

                    <a href="<?= BASE_URL ?>index.php" class="hover:text-amber-600">Home</a>

                    <a href="<?= BASE_URL ?>products/shop.php" class="hover:text-amber-600">Shop</a>

                    <a href="<?= BASE_URL ?>about.php" class="hover:text-amber-600">About</a>

                    <a href="<?= BASE_URL ?>contact.php" class="hover:text-amber-600">Contact</a>

                </nav>


                <!-- RIGHT ACTIONS -->

                <!-- RIGHT ACTIONS -->

                <div class="flex items-center gap-6 text-xl relative">

                    <!-- CART DROPDOWN -->

                    <div class="relative">

                        <button onclick="toggleCart()" class="relative">

                            <i class="fa-solid fa-bag-shopping"></i>

                            <?php if ($cartCount > 0): ?>

                                <span
                                    class="absolute -top-2 -right-2 bg-amber-600 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">

                                    <?= $cartCount ?>

                                </span>

                            <?php endif; ?>

                        </button>


                        <!-- MINI CART -->

                        <div id="miniCart"
                            class="hidden absolute right-0 mt-4 w-80 bg-white rounded-xl shadow-lg border p-4 text-sm z-50">

                            <h3 class="font-semibold mb-3 flex items-center gap-2">

                                <i class="fa-solid fa-cart-shopping text-amber-600"></i>

                                Your Cart

                            </h3>

                            <?php

                            $subtotal = 0;

                            if (!empty($_SESSION['cart'])):

                                foreach ($_SESSION['cart'] as $item):

                                    $line = $item['qty'] * $item['price'];
                                    $subtotal += $line;

                            ?>

                                    <div class="flex items-center gap-3 py-2 border-b">

                                        <img
                                            src="<?= BASE_URL ?>uploads/products/<?= $item['image'] ?>"
                                            class="w-12 h-12 rounded object-cover">

                                        <div class="flex-1">

                                            <p class="text-sm font-medium">

                                                <?= htmlspecialchars($item['name']) ?>

                                            </p>

                                            <p class="text-xs text-gray-500">

                                                <?= $item['qty'] ?> × <?= $system['currency'] ?> <?= $item['price'] ?>

                                            </p>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                                <div class="flex justify-between font-semibold mt-3">

                                    <span>Subtotal</span>

                                    <span><?= $system['currency'] ?> <?= $subtotal ?></span>

                                </div>

                                <div class="mt-4 flex gap-2">

                                    <a
                                        href="<?= BASE_URL ?>cart/view_cart.php"
                                        class="flex-1 text-center border rounded-lg py-2 hover:bg-gray-50">

                                        View Cart

                                    </a>

                                    <a
                                        href="<?= BASE_URL ?>checkout.php"
                                        class="flex-1 text-center bg-amber-600 text-white rounded-lg py-2 hover:bg-amber-700">

                                        Checkout

                                    </a>

                                </div>

                            <?php else: ?>

                                <p class="text-center text-gray-500 py-6">

                                    Your cart is empty

                                </p>

                            <?php endif; ?>

                        </div>

                    </div>



                    <!-- PROFILE DROPDOWN -->

                    <div class="relative">

                        <button onclick="toggleProfile()">

                            <i class="fa-regular fa-user"></i>

                        </button>

                        <div id="profileMenu"
                            class="hidden absolute right-0 mt-4 w-56 bg-white rounded-xl shadow-lg border p-4 text-sm">

                            <?php if (isset($_SESSION['customer'])): ?>

                                <div class="border-b pb-3 mb-3">

                                    <p class="font-semibold">

                                        <?= htmlspecialchars($_SESSION['customer']['name'] ?? 'Customer') ?>

                                    </p>

                                    <p class="text-xs text-gray-500">

                                        <?= htmlspecialchars($_SESSION['customer']['email'] ?? '') ?>

                                    </p>

                                </div>

                                <a
                                    href="<?= BASE_URL ?>account/dashboard.php"
                                    class="block py-2 hover:text-amber-600">

                                    <i class="fa-solid fa-user mr-2"></i>
                                    My Account

                                </a>

                                <a
                                    href="<?= BASE_URL ?>cart/view_cart.php"
                                    class="block py-2 hover:text-amber-600">

                                    <i class="fa-solid fa-bag-shopping mr-2"></i>
                                    My Orders

                                </a>

                                <a
                                    href="<?= BASE_URL ?>auth/logout.php"
                                    class="block py-2 text-red-600">

                                    <i class="fa-solid fa-right-from-bracket mr-2"></i>
                                    Logout

                                </a>

                            <?php else: ?>

                                <a
                                    href="<?= BASE_URL ?>auth/login.php"
                                    class="block py-2 hover:text-amber-600">

                                    <i class="fa-solid fa-right-to-bracket mr-2"></i>
                                    Login

                                </a>

                                <a
                                    href="<?= BASE_URL ?>auth/login.php"
                                    class="block py-2 hover:text-amber-600">

                                    <i class="fa-solid fa-user-plus mr-2"></i>
                                    Register

                                </a>

                            <?php endif; ?>

                        </div>

                    </div>


                    <script>
                        function toggleCart() {

                            document.getElementById("miniCart").classList.toggle("hidden");

                        }

                        function toggleProfile() {

                            document.getElementById("profileMenu").classList.toggle("hidden");

                        }

                        document.addEventListener("click", function(e) {

                            let cart = document.getElementById("miniCart");
                            let profile = document.getElementById("profileMenu");

                            if (!e.target.closest("#miniCart") && !e.target.closest(".fa-bag-shopping")) {
                                cart.classList.add("hidden");
                            }

                            if (!e.target.closest("#profileMenu") && !e.target.closest(".fa-user")) {
                                profile.classList.add("hidden");
                            }

                        });
                    </script>


                    <!-- MOBILE MENU BUTTON -->

                    <i onclick="toggleMenu()" class="fa-solid fa-bars md:hidden cursor-pointer text-2xl"></i>

                </div>

            </div>


            <!-- MOBILE NAV -->

            <div id="mobileMenu" class="hidden md:hidden pb-6 space-y-4 text-sm font-medium">

                <a href="<?= BASE_URL ?>index.php" class="block">Home</a>

                <a href="<?= BASE_URL ?>products/shop.php" class="block">Shop</a>

                <a href="<?= BASE_URL ?>about.php" class="block">About</a>

                <a href="<?= BASE_URL ?>contact.php" class="block">Contact</a>

            </div>

        </header>