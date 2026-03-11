<?php

$env = parse_ini_file(__DIR__ . '/../.env');

define('APP_NAME', $env['APP_NAME']);
define('BASE_URL', $env['BASE_URL']);

define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);