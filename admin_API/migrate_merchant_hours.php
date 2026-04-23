<?php
// admin_API/migrate_merchant_hours.php
require_once 'db.php';

try {
    echo "Starting Merchant Operating Hours migration...\n";

    $sql = "CREATE TABLE IF NOT EXISTS `merchant_operating_hours` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
        `is_open` BOOLEAN DEFAULT TRUE,
        `open_time` TIME DEFAULT '08:00:00',
        `close_time` TIME DEFAULT '20:00:00',
        UNIQUE KEY `merchant_day` (`merchant_id`, `day_of_week`),
        FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✓ merchant_operating_hours table created\n";

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
