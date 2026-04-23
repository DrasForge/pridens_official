<?php
// admin_API/migrate_food_panda_style.php
require_once 'db.php';

try {
    echo "Upgrading database for FoodPanda-style management...\n";

    // 1. Differentiate custom categories
    $pdo->exec("ALTER TABLE `merchant_custom_categories` ADD COLUMN `general_type` ENUM('Products', 'Foods', 'Spots', 'Services') DEFAULT 'Products' AFTER `merchant_id` ");
    echo "✓ general_type added to merchant_custom_categories\n";

    // 2. Add Modifier Groups (e.g., "Select your drink", "Extra toppings")
    $sqlGroups = "CREATE TABLE IF NOT EXISTS `product_menu_modifier_groups` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `min_selection` INT DEFAULT 0,
        `max_selection` INT DEFAULT 1,
        `is_required` BOOLEAN DEFAULT FALSE,
        `sort_order` INT DEFAULT 0,
        FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE
    )";
    $pdo->exec($sqlGroups);
    echo "✓ product_menu_modifier_groups created\n";

    // 3. Add Modifier Options (e.g., "Coke", "Extra Rice")
    $sqlOptions = "CREATE TABLE IF NOT EXISTS `product_menu_modifier_options` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `group_id` INT NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `extra_price` DECIMAL(15, 2) DEFAULT 0.00,
        `is_available` BOOLEAN DEFAULT TRUE,
        `sort_order` INT DEFAULT 0,
        FOREIGN KEY (`group_id`) REFERENCES `product_menu_modifier_groups`(`id`) ON DELETE CASCADE
    )";
    $pdo->exec($sqlOptions);
    echo "✓ product_menu_modifier_options created\n";

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration note: " . $e->getMessage() . "\n";
}
?>
