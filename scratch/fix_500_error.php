<?php
require_once 'admin_API/db.php';

try {
    echo "Adding missing insurance columns to subscribers table...\n";
    
    $queries = [
        "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_policy_number VARCHAR(100) DEFAULT NULL AFTER insurance_status",
        "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_document_path VARCHAR(255) DEFAULT NULL AFTER insurance_policy_number",
        "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_ready_for_approval BOOLEAN DEFAULT FALSE AFTER insurance_document_path"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "Executed: " . substr($sql, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "Skipped or Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
?>
