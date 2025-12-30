<?php
require_once 'includes/config.php';

// Safe Column Adder
function addColumnIfNotExists($pdo, $table, $column, $definition)
{
    try {
        // Check if exists
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->fetch()) {
            echo "Column <b>$column</b> already exists in $table.<br>";
        } else {
            $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
            echo "Added column <b>$column</b> to $table.<br>";
        }
    } catch (PDOException $e) {
        echo "Error adding $column: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Upgrading Packages Table (Final)...</h2>";

// 1. Itinerary (JSON storage for Day-by-Day plan)
addColumnIfNotExists($pdo, 'packages', 'itinerary', "LONGTEXT DEFAULT NULL COMMENT 'JSON: [{day_title, description}, ...]'");

// 2. Inclusions (JSON storage for list)
addColumnIfNotExists($pdo, 'packages', 'inclusions', "LONGTEXT DEFAULT NULL COMMENT 'JSON: [item1, item2...]'");

// 3. Exclusions (JSON storage for list)
addColumnIfNotExists($pdo, 'packages', 'exclusions', "LONGTEXT DEFAULT NULL COMMENT 'JSON: [item1, item2...]'");

// 4. Booking Sidebar Settings (JSON for toggles)
addColumnIfNotExists($pdo, 'packages', 'sidebar_settings', "LONGTEXT DEFAULT NULL COMMENT 'JSON: {show_phone, show_pax...}'");

// 5. Display Order (Sorting)
addColumnIfNotExists($pdo, 'packages', 'display_order', "INT DEFAULT 0");

echo "<br><b>Database Upgrade Complete!</b>";
?>