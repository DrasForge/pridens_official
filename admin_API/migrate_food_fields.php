<?php
// admin_API/migrate_food_fields.php
require_once 'db.php';

try {
    echo "Adding Food-specific fields to merchant_products...\n";

    $pdo->exec("ALTER TABLE `merchant_products` ADD COLUMN `prep_time_mins` INT DEFAULT 0 AFTER `has_variations` ");
    echo "✓ prep_time_mins added\n";

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration info: " . $e->getMessage() . "\n";
}
?>
