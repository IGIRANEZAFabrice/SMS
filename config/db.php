<?php
// Initialize session for authentication state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection Configuration
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'stock';

// Create connection
$conn = @new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Unable to connect to database. Please contact support.');
}

// Set charset to utf8mb4 for better character support
$conn->set_charset('utf8mb4');

// Role constants
define('ROLE_ADMIN', 1);
define('ROLE_USER', 2);
?>
