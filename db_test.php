<?php
require_once 'includes/config.php';
echo "<h1>Database Connection Check</h1>";
try {
    $pdo->query("SELECT 1");
    echo "Connected successfully to database: " . DB_NAME;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>