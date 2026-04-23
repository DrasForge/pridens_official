<?php
// admin_API/alter_services_v2.php
require_once 'db.php';

try {
    echo "Updating Services table with methods...\n";

    $sql = "ALTER TABLE merchant_services 
            ADD COLUMN service_method VARCHAR(255) DEFAULT 'In-Store', 
            ADD COLUMN payment_methods VARCHAR(255) DEFAULT 'Online';";
    
    $pdo->exec($sql);
    echo "✓ merchant_services table updated\n";
} catch (PDOException $e) {
    die("Alter failed: " . $e->getMessage() . "\n");
}
?>
