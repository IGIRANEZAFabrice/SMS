<?php
/**
 * Database Connection Configuration
 * For InfinityFree Hosting
 */

// Database credentials - Update these for different environments
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'workshop';

// Enable error reporting for debugging (disable in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Create connection
$conn = @new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    // Log error instead of displaying it
    error_log("Database connection failed: " . $conn->connect_error);
    die("Unable to connect to database. Please contact support.");
}

// Set charset to utf8mb4 for better character support
$conn->set_charset("utf8mb4");

// Optional: Set timezone
// $conn->query("SET time_zone = '+00:00'");

?>

