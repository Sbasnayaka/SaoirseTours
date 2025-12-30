<?php
// DATABASE REPAIR SCRIPT
// Run this file to fix "Column not found" errors.

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo '<div style="font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto;">';
echo '<h1>Database Repair Utility</h1>';

try {
    // 1. Check Connection
    if ($pdo) {
        echo '<p style="color: green;">✅ Database Connected.</p>';
    }

    // 2. Define Missing Columns
    $columns = [
        'itinerary' => 'LONGTEXT DEFAULT NULL',
        'inclusions' => 'LONGTEXT DEFAULT NULL',
        'exclusions' => 'LONGTEXT DEFAULT NULL',
        'sidebar_settings' => 'LONGTEXT DEFAULT NULL',
        'display_order' => 'INT DEFAULT 0'
    ];

    $changes = 0;

    foreach ($columns as $name => $def) {
        // Check if exists
        $stmt = $pdo->query("SHOW COLUMNS FROM packages LIKE '$name'");
        if (!$stmt->fetch()) {
            // Add if missing
            $pdo->exec("ALTER TABLE packages ADD `$name` $def");
            echo "<p style='color: blue;'>➕ Added column: <strong>$name</strong></p>";
            $changes++;
        } else {
            echo "<p style='color: gray;'>✔ Column <strong>$name</strong> already exists.</p>";
        }
    }

    if ($changes > 0) {
        echo '<h2 style="color: green;">🎉 REPAIR COMPLETE! ' . $changes . ' columns added.</h2>';
        echo '<p>You can now use the Admin Panel safely.</p>';
    } else {
        echo '<h2 style="color: green;">✅ Database is already up to date. No changes needed.</h2>';
    }

    echo '<a href="admin/packages.php" style="display:inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;">Go to Admin Panel</a>';

} catch (Exception $e) {
    echo '<h2 style="color: red;">❌ Error: ' . $e->getMessage() . '</h2>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '</div>';
?>