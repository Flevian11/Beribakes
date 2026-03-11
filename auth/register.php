<?php

session_start();
require_once '../config/db.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
header("Location: login.php");
exit;
}

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$password = $_POST['password'];

/* VALIDATION */

if(!$name || !$email || !$password){
$_SESSION['toast'] = "⚠ Please fill all required fields";
header("Location: login.php");
exit;
}

/* CHECK IF EMAIL EXISTS */

$stmt = $pdo->prepare("SELECT id FROM customers WHERE email=?");
$stmt->execute([$email]);

if($stmt->fetch()){
$_SESSION['toast'] = "⚠ Email already registered";
header("Location: login.php");
exit;
}

/* HASH PASSWORD */

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/* INSERT USER */

$stmt = $pdo->prepare(
"INSERT INTO customers(name,email,phone,password)
VALUES(?,?,?,?)"
);

$stmt->execute([$name,$email,$phone,$hashedPassword]);

/* LOGIN USER */

$_SESSION['customer'] = [
"id"=>$pdo->lastInsertId(),
"name"=>$name,
"email"=>$email
];

$_SESSION['toast'] = "✔ Account created successfully";

header("Location: ../index.php");
exit;