<?php

session_start();
require_once '../config/db.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
header("Location: login.php");
exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

/* FIND USER */

$stmt = $pdo->prepare("SELECT * FROM customers WHERE email=?");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){

$_SESSION['toast'] = "⚠ Invalid email or password";
header("Location: login.php");
exit;

}

/* VERIFY PASSWORD */

if(!password_verify($password,$user['password'])){

$_SESSION['toast'] = "⚠ Invalid email or password";
header("Location: login.php");
exit;

}

/* LOGIN */

$_SESSION['customer'] = [
"id"=>$user['id'],
"name"=>$user['name'],
"email"=>$user['email']
];

$_SESSION['toast'] = "✔ Welcome back ".$user['name'];

header("Location: ../index.php");
exit;