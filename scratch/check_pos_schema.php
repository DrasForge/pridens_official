<?php
require_once 'admin_API/db.php';

try {
    $tables = ['pos_products', 'pos_transaction_items'];
    foreach ($tables as $table) {
        echo "\nTable: $table\n";
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            echo "{$row['Field']} | {$row['Type']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
