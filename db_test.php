<?php
// MENU DEBUGGER
require_once 'includes/config.php';
$stmt = $pdo->query("SELECT * FROM menu_items WHERE menu_id = 1");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($items);
echo "</pre>";
?>