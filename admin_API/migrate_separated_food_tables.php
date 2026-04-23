<?php
// admin_API/migrate_separated_food_tables.php
require_once 'db.php';

try {
    echo "Creating fully separated database tables for Food items...\n";

    // 1. Food Categories
    $sqlCategories = "CREATE TABLE IF NOT EXISTS `merchant_food_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `image_path` VARCHAR(255) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlCategories);
    echo "✓ merchant_food_categories created\n";

    // 2. Food Items
    $sqlFoods = "CREATE TABLE IF NOT EXISTS `merchant_foods` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `food_category_id` INT DEFAULT NULL,
        `name` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `base_price` DECIMAL(15, 2) NOT NULL,
        `discount_price` DECIMAL(15, 2) DEFAULT NULL,
        `image_path` VARCHAR(255) DEFAULT NULL,
        `prep_time_mins` INT DEFAULT 15,
        `serving_size` VARCHAR(100) DEFAULT NULL,
        `spicy_level` INT DEFAULT 0,
        `is_best_seller` TINYINT(1) DEFAULT 0,
        `is_new` TINYINT(1) DEFAULT 0,
        `is_vegan` TINYINT(1) DEFAULT 0,
        `is_halal` TINYINT(1) DEFAULT 0,
        `status` ENUM('Available', 'Out of Stock', 'Disabled') DEFAULT 'Available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`food_category_id`) REFERENCES `merchant_food_categories`(`id`) ON DELETE SET NULL
    )";
    $pdo->exec($sqlFoods);
    echo "✓ merchant_foods created\n";

    // 3. Food Modifiers (Groups)
    $sqlGroups = "CREATE TABLE IF NOT EXISTS `merchant_food_modifier_groups` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `food_id` INT NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `min_selection` INT DEFAULT 0,
        `max_selection` INT DEFAULT 1,
        `is_required` TINYINT(1) DEFAULT 0,
        `sort_order` INT DEFAULT 0,
        FOREIGN KEY (`food_id`) REFERENCES `merchant_foods`(`id`) ON DELETE CASCADE
    )";
    $pdo->exec($sqlGroups);
    echo "✓ merchant_food_modifier_groups created\n";

    // 4. Food Modifiers (Options)
    $sqlOptions = "CREATE TABLE IF NOT EXISTS `merchant_food_modifier_options` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `group_id` INT NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `extra_price` DECIMAL(15, 2) DEFAULT 0.00,
        `is_available` TINYINT(1) DEFAULT 1,
        `sort_order` INT DEFAULT 0,
        FOREIGN KEY (`group_id`) REFERENCES `merchant_food_modifier_groups`(`id`) ON DELETE CASCADE
    )";
    $pdo->exec($sqlOptions);
    echo "✓ merchant_food_modifier_options created\n";

    // 5. Food Orders
    $sqlOrders = "CREATE TABLE IF NOT EXISTS `merchant_food_orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `subscriber_id` INT NOT NULL,
        `status` ENUM('Pending', 'Preparing', 'Ready', 'Delivered', 'Cancelled') DEFAULT 'Pending',
        `total_amount` DECIMAL(15, 2) NOT NULL,
        `payment_channel_id` INT DEFAULT NULL,
        `prep_notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlOrders);
    echo "✓ merchant_food_orders created\n";

    // 6. Food Order Items
    $sqlOrderItems = "CREATE TABLE IF NOT EXISTS `merchant_food_order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `food_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `price` DECIMAL(15, 2) NOT NULL,
        `modifiers_json` JSON DEFAULT NULL,
        FOREIGN KEY (`order_id`) REFERENCES `merchant_food_orders`(`id`) ON DELETE CASCADE
    )";
    $pdo->exec($sqlOrderItems);
    echo "✓ merchant_food_order_items created\n";

    echo "Migration completed successfully! Food data is now physically separated from Product data.\n";
} catch (PDOException $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
?>
