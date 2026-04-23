<?php
// diag_db.php - Open this in your browser: http://localhost:8001/diag_db.php
require_once 'admin_API/db.php';

header('Content-Type: text/plain');

echo "--- Database Diagnostics ---\n";
echo "Host: " . $host . "\n";
echo "Database: " . $db . "\n";
echo "User: " . $user . "\n";

try {
    echo "\n--- Tables in " . $db . " ---\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetchColumn()) {
        echo "- $row\n";
    }

    if (in_array('pos_products', $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
        echo "\n--- Columns in pos_products ---\n";
        $stmt = $pdo->query("DESCRIBE pos_products");
        while ($row = $stmt->fetch()) {
            echo "{$row['Field']} | {$row['Type']}\n";
        }
    } else {
        echo "\nERROR: pos_products table not found in " . $db . "\n";
    }

} catch (Exception $e) {
    echo "Connection Error: " . $e->getMessage() . "\n";
}
?>
