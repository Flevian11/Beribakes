<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'config/config.php';

include 'includes/header.php';

?>

<section class="mt-12 max-w-6xl mx-auto">

<div class="text-center mb-10">

<h1 class="text-4xl font-serif mb-3">

Contact Us

</h1>

<p class="text-gray-600">

We would love to hear from you. Reach out for orders or inquiries.

</p>

</div>


<div class="grid md:grid-cols-2 gap-12">

<!-- CONTACT FORM -->

<div class="glass p-8 rounded-2xl">

<form action="#" class="space-y-4">

<input
placeholder="Your Name"
class="border rounded-xl p-3 w-full">

<input
placeholder="Email Address"
class="border rounded-xl p-3 w-full">

<input
placeholder="Phone Number"
class="border rounded-xl p-3 w-full">

<textarea
placeholder="Your Message"
class="border rounded-xl p-3 w-full"></textarea>

<button
class="bg-amber-600 text-white px-6 py-3 rounded-xl w-full">

Send Message

</button>

</form>

</div>


<!-- CONTACT INFO -->

<div class="space-y-6">

<div class="flex gap-4">

<i class="fa-solid fa-location-dot text-amber-600 text-xl"></i>

<div>

<h4 class="font-semibold">

Address

</h4>

<p class="text-gray-600 text-sm">

Nairobi, Kenya

</p>

</div>

</div>


<div class="flex gap-4">

<i class="fa-solid fa-phone text-amber-600 text-xl"></i>

<div>

<h4 class="font-semibold">

Phone

</h4>

<p class="text-gray-600 text-sm">

+254 700 000000

</p>

</div>

</div>


<div class="flex gap-4">

<i class="fa-solid fa-envelope text-amber-600 text-xl"></i>

<div>

<h4 class="font-semibold">

Email

</h4>

<p class="text-gray-600 text-sm">

info@beribakes.com

</p>

</div>

</div>

</div>

</div>

</section>

<?php include 'includes/footer.php'; ?>