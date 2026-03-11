<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__.'/../config/config.php';

define('ADMIN_URL', BASE_URL.'admin/');

/* ROUTER */

if(isset($_SESSION['admin'])){

header("Location: ".ADMIN_URL."dashboard.php");
exit;

}else{

header("Location: ".ADMIN_URL."login.php");
exit;

}