<?php
// admin_API/alter_services_v3.php
require_once 'db.php';

try {
    echo "Updating Services table with Exclusivity features...\n";

    $sql = "ALTER TABLE merchant_services 
            ADD COLUMN is_exclusive TINYINT(1) DEFAULT 0,
            ADD COLUMN exclusive_percentage DECIMAL(5,2) DEFAULT 0.00;";
    
    $pdo->exec($sql);
    echo "✓ merchant_services table updated\n";
} catch (PDOException $e) {
    die("Alter failed: " . $e->getMessage() . "\n");
}
?>
