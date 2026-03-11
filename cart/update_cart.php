<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$id = $_POST['id'];
$action = $_POST['action'];

if(isset($_SESSION['cart'][$id])){

if($action === "increase"){
$_SESSION['cart'][$id]['qty']++;
}

if($action === "decrease"){
$_SESSION['cart'][$id]['qty']--;

if($_SESSION['cart'][$id]['qty'] <= 0){
unset($_SESSION['cart'][$id]);
}

}

}

header("Location: view_cart.php");