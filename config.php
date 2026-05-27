<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gateflow_db');
define('DB_USER', 'gateflow_app');
define('DB_PASS', getenv('GATEFLOW_DB_PASSWORD'));

define('AIRLINE_CODE', 'GF');
define('KIOSK_VERSION', '3.2.1');

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('System temporarily unavailable');
}