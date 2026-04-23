<?php
// admin_API/migrate_merchant_v5.php
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE merchants 
        ADD COLUMN IF NOT EXISTS business_email_2 VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS business_email_3 VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS business_contact_2 VARCHAR(30) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS business_contact_3 VARCHAR(30) DEFAULT NULL
    ");
    echo "✓ merchants table upgraded with extra contacts\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
