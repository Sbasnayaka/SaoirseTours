<?php
// Database Configuration
$host = 'localhost';
$dbname = 'tourism_cms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Site URL Configuration (Auto-detect)
// You can manually set this if needed, e.g., 'http://localhost/SaoirseTours/'
$site_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/";

// Basic Error Reporting (Turn off for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>