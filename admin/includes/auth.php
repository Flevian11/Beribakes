<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['user_id'])) {
    header("Location: " . ADMIN_URL . "login.php");
    exit;
}

// Get current admin/user info
if (isset($_SESSION['admin'])) {
    $current_user = $_SESSION['admin'];
} else {
    // Fetch user details from database if needed
    require_once __DIR__ . '/../../config/db.php';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}