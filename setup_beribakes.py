import os

base = r"C:\xampp\htdocs\beribakes"

folders = [
"config",
"includes",
"auth",
"admin",
"products",
"cart",
"orders",
"reports",
"assets/css",
"assets/js"
]

files = {

"index.php": """<?php include 'includes/header.php'; ?>
<h1>Welcome to BeriBakes Bakery</h1>
<p>Fresh bread, cakes and pastries made daily.</p>
<a href="products/shop.php">Shop Now</a>
<?php include 'includes/footer.php'; ?>
""",

"includes/header.php": """<!DOCTYPE html>
<html>
<head>
<title>BeriBakes Bakery</title>
<link rel="stylesheet" href="/beribakes/assets/css/style.css">
</head>
<body>
<nav>
<a href="/beribakes">Home</a> |
<a href="/beribakes/products/shop.php">Shop</a> |
<a href="/beribakes/cart/view_cart.php">Cart</a> |
<a href="/beribakes/auth/login.php">Admin</a>
</nav>
<hr>
""",

"includes/footer.php": """
<hr>
<footer>
<p>BeriBakes Bakery Management System</p>
</footer>
</body>
</html>
""",

"config/db.php": """<?php
$conn = mysqli_connect("localhost","root","","beribakes_db");
if(!$conn){ die("DB connection failed"); }
?>""",

"assets/css/style.css": """
body{font-family:Arial;margin:40px;}
nav a{margin-right:10px;}
.product{border:1px solid #ccc;padding:10px;margin:10px;}
button{padding:6px 12px;}
""",

"products/shop.php": """<?php include '../includes/header.php'; ?>
<h2>Bakery Products</h2>

<div class="product">
<h3>Chocolate Cake</h3>
<p>Price: KES 800</p>
<a href="../cart/add_to_cart.php?id=1">Add to Cart</a>
</div>

<div class="product">
<h3>Fresh Bread</h3>
<p>Price: KES 150</p>
<a href="../cart/add_to_cart.php?id=2">Add to Cart</a>
</div>

<div class="product">
<h3>Croissant</h3>
<p>Price: KES 200</p>
<a href="../cart/add_to_cart.php?id=3">Add to Cart</a>
</div>

<?php include '../includes/footer.php'; ?>
""",

"cart/add_to_cart.php": """<?php
session_start();
$id=$_GET['id'];

$_SESSION['cart'][]=$id;

header("Location:view_cart.php");
?>""",

"cart/view_cart.php": """<?php
session_start();
include '../includes/header.php';

echo "<h2>Your Cart</h2>";

if(empty($_SESSION['cart'])){
echo "Cart is empty";
}else{

foreach($_SESSION['cart'] as $item){
echo "Product ID: ".$item."<br>";
}

echo "<br><a href='checkout.php'>Checkout</a>";
}

include '../includes/footer.php';
?>""",

"cart/checkout.php": """<?php
session_start();
include '../includes/header.php';

echo "<h2>Checkout</h2>";

if(empty($_SESSION['cart'])){
echo "Cart empty";
}else{
echo "Order placed successfully!";
unset($_SESSION['cart']);
}

include '../includes/footer.php';
?>""",

"auth/login.php": """<?php include '../includes/header.php'; ?>
<h2>Admin Login</h2>
<form method="post" action="process_login.php">
<input type="text" name="email" placeholder="Email"><br><br>
<input type="password" name="password" placeholder="Password"><br><br>
<button type="submit">Login</button>
</form>
<?php include '../includes/footer.php'; ?>
""",

"auth/process_login.php": """<?php
session_start();
$email=$_POST['email'];
$password=$_POST['password'];

if($email=="admin@beribakes.com" && $password=="admin123"){
$_SESSION['admin']=true;
header("Location:../admin/dashboard.php");
}else{
echo "Login failed";
}
?>""",

"admin/dashboard.php": """<?php
session_start();
if(!isset($_SESSION['admin'])){ header("Location:../auth/login.php"); }
include '../includes/header.php';
?>

<h1>Admin Dashboard</h1>

<ul>
<li><a href="../products/manage_products.php">Manage Products</a></li>
<li><a href="../orders/manage_orders.php">Manage Orders</a></li>
<li><a href="../reports/sales_report.php">Sales Report</a></li>
</ul>

<?php include '../includes/footer.php'; ?>
""",

"products/manage_products.php": """<?php include '../includes/header.php'; ?>
<h2>Manage Products</h2>
<p>Product management page</p>
<?php include '../includes/footer.php'; ?>
""",

"orders/manage_orders.php": """<?php include '../includes/header.php'; ?>
<h2>Manage Orders</h2>
<p>Orders list</p>
<?php include '../includes/footer.php'; ?>
""",

"reports/sales_report.php": """<?php include '../includes/header.php'; ?>
<h2>Sales Report</h2>
<p>Daily sales summary</p>
<?php include '../includes/footer.php'; ?>
"""
}

os.makedirs(base,exist_ok=True)

for folder in folders:
    os.makedirs(os.path.join(base,folder),exist_ok=True)

for path,content in files.items():
    full=os.path.join(base,path)
    os.makedirs(os.path.dirname(full),exist_ok=True)
    with open(full,"w",encoding="utf-8") as f:
        f.write(content)

print("BeriBakes system structure created successfully!")