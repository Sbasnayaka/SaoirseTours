<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";
echo "<p>Checking connection to MySQL...</p>";

$host = 'localhost';
$dbname = 'tourism_cms';
$username = 'root';
$password = '';

// TRY PORT 3307
echo "<h3>Attempt 1: Port 3307</h3>";
try {
    $pdo = new PDO("mysql:host=$host;port=3307;dbname=$dbname", $username, $password);
    echo "<p style='color:green; font-weight:bold;'>SUCCESS! Connected on Port 3307</p>";
    $pdo = null;
} catch (PDOException $e) {
    echo "<p style='color:red;'>Failed on 3307: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// TRY PORT 3306
echo "<h3>Attempt 2: Port 3306</h3>";
try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname", $username, $password);
    echo "<p style='color:green; font-weight:bold;'>SUCCESS! Connected on Port 3306</p>";
    $pdo = null;
} catch (PDOException $e) {
    echo "<p style='color:red;'>Failed on 3306: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// TRY NO PORT (Default)
echo "<h3>Attempt 3: No Port (Default)</h3>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "<p style='color:green; font-weight:bold;'>SUCCESS! Connected on Default Port</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Failed on Default: " . $e->getMessage() . "</p>";
}
?>