<?php
// MIGRATION SCRIPT FORCE EXECUTION (Unique File)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<h1>Starting Migration...</h1>";

try {
    // 1. Add Columns
    $cols = [
        "ADD COLUMN itinerary LONGTEXT DEFAULT NULL",
        "ADD COLUMN inclusions LONGTEXT DEFAULT NULL",
        "ADD COLUMN exclusions LONGTEXT DEFAULT NULL",
        "ADD COLUMN sidebar_settings LONGTEXT DEFAULT NULL",
        "ADD COLUMN display_order INT DEFAULT 0"
    ];

    $sql = "ALTER TABLE packages " . implode(", ", $cols);
    $pdo->exec($sql);
    echo "<h2 style='color:green'>SUCCESS: All columns added.</h2>";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<h2 style='color:orange'>Columns already exist (Duplicate). Continuing...</h2>";
    } else {
        echo "<h2 style='color:red'>ERROR: " . $e->getMessage() . "</h2>";
    }
}

// 2. Verify
echo "<h3>Verifying Columns:</h3><ul>";
$stmt = $pdo->query("SHOW COLUMNS FROM packages");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<li>" . $row['Field'] . "</li>";
}
echo "</ul>";
?>