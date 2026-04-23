<?php
require_once 'admin_API/db.php';

try {
    echo "\n--- Tables List ---\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetchColumn()) {
        echo "- $row\n";
    }

    $tablesToCheck = ['subscribers', 'admins', 'subscription_plans', 'agents', 'merchants'];
    foreach ($tablesToCheck as $table) {
        echo "\n--- Table: $table ---\n";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            while ($row = $stmt->fetch()) {
                echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
            }
        } catch (Exception $e) {
            echo "Error describing $table: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
