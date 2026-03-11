<?php

session_start();
require_once '../config/db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND role='admin'");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
die("Admin not found");
}

if(password_verify($password, $user['password'])){

$_SESSION['admin'] = $user;

header("Location: index.php");

exit;

}else{

echo "Invalid password";

}