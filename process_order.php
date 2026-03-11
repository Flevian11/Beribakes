<?php

/* SAFE SESSION */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db.php';
require_once 'config/config.php';

/* VALIDATE CART */

$cart = $_SESSION['cart'] ?? [];

if(empty($cart)){
header("Location: ".BASE_URL."products/shop.php");
exit;
}

/* CUSTOMER DATA */

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);
$payment = $_POST['payment'];

/* VALIDATION */

if(!$name || !$phone || !$address){
$_SESSION['toast'] = "⚠ Missing required fields";
header("Location: checkout.php");
exit;
}

/* SAVE / FIND CUSTOMER */

$stmt = $pdo->prepare("SELECT id FROM customers WHERE email=?");
$stmt->execute([$email]);

$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$customer){

$stmt = $pdo->prepare(
"INSERT INTO customers(name,email,phone)
VALUES(?,?,?)"
);

$stmt->execute([$name,$email,$phone]);

$customer_id = $pdo->lastInsertId();

}else{

$customer_id = $customer['id'];

}

/* CALCULATE TOTAL */

$total = 0;

foreach($cart as $item){

$total += $item['qty'] * $item['price'];

}

/* CREATE ORDER */

$stmt = $pdo->prepare(
"INSERT INTO orders(customer_id,total_amount,order_status)
VALUES(?,?,?)"
);

$stmt->execute([
$customer_id,
$total,
'pending'
]);

$order_id = $pdo->lastInsertId();

/* INSERT ORDER ITEMS */

$stmt = $pdo->prepare(
"INSERT INTO order_items(order_id,product_id,quantity,price)
VALUES(?,?,?,?)"
);

foreach($cart as $id=>$item){

$stmt->execute([
$order_id,
$id,
$item['qty'],
$item['price']
]);

}

/* PAYMENT SIMULATION */

if($payment === "mpesa"){

$_SESSION['toast'] =
"✔ Order placed. Simulated M-Pesa payment request sent.";

}else{

$_SESSION['toast'] =
"✔ Order placed. Pay on delivery.";

}

/* CLEAR CART */

unset($_SESSION['cart']);

/* REDIRECT */

header("Location: order_success.php?order=".$order_id);
exit;