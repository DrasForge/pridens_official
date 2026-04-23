<?php
// admin_API/migrate_custom_categories.php
require_once 'db.php';

try {
    echo "Starting Custom Categories migration...\n";

    // 1. Create merchant_custom_categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `merchant_custom_categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `image_path` VARCHAR(255) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ merchant_custom_categories table created\n";

    // 2. Add custom_category_id to merchant_products
    // We check if it exists first to avoid errors on re-run
    $stmt = $pdo->query("SHOW COLUMNS FROM `merchant_products` LIKE 'custom_category_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `merchant_products` ADD COLUMN `custom_category_id` INT DEFAULT NULL AFTER `category` ");
        $pdo->exec("ALTER TABLE `merchant_products` ADD FOREIGN KEY (`custom_category_id`) REFERENCES `merchant_custom_categories`(`id`) ON DELETE SET NULL");
        echo "✓ merchant_products table updated with custom_category_id\n";
    } else {
        echo "i custom_category_id already exists in merchant_products\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
