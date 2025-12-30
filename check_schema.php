<?php
require_once 'includes/config.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM packages");
    echo "<h3>Columns in 'packages' table:</h3><ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>