<?php
// admin_API/upgrade_spots_v2.php
require_once 'db.php';

try {
    echo "Upgrading Spots system for editability...\n";
    
    // Add slot_index to track specific media positions (0-5 for pics, 0-1 for vids)
    $pdo->exec("ALTER TABLE merchant_spot_media ADD COLUMN slot_index INT NOT NULL DEFAULT 0 AFTER media_type");
    
    echo "✓ slot_index added to merchant_spot_media\n";
    echo "Upgrade complete!\n";
} catch (PDOException $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
?>
