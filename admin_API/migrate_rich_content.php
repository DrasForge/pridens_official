<?php
// admin_API/migrate_rich_content.php
require_once 'db.php';

try {
    echo "Starting Rich Content & Video migration...\n";

    // 1. Upgrade description column and add video_path
    $pdo->exec("ALTER TABLE `merchant_products` 
                MODIFY COLUMN `description` MEDIUMTEXT,
                ADD COLUMN `video_path` VARCHAR(255) DEFAULT NULL AFTER `image_path` ");
    
    echo "✓ merchant_products updated (description upgraded to MEDIUMTEXT, video_path added)\n";

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
