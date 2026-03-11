<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$id = $_GET['id'];

unset($_SESSION['cart'][$id]);

header("Location: view_cart.php");