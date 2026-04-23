<?php
// admin_API/migrate_inclusions.php
require_once 'db.php';

try {
    echo "Starting Inclusions migration...\n";

    // 1. Add inclusions column to merchant_products
    $stmt = $pdo->query("SHOW COLUMNS FROM `merchant_products` LIKE 'inclusions'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `merchant_products` ADD COLUMN `inclusions` TEXT DEFAULT NULL AFTER `video_path` ");
        echo "✓ merchant_products updated (inclusions column added)\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
