<?php

require_once '../config/db.php';
include '../includes/header.php';

?>

<section class="min-h-[80vh] flex items-center justify-center py-12">

    <div class="glass w-full max-w-5xl rounded-3xl shadow-xl grid md:grid-cols-2 overflow-hidden">

        <!-- LEFT PANEL -->

        <div class="hidden md:flex flex-col justify-center items-center bg-gradient-to-br from-amber-50 to-orange-100 p-10 text-center">

            <i class="fa-solid fa-cake-candles text-6xl text-amber-600 mb-6 float"></i>

            <h2 class="text-3xl font-serif mb-4">
                Welcome to BeriBakes
            </h2>

            <p class="text-stone-600">
                Fresh bakery delights crafted daily with love and care.
            </p>

        </div>


        <!-- RIGHT PANEL -->

        <div class="p-8">

            <!-- TABS -->

            <div class="flex border-b mb-6 text-sm font-semibold">

                <button
                    onclick="showTab('login')"
                    class="flex-1 py-3 text-center tab-btn">

                    Login

                </button>

                <button
                    onclick="showTab('register')"
                    class="flex-1 py-3 text-center tab-btn">

                    Register

                </button>

            </div>


            <!-- FLOATING ERROR -->

            <div id="errorBox"
                class="hidden mb-4 bg-red-100 text-red-700 p-3 rounded-xl text-sm">

            </div>



            <!-- LOGIN -->

            <div id="loginTab">

                <form id="loginForm" action="login_process.php" method="POST" class="space-y-4">

                    <input
                        type="email"
                        name="email"
                        placeholder="Email"
                        required
                        class="w-full border rounded-xl p-3">

                    <div class="relative">

                        <input
                            type="password"
                            name="password"
                            id="loginPassword"
                            placeholder="Password"
                            required
                            class="w-full border rounded-xl p-3">

                        <i
                            onclick="togglePassword('loginPassword')"
                            class="fa-regular fa-eye absolute right-4 top-4 cursor-pointer text-gray-500">

                        </i>

                    </div>

                    <button
                        class="w-full bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-xl">

                        Login

                    </button>

                </form>


                <!-- SOCIAL LOGIN -->

                <div class="mt-6">

                    <p class="text-center text-sm text-stone-500 mb-4">
                        or continue with
                    </p>

                    <div class="grid grid-cols-3 gap-3">

                        <a href="social_login.php?provider=google"
                            class="border rounded-xl py-2 flex items-center justify-center gap-2 hover:bg-gray-50">

                            <i class="fa-brands fa-google text-red-500"></i>
                            Google

                        </a>

                        <a href="social_login.php?provider=apple"
                            class="border rounded-xl py-2 flex items-center justify-center gap-2 hover:bg-gray-50">

                            <i class="fa-brands fa-apple"></i>
                            Apple

                        </a>

                        <a href="social_login.php?provider=facebook"
                            class="border rounded-xl py-2 flex items-center justify-center gap-2 hover:bg-gray-50">

                            <i class="fa-brands fa-facebook text-blue-600"></i>
                            Facebook

                        </a>

                    </div>

                </div>

            </div>



            <!-- REGISTER -->

            <div id="registerTab" class="hidden">

                <form id="registerForm" action="register.php" method="POST" class="space-y-4">

                    <input
                        type="text"
                        name="name"
                        placeholder="Full Name"
                        required
                        class="w-full border rounded-xl p-3">

                    <input
                        type="email"
                        name="email"
                        placeholder="Email"
                        required
                        class="w-full border rounded-xl p-3">

                    <input
                        type="text"
                        name="phone"
                        placeholder="Phone Number"
                        class="w-full border rounded-xl p-3">

                    <div class="relative">

                        <input
                            type="password"
                            name="password"
                            id="registerPassword"
                            placeholder="Password"
                            required
                            class="w-full border rounded-xl p-3">

                        <i
                            onclick="togglePassword('registerPassword')"
                            class="fa-regular fa-eye absolute right-4 top-4 cursor-pointer text-gray-500">

                        </i>

                    </div>

                    <!-- PASSWORD STRENGTH -->

                    <div id="passwordStrength" class="text-xs text-gray-500"></div>

                    <button
                        class="w-full bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-xl">

                        Create Account

                    </button>

                </form>

            </div>

        </div>

    </div>

</section>



<script>
    /* TAB SWITCH */

    function showTab(tab) {

        document.getElementById("loginTab").classList.add("hidden");
        document.getElementById("registerTab").classList.add("hidden");

        if (tab === "login") {
            document.getElementById("loginTab").classList.remove("hidden");
        }

        if (tab === "register") {
            document.getElementById("registerTab").classList.remove("hidden");
        }

    }


    /* PASSWORD VISIBILITY */

    function togglePassword(id) {

        let input = document.getElementById(id);

        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }

    }


    /* PASSWORD STRENGTH */

    const passwordInput = document.getElementById("registerPassword");

    if (passwordInput) {

        passwordInput.addEventListener("input", function() {

            let val = passwordInput.value;

            let strength = "Weak";

            if (val.length > 8 && /[A-Z]/.test(val) && /[0-9]/.test(val)) {
                strength = "Strong";
            } else if (val.length > 6) {
                strength = "Medium";
            }

            document.getElementById("passwordStrength").innerText =
                "Password strength: " + strength;

        });

    }


    /* CLIENT VALIDATION */

    function showError(msg) {

        let box = document.getElementById("errorBox");

        box.innerText = msg;

        box.classList.remove("hidden");

        setTimeout(() => {
            box.classList.add("hidden");
        }, 3000);

    }

    document.getElementById("loginForm").addEventListener("submit", function(e) {

        let email = this.email.value;

        if (!email.includes("@")) {
            e.preventDefault();
            showError("Enter a valid email address");
        }

    });

    document.getElementById("registerForm").addEventListener("submit", function(e) {

        let pass = this.password.value;

        if (pass.length < 6) {
            e.preventDefault();
            showError("Password must be at least 6 characters");
        }

    });
</script>


<?php include '../includes/footer.php'; ?>