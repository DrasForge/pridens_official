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
    
    // 1. Add SKU column if not exists
    $pdo->exec("ALTER TABLE pos_products ADD COLUMN sku VARCHAR(50) UNIQUE AFTER id");
    echo "SKU column added to pos_products\n";
    
    // 2. Update existing products with SKUs
    $pdo->exec("UPDATE pos_products SET sku = 'APP-PROM-01' WHERE name = 'Agent Promotion Application Fee'");
    $pdo->exec("UPDATE pos_products SET sku = 'SUB-POST-01' WHERE name = 'Basic After 3 Years'");
    $pdo->exec("UPDATE pos_products SET sku = 'SUB-MON-01' WHERE name = 'Basic Monthly Subscription'");
    $pdo->exec("UPDATE pos_products SET sku = 'SUB-PLAN-01' WHERE name = 'Basic Plan Subscription'");
    $pdo->exec("UPDATE pos_products SET sku = 'APP-REG-01' WHERE name = 'New Agent Registration Fee'");
    echo "Existing products updated with SKUs\n";
    
    // 3. Create Subscription Plans Master Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `subscription_plans` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `plan_name` VARCHAR(255) NOT NULL,
        `insurance_coverage` ENUM('Individual', '2 persons', '3 individuals', 'Family') NOT NULL,
        `payment_terms` ENUM('Every Month', 'Every Quarter', 'Every Semi-Annual', 'Annually') NOT NULL,
        `subscription_term_months` INT NOT NULL,
        `insurance_term_months` INT NOT NULL,
        `quota_weight` DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
        `requires_beneficiaries` BOOLEAN DEFAULT TRUE,
        `onboarding_product_id` INT NOT NULL,
        `monthly_product_id` INT NOT NULL,
        `post_term_enabled` BOOLEAN DEFAULT FALSE,
        `post_term_product_id` INT DEFAULT NULL,
        `post_term_payment_cycle` ENUM('Every Month', 'Every Quarter', 'Every Semi-Annual', 'Annually') DEFAULT NULL,
        `otc_l1` DECIMAL(15, 2) DEFAULT 0.00,
        `otc_l2` DECIMAL(15, 2) DEFAULT 0.00,
        `otc_l3` DECIMAL(15, 2) DEFAULT 0.00,
        `otc_l4` DECIMAL(15, 2) DEFAULT 0.00,
        `otc_l5` DECIMAL(15, 2) DEFAULT 0.00,
        `monthly_pvoucher` DECIMAL(15, 2) DEFAULT 0.00,
        `monthly_merchant_handling` DECIMAL(15, 2) DEFAULT 0.00,
        `monthly_points_rewards` DECIMAL(15, 2) DEFAULT 0.00,
        `monthly_collection_fee` DECIMAL(15, 2) DEFAULT 0.00,
        `residual_l2` DECIMAL(15, 2) DEFAULT 0.00,
        `residual_l3` DECIMAL(15, 2) DEFAULT 0.00,
        `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`onboarding_product_id`) REFERENCES `pos_products`(`id`),
        FOREIGN KEY (`monthly_product_id`) REFERENCES `pos_products`(`id`),
        FOREIGN KEY (`post_term_product_id`) REFERENCES `pos_products`(`id`)
    )");
    echo "subscription_plans table created\n";
    
    // 4. Position-Based Residuals
    $pdo->exec("CREATE TABLE IF NOT EXISTS `plan_position_residuals` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `plan_id` INT NOT NULL,
        `rank_name` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
    )");
    echo "plan_position_residuals table created\n";
    
    // 5. Insurance Benefits
    $pdo->exec("CREATE TABLE IF NOT EXISTS `plan_insurance_benefits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `plan_id` INT NOT NULL,
        `benefit_name` ENUM('Accidental Benefit', 'Life Benefit', 'Burial Expense') NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `requires_contestability` BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
    )");
    echo "plan_insurance_benefits table created\n";
    
    // 6. Claim Requirements
    $pdo->exec("CREATE TABLE IF NOT EXISTS `plan_claim_requirements` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `plan_id` INT NOT NULL,
        `document_name` VARCHAR(255) NOT NULL,
        FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
    )");
    echo "plan_claim_requirements table created\n";
    
    // 7. Billing Brackets
    $pdo->exec("CREATE TABLE IF NOT EXISTS `plan_billing_brackets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `plan_id` INT NOT NULL,
        `approval_from_day` INT NOT NULL,
        `approval_to_day` INT NOT NULL,
        `bill_on_day` INT NOT NULL,
        FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
    )");
    echo "plan_billing_brackets table created\n";

} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
