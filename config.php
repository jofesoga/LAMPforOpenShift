<?php
// Database configuration
define('DB_HOST', 'mysql');
define('DB_USER', 'admin');
define('DB_PASS', 'txori4737');
define('DB_NAME', 'retail_store');

// Create connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Base URL
define('BASE_URL', 'http://localhost/');

// Include functions
require_once 'functions.php';
?>