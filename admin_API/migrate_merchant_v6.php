<?php
// admin_API/migrate_merchant_v6.php
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE merchants 
        ADD COLUMN IF NOT EXISTS profile_last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");
    echo "✓ merchants table upgraded with profile_last_updated\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
