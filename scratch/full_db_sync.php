<?php
require_once 'admin_API/db.php';

try {
    echo "Applying comprehensive schema fixes to live database...\n";

    // 1. Add missing columns to 'subscribers' that we didn't add in Phase 1
    $subscriberCols = [
        "approved_by INT DEFAULT NULL",
        "approved_at TIMESTAMP NULL DEFAULT NULL",
        "insurance_approved_by INT DEFAULT NULL",
        "insurance_approved_at TIMESTAMP NULL DEFAULT NULL"
    ];
    foreach ($subscriberCols as $col) {
        list($cName) = explode(" ", $col);
        try {
            $pdo->exec("ALTER TABLE subscribers ADD COLUMN $col");
            echo "Added to subscribers: $cName\n";
        } catch (Exception $e) {}
    }

    // 2. Add missing columns to 'admins'
    $adminCols = [
        "first_name VARCHAR(100) DEFAULT 'Admin'",
        "last_name VARCHAR(100) DEFAULT 'User'"
    ];
    foreach ($adminCols as $col) {
        list($cName) = explode(" ", $col);
        try {
            $pdo->exec("ALTER TABLE admins ADD COLUMN $col");
            echo "Added to admins: $cName\n";
        } catch (Exception $e) {}
    }

    // 3. Add missing columns to 'agents'
    $agentCols = [
        "middle_name VARCHAR(100) DEFAULT NULL",
        "suffix VARCHAR(20) DEFAULT NULL",
        "birthdate DATE DEFAULT NULL",
        "gender VARCHAR(10) DEFAULT NULL",
        "marital_status VARCHAR(20) DEFAULT NULL",
        "occupation VARCHAR(100) DEFAULT NULL",
        "source_of_income VARCHAR(100) DEFAULT NULL",
        "phone VARCHAR(20) DEFAULT NULL",
        "address TEXT DEFAULT NULL",
        "province VARCHAR(100) DEFAULT NULL",
        "city VARCHAR(100) DEFAULT NULL",
        "barangay VARCHAR(100) DEFAULT NULL",
        "agent_position VARCHAR(50) DEFAULT 'Sales Agent'",
        "referral_code VARCHAR(20) DEFAULT NULL",
        "rank_id INT DEFAULT NULL",
        "rank_promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        "team_id INT DEFAULT NULL",
        "payment_status ENUM('unpaid', 'paid') DEFAULT 'paid'"
    ];
    foreach ($agentCols as $col) {
        list($cName) = explode(" ", $col);
        try {
            $pdo->exec("ALTER TABLE agents ADD COLUMN $col");
            echo "Added to agents: $cName\n";
        } catch (Exception $e) {}
    }

    // 4. Create missing tables (Merchants, Commissions, etc.)
    // We can just run the CREATE TABLE IF NOT EXISTS for each
    $missingTables = [
        "CREATE TABLE IF NOT EXISTS `merchants` (
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
            `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `merchant_owners` (
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
            `id_back_path` VARCHAR(255) DEFAULT NULL
        )",
        "CREATE TABLE IF NOT EXISTS `merchant_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_id` INT NOT NULL UNIQUE,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `last_login` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `merchant_documents` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_id` INT NOT NULL,
            `doc_type` VARCHAR(100) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `agent_commissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `agent_id` VARCHAR(50) NOT NULL,
            `source_account_id` VARCHAR(50) NOT NULL,
            `transaction_id` INT DEFAULT NULL,
            `commission_type` ENUM('One-Time', 'Residual', 'Collection Fee', 'Position-Based') NOT NULL,
            `level` INT DEFAULT NULL,
            `amount` DECIMAL(15, 2) NOT NULL,
            `status` ENUM('Outright', 'Unpaid', 'Pending Encashment', 'Paid') DEFAULT 'Unpaid',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `cleared_at` DATETIME DEFAULT NULL
        )",
        "CREATE TABLE IF NOT EXISTS `agent_promotions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `agent_id` VARCHAR(20) NOT NULL,
            `current_rank_id` INT NOT NULL,
            `target_rank_id` INT NOT NULL,
            `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            `reviewed_by` INT DEFAULT NULL,
            `review_notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `teams` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `team_name` VARCHAR(255) NOT NULL,
            `team_goal` TEXT DEFAULT NULL,
            `team_logo` VARCHAR(255) DEFAULT NULL,
            `leader_id` VARCHAR(20) NOT NULL UNIQUE,
            `parent_team_id` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS `document_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `template_key` VARCHAR(50) NOT NULL UNIQUE,
            `template_html` LONGTEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($missingTables as $sql) {
        $pdo->exec($sql);
        echo "Ensured table exists.\n";
    }

    echo "Full synchronization complete.\n";

} catch (Exception $e) {
    echo "Error during full sync: " . $e->getMessage() . "\n";
}
?>
