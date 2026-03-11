<?php

session_start();
require_once '../config/db.php';

$provider = $_GET['provider'] ?? 'google';

/*
Mock OAuth user
*/

$email = $provider."_user".rand(1000,9999)."@example.com";
$name = ucfirst($provider)." User";

/* CHECK IF USER EXISTS */

$stmt = $pdo->prepare("SELECT * FROM customers WHERE email=?");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){

$stmt = $pdo->prepare(
"INSERT INTO customers(name,email,phone,password)
VALUES(?,?,?,?)"
);

$stmt->execute([
$name,
$email,
"",
password_hash(rand(10000,99999), PASSWORD_DEFAULT)
]);

$user = [
"id"=>$pdo->lastInsertId(),
"name"=>$name,
"email"=>$email
];

}

/* LOGIN USER */

$_SESSION['customer'] = $user;

$_SESSION['toast'] = "✔ Logged in with ".ucfirst($provider);

header("Location: ../index.php");
exit;