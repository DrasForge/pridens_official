<?php
$host = 'localhost';
$db   = 'pridens_official';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Add columns to subscription_plans
    $sql = "ALTER TABLE `subscription_plans` 
            ADD COLUMN `residual_l1` DECIMAL(15, 2) DEFAULT 0.00 AFTER `monthly_collection_fee`,
            ADD COLUMN `residual_l4` DECIMAL(15, 2) DEFAULT 0.00 AFTER `residual_l3`,
            ADD COLUMN `climbs_insurance_premium` DECIMAL(15, 2) DEFAULT 0.00 AFTER `residual_l4`,
            ADD COLUMN `post_term_sku` VARCHAR(100) DEFAULT NULL AFTER `post_term_payment_cycle`,
            ADD COLUMN `post_term_fee` DECIMAL(15, 2) DEFAULT 0.00 AFTER `post_term_sku`,
            ADD COLUMN `card_theme` TEXT DEFAULT NULL AFTER `status`
    ";
    
    $pdo->exec($sql);
    echo "Migration successful: Columns added to subscription_plans.\n";

    // Modify plan_insurance_benefits enum to varchar
    $pdo->exec("ALTER TABLE `plan_insurance_benefits` MODIFY COLUMN `benefit_name` VARCHAR(255) NOT NULL");
    echo "Migration successful: plan_insurance_benefits.benefit_name changed to VARCHAR.\n";

} catch (\PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
