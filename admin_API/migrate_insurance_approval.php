<?php
// admin_API/migrate_insurance_approval.php
require_once 'db.php';

try {
    echo "Starting migration...\n";

    // 1. Add fields one by one to avoid dependency on MySQL 8.0.19+ IF NOT EXISTS for columns
    $columnsToAdd = [
        'insurance_policy_number' => "VARCHAR(100) DEFAULT NULL AFTER insurance_status",
        'insurance_document_path' => "VARCHAR(255) DEFAULT NULL AFTER insurance_policy_number",
        'insurance_ready_for_approval' => "BOOLEAN DEFAULT FALSE AFTER insurance_document_path"
    ];

    foreach ($columnsToAdd as $col => $definition) {
        $checkCol = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscribers' AND COLUMN_NAME = ?");
        $checkCol->execute([$col]);
        if (!$checkCol->fetch()) {
            $pdo->exec("ALTER TABLE subscribers ADD COLUMN $col $definition");
            echo "Added column: $col\n";
        } else {
            echo "Column $col already exists.\n";
        }
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
