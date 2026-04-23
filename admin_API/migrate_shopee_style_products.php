<?php
// admin_API/migrate_shopee_style_products.php
require_once 'db.php';

try {
    echo "Starting Shopee-style product migration...\n";

    // 1. Update merchant_products with logistical and branding fields
    $pdo->exec("
        ALTER TABLE `merchant_products`
        ADD COLUMN `brand` VARCHAR(100) DEFAULT NULL AFTER `name`,
        ADD COLUMN `weight_g` INT DEFAULT 0 AFTER `stock_quantity`,
        ADD COLUMN `length_cm` INT DEFAULT 0 AFTER `weight_g`,
        ADD COLUMN `width_cm` INT DEFAULT 0 AFTER `length_cm`,
        ADD COLUMN `height_cm` INT DEFAULT 0 AFTER `width_cm`,
        ADD COLUMN `has_variations` BOOLEAN DEFAULT FALSE AFTER `category`,
        ADD COLUMN `commission_percent` DECIMAL(5, 2) DEFAULT 0.00 AFTER `base_price`
    ");
    echo "✓ merchant_products table updated\n";

    // 2. Create product_images table (Gallery)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_images` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `image_path` VARCHAR(255) NOT NULL,
            `is_cover` BOOLEAN DEFAULT FALSE,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ product_images table created\n";

    // 3. Create product_variation_tiers (e.g., Color, Size)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_variation_tiers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `tier_index` TINYINT NOT NULL, -- 1 or 2
            FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ product_variation_tiers table created\n";

    // 4. Create product_variation_options (e.g., Red, Blue)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_variation_options` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `tier_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `image_path` VARCHAR(255) DEFAULT NULL, -- Image for specific variation (common for Tier 1)
            FOREIGN KEY (`tier_id`) REFERENCES `product_variation_tiers`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ product_variation_options table created\n";

    // 5. Create product_models (The Varied SKUs / Child Items)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_models` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `name` VARCHAR(255) NOT NULL, -- Auto-generated combined name like 'Red, Large'
            `variation_key` VARCHAR(255) NOT NULL, -- e.g. '0-1' (index of options)
            `sku` VARCHAR(100) DEFAULT NULL,
            `price` DECIMAL(15, 2) NOT NULL,
            `stock` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ product_models table created\n";

    // 6. Create product_wholesale_prices
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `product_wholesale_prices` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `min_quantity` INT NOT NULL,
            `unit_price` DECIMAL(15, 2) NOT NULL,
            FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ product_wholesale_prices table created\n";

    echo "\nShopee-style migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
