<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', '127.0.0.1:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'LostFoundSystem');

// Upload directory — adjust folder name if your project folder is different
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'http://localhost/lostfound/uploads/');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("<h3 style='color:red'>DB Error: " . $conn->connect_error . "</h3>");
    }
    $conn->set_charset("utf8");
    return $conn;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
