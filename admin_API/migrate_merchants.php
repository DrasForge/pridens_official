<?php
// admin_API/migrate_merchants.php
require_once 'db.php';

try {
    // 1. Master merchant store profile
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `merchants` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_code` VARCHAR(20) NOT NULL UNIQUE,
            `business_type` VARCHAR(50) NOT NULL,
            `business_name` VARCHAR(255) NOT NULL,
            `store_name` VARCHAR(255) NOT NULL,
            `store_slug` VARCHAR(255) NOT NULL UNIQUE,
            `store_category` VARCHAR(100) NOT NULL,
            `store_description` TEXT DEFAULT NULL,
            `store_logo` VARCHAR(255) DEFAULT NULL,
            `store_banner` VARCHAR(255) DEFAULT NULL,
            `business_tin` VARCHAR(50) DEFAULT NULL,
            `business_email` VARCHAR(255) NOT NULL,
            `business_contact` VARCHAR(30) NOT NULL,
            `opening_hours` TEXT DEFAULT NULL,
            `address_street` TEXT DEFAULT NULL,
            `address_barangay` VARCHAR(100) DEFAULT NULL,
            `address_city` VARCHAR(100) DEFAULT NULL,
            `address_province` VARCHAR(100) DEFAULT NULL,
            `latitude` DECIMAL(10,7) DEFAULT NULL,
            `longitude` DECIMAL(10,7) DEFAULT NULL,
            `bank_name` VARCHAR(100) DEFAULT NULL,
            `bank_account_name` VARCHAR(255) DEFAULT NULL,
            `bank_account_number` VARCHAR(50) DEFAULT NULL,
            `bank_account_type` ENUM('Savings','Current','E-Wallet') DEFAULT 'Savings',
            `status` ENUM('Pending','Active','Suspended','Rejected') DEFAULT 'Pending',
            `rejection_reason` TEXT DEFAULT NULL,
            `verified_by` INT DEFAULT NULL,
            `verified_at` DATETIME DEFAULT NULL,
            `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`verified_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
        )
    ");
    echo "✓ merchants table created\n";

    // 2. Owner / Representative info
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `merchant_owners` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_id` INT NOT NULL,
            `first_name` VARCHAR(100) NOT NULL,
            `last_name` VARCHAR(100) NOT NULL,
            `middle_name` VARCHAR(100) DEFAULT NULL,
            `birthdate` DATE DEFAULT NULL,
            `gender` VARCHAR(20) DEFAULT NULL,
            `nationality` VARCHAR(100) DEFAULT 'Filipino',
            `contact_number` VARCHAR(30) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `id_type` VARCHAR(100) DEFAULT NULL,
            `id_number` VARCHAR(100) DEFAULT NULL,
            `id_front_path` VARCHAR(255) DEFAULT NULL,
            `id_back_path` VARCHAR(255) DEFAULT NULL,
            FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ merchant_owners table created\n";

    // 3. Merchant login accounts
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `merchant_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_id` INT NOT NULL UNIQUE,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `last_login` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ merchant_users table created\n";

    // 4. Supporting business documents
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `merchant_documents` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_id` INT NOT NULL,
            `doc_type` VARCHAR(100) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ merchant_documents table created\n";

    echo "\nAll merchant tables created successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
