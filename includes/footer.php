<footer class="mt-16 pt-8 border-t border-amber-200/50 bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 rounded-t-3xl">

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 py-10 px-4">

<!-- Brand Section -->
<div>

<div class="flex items-center gap-3 text-2xl font-serif">

<i class="fa-solid fa-cake-candles text-amber-500"></i>

<?= strtolower($system['system_name']) ?>

</div>

<p class="text-stone-500 text-sm mt-3 leading-relaxed">

<?= $system['system_tagline'] ?>

</p>

<div class="flex gap-4 mt-5 text-amber-600 text-xl">

<i class="fa-brands fa-instagram hover:text-amber-800 cursor-pointer"></i>

<i class="fa-brands fa-facebook hover:text-amber-800 cursor-pointer"></i>

<i class="fa-brands fa-x-twitter hover:text-amber-800 cursor-pointer"></i>

<i class="fa-brands fa-pinterest hover:text-amber-800 cursor-pointer"></i>

</div>

</div>



<!-- Explore -->
<div>

<h4 class="font-semibold flex items-center gap-2">

<i class="fa-regular fa-compass text-amber-500"></i>

Explore

</h4>

<ul class="mt-4 space-y-2 text-stone-600 text-sm">

<li>
<a href="products/shop.php" class="hover:text-amber-600 flex items-center gap-2">
<i class="fa-regular fa-circle-right text-xs"></i>
All Products
</a>
</li>

<li>
<a href="#" class="hover:text-amber-600 flex items-center gap-2">
<i class="fa-regular fa-circle-right text-xs"></i>
Weekly Specials
</a>
</li>

<li>
<a href="#" class="hover:text-amber-600 flex items-center gap-2">
<i class="fa-regular fa-circle-right text-xs"></i>
Gift Boxes
</a>
</li>

<li>
<a href="#" class="hover:text-amber-600 flex items-center gap-2">
<i class="fa-regular fa-circle-right text-xs"></i>
Wholesale
</a>
</li>

</ul>

</div>



<!-- Help -->
<div>

<h4 class="font-semibold flex items-center gap-2">

<i class="fa-regular fa-message text-amber-500"></i>

Help

</h4>

<ul class="mt-4 space-y-2 text-stone-600 text-sm">

<li>
<a href="#" class="hover:text-amber-600 flex items-center gap-2">
<i class="fa-regular fa-circle-right text-xs"></i>
Contact Us
</a>
</li>

<li>
<a href="#" class="hover:text-amber-600 flex items-center gap-2">
<i class="fa-regular fa-circle-right text-xs"></i>
Delivery Info
</a>
</li>

<li>
<a href="#" class="hover:text-amber-600 flex items-center gap-2">
<i class="fa-regular fa-circle-right text-xs"></i>
Allergy Advice
</a>
</li>

<li>
<a href="#" class="hover:text-amber-600 flex items-center gap-2">
<i class="fa-regular fa-circle-right text-xs"></i>
Returns
</a>
</li>

</ul>

</div>



<!-- Opening Hours -->
<div>

<h4 class="font-semibold flex items-center gap-2">

<i class="fa-regular fa-clock text-amber-500"></i>

Opening Hours

</h4>

<ul class="mt-4 space-y-2 text-stone-600 text-sm">

<li class="flex justify-between">
<span>Mon - Fri</span>
<span>7am – 7pm</span>
</li>

<li class="flex justify-between">
<span>Saturday</span>
<span>8am – 6pm</span>
</li>

<li class="flex justify-between">
<span>Sunday</span>
<span>8am – 2pm</span>
</li>

</ul>

<p class="mt-4 text-sm bg-amber-100 p-2 rounded-full text-center">

<i class="fa-regular fa-bell"></i>
Fresh bake-off at 10am daily

</p>

</div>

</div>



<!-- Bottom Footer -->

<div class="border-t border-amber-200 py-6 px-4 flex flex-col md:flex-row justify-between items-center text-sm text-stone-500">

<span>
© <?= date('Y') ?> <?= $system['system_name'] ?> · handmade with ❤
</span>

<div class="flex gap-4 items-center text-xl mt-3 md:mt-0">

<i class="fa-brands fa-cc-visa"></i>

<i class="fa-brands fa-cc-mastercard"></i>

<i class="fa-brands fa-cc-apple-pay"></i>

<img
src="https://upload.wikimedia.org/wikipedia/commons/1/15/M-PESA_LOGO-01.svg"
class="h-6"
alt="Mpesa">

</div>

</div>

</footer>

</div>

<script>

function toggleMenu(){
document.getElementById("mobileMenu").classList.toggle("hidden");
}

</script>

<?php if(isset($_SESSION['toast'])): ?>

<div id="toast"
class="fixed bottom-6 right-6 bg-green-600 text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 z-50">

<i class="fa-solid fa-circle-check"></i>

<span><?= $_SESSION['toast'] ?></span>

</div>

<script>

setTimeout(()=>{
document.getElementById("toast").style.opacity="0";
document.getElementById("toast").style.transform="translateY(20px)";
},2500);

setTimeout(()=>{
document.getElementById("toast").remove();
},3000);

</script>

<?php unset($_SESSION['toast']); endif; ?>

</body>
</html>