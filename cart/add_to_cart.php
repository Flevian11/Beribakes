<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once '../config/db.php';

if(!isset($_SESSION['customer'])){
header("Location: ../auth/login.php");
exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$product){
exit("Product not found");
}

/* CART */

$cart = $_SESSION['cart'] ?? [];

if(isset($cart[$id])){
$cart[$id]['qty']++;
}else{

$cart[$id] = [
"name"=>$product['product_name'],
"price"=>$product['price'],
"image"=>$product['image'],
"qty"=>1
];

}

$_SESSION['cart'] = $cart;

/* TOAST MESSAGE */

$_SESSION['toast'] = "✔ ".$product['product_name']." added to cart";

header("Location: ".$_SERVER['HTTP_REFERER']);
exit;