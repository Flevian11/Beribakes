<?php
// First include the main config
require_once __DIR__ . '/../../config/config.php';

// Define admin constants if not already defined
if (!defined('ADMIN_URL')) {
    // Get the base URL and ensure it ends with a slash
    $base_url = defined('BASE_URL') ? BASE_URL : '/beribakes/';
    define('ADMIN_URL', rtrim($base_url, '/') . '/admin/');
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'BeriBakes Bakery');
}

// You can add more admin-specific constants here
define('ADMIN_ASSETS', ADMIN_URL . 'assets/');
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('PRODUCT_IMAGE_URL', UPLOAD_URL . 'products/');