-- admin_API/schema.sql
-- Import this file into your phpMyAdmin under the database `pridens_official`

CREATE DATABASE IF NOT EXISTS `pridens_official`;
USE `pridens_official`;

-- Clean up old tables for a fresh import
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `rank_incentive_tiers`;
DROP TABLE IF EXISTS `rank_incentive_config`;
DROP TABLE IF EXISTS `rank_clusters`;
DROP TABLE IF EXISTS `rank_qualifications`;
DROP TABLE IF EXISTS `plan_billing_brackets`;
DROP TABLE IF EXISTS `plan_claim_requirements`;
DROP TABLE IF EXISTS `plan_insurance_benefits`;
DROP TABLE IF EXISTS `plan_position_residuals`;
DROP TABLE IF EXISTS `agent_promotions`;
DROP TABLE IF EXISTS `agent_commissions`;
DROP TABLE IF EXISTS `merchant_documents`;
DROP TABLE IF EXISTS `merchant_users`;
DROP TABLE IF EXISTS `merchant_owners`;
DROP TABLE IF EXISTS `merchants`;
DROP TABLE IF EXISTS `subscriber_pvoucher_ledger`;
DROP TABLE IF EXISTS `subscriber_user`;
DROP TABLE IF EXISTS `subscriber_beneficiary`;
DROP TABLE IF EXISTS `subscribers`;
DROP TABLE IF EXISTS `teams`;
DROP TABLE IF EXISTS `agents`;
DROP TABLE IF EXISTS `ranks`;
DROP TABLE IF EXISTS `subscription_plans`;
DROP TABLE IF EXISTS `pos_audit_logs`;
DROP TABLE IF EXISTS `pos_transaction_items`;
DROP TABLE IF EXISTS `pos_transactions`;
DROP TABLE IF EXISTS `pos_shifts`;
DROP TABLE IF EXISTS `pos_products`;
DROP TABLE IF EXISTS `pos_system_settings`;
DROP TABLE IF EXISTS `document_templates`;
DROP TABLE IF EXISTS `admins`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Admins Table
CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) DEFAULT 'Admin',
    `last_name` VARCHAR(100) DEFAULT 'User',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO `admins` (`username`, `password_hash`, `first_name`, `last_name`) 
VALUES ('admin', '$2y$10$wE9mHhU8q/W9XNn7WfN8.u/2sZzKxK8XwHb/0jC13F5BvqKVj4j4y', 'System', 'Admin') 
ON DUPLICATE KEY UPDATE id=id;

-- 2. POS Products (Core Dependency)
CREATE TABLE `pos_products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(15, 2) NOT NULL,
    `category` VARCHAR(100) DEFAULT 'All Items',
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO `pos_products` (`sku`, `name`, `price`, `category`) VALUES
('APP-PROM-01', 'Agent Promotion Application Fee', 150.00, 'Applications'),
('SUB-POST-01', 'Basic After 3 Years', 1000.00, 'Subscriptions'),
('SUB-MON-01', 'Basic Monthly Subscription', 425.00, 'Subscriptions'),
('SUB-PLAN-01', 'Basic Plan Subscription', 2645.00, 'Subscriptions'),
('APP-REG-01', 'New Agent Registration Fee', 100.00, 'Applications')
ON DUPLICATE KEY UPDATE id=id;

-- 3. Subscription Plans
CREATE TABLE `subscription_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_name` VARCHAR(255) NOT NULL,
    `has_insurance` BOOLEAN DEFAULT TRUE,
    `insurance_coverage` ENUM('Individual', '2 persons', '3 individuals', 'Family') NOT NULL,
    `payment_terms` ENUM('Every Month', 'Every Quarter', 'Every Semi-Annual', 'Annually') NOT NULL,
    `subscription_term_months` INT NOT NULL,
    `insurance_term_months` INT NOT NULL,
    `contestability_period_days` INT DEFAULT 0,
    `quota_weight` DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
    `requires_beneficiaries` BOOLEAN DEFAULT TRUE,
    `onboarding_product_id` INT NOT NULL,
    `monthly_product_id` INT NOT NULL,
    `post_term_enabled` BOOLEAN DEFAULT FALSE,
    `post_term_product_id` INT DEFAULT NULL,
    `post_term_payment_cycle` ENUM('Every Month', 'Every Quarter', 'Every Semi-Annual', 'Annually') DEFAULT NULL,
    `post_term_sku` VARCHAR(100) DEFAULT NULL,
    `post_term_fee` DECIMAL(15, 2) DEFAULT 0.00,
    `otc_l1` DECIMAL(15, 2) DEFAULT 0.00,
    `otc_l2` DECIMAL(15, 2) DEFAULT 0.00,
    `otc_l3` DECIMAL(15, 2) DEFAULT 0.00,
    `otc_l4` DECIMAL(15, 2) DEFAULT 0.00,
    `otc_l5` DECIMAL(15, 2) DEFAULT 0.00,
    `monthly_pvoucher` DECIMAL(15, 2) DEFAULT 0.00,
    `monthly_merchant_handling` DECIMAL(15, 2) DEFAULT 0.00,
    `monthly_points_rewards` DECIMAL(15, 2) DEFAULT 0.00,
    `monthly_collection_fee` DECIMAL(15, 2) DEFAULT 0.00,
    `residual_l1` DECIMAL(15, 2) DEFAULT 0.00,
    `residual_l2` DECIMAL(15, 2) DEFAULT 0.00,
    `residual_l3` DECIMAL(15, 2) DEFAULT 0.00,
    `residual_l4` DECIMAL(15, 2) DEFAULT 0.00,
    `climbs_insurance_premium` DECIMAL(15, 2) DEFAULT 0.00,
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `card_theme` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`onboarding_product_id`) REFERENCES `pos_products`(`id`),
    FOREIGN KEY (`monthly_product_id`) REFERENCES `pos_products`(`id`)
);

-- 4. Ranks Table
CREATE TABLE `ranks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `rank_name` VARCHAR(100) NOT NULL UNIQUE,
    `level` INT NOT NULL,
    `promotion_type` ENUM('auto', 'paid', 'appointed') DEFAULT 'auto',
    `is_team_leader` BOOLEAN DEFAULT FALSE,
    `rank_order` INT DEFAULT 0,
    `is_default` BOOLEAN DEFAULT FALSE,
    `is_auto` BOOLEAN DEFAULT TRUE,
    `is_entry` BOOLEAN DEFAULT FALSE,
    `has_clusters` BOOLEAN DEFAULT FALSE,
    `has_incentives` BOOLEAN DEFAULT FALSE,
    `rules_count` INT DEFAULT 0,
    `card_theme` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO `ranks` (`rank_name`, `level`, `is_auto`, `is_entry`, `has_clusters`, `has_incentives`, `is_team_leader`, `rank_order`) VALUES
('Affiliator', 1, TRUE, FALSE, TRUE, TRUE, 0, 1),
('Pre-Agent', 2, TRUE, TRUE, TRUE, TRUE, 0, 2),
('Sales Agent', 3, TRUE, FALSE, TRUE, TRUE, 0, 3),
('Sales Manager', 4, TRUE, FALSE, TRUE, TRUE, 0, 4),
('Executive Sales Manager', 5, TRUE, FALSE, TRUE, TRUE, 1, 5),
('Executive Sales Director', 6, FALSE, FALSE, TRUE, TRUE, 1, 6)
ON DUPLICATE KEY UPDATE id=id;

-- 5. Agents Table
CREATE TABLE `agents` (
    `agent_id` VARCHAR(20) PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100) DEFAULT NULL,
    `suffix` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `gender` VARCHAR(10) DEFAULT NULL,
    `birthdate` DATE DEFAULT NULL,
    `marital_status` VARCHAR(20) DEFAULT NULL,
    `occupation` VARCHAR(100) DEFAULT NULL,
    `source_of_income` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `barangay` VARCHAR(100) DEFAULT NULL,
    `agent_position` VARCHAR(50) DEFAULT 'Sales Agent',
    `referral_code` VARCHAR(20) DEFAULT NULL,
    `rank_id` INT DEFAULT NULL,
    `rank_promoted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `team_id` INT DEFAULT NULL,
    `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
    `payment_status` ENUM('unpaid', 'paid') DEFAULT 'paid',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`rank_id`) REFERENCES `ranks`(`id`) ON DELETE SET NULL
);

-- 6. Subscribers Table
CREATE TABLE `subscribers` (
    `account_id` VARCHAR(20) PRIMARY KEY,
    `referral_code` VARCHAR(20) DEFAULT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100) DEFAULT NULL,
    `suffix` VARCHAR(20) DEFAULT NULL,
    `date_of_birth` DATE NOT NULL,
    `place_of_birth` VARCHAR(255) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `civil_status` ENUM('Single', 'Married', 'Widowed', 'Separated') NOT NULL,
    `nationality` VARCHAR(100) NOT NULL,
    `occupation` VARCHAR(100) NOT NULL,
    `source_of_income` VARCHAR(100) NOT NULL,
    `monthly_income` DECIMAL(15, 2) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `contact_number` VARCHAR(20) NOT NULL,
    `address_house_street` VARCHAR(255) NOT NULL,
    `address_region` VARCHAR(100) NOT NULL,
    `address_city` VARCHAR(100) NOT NULL,
    `address_barangay` VARCHAR(100) NOT NULL,
    `address_zip_code` VARCHAR(20) NOT NULL,
    `privacy_policy_ack` BOOLEAN NOT NULL DEFAULT TRUE,
    `sales_invoice_number` VARCHAR(100) NOT NULL,
    `plan_id` INT DEFAULT NULL,
    `billing_day` TINYINT DEFAULT NULL,
    `subscription_status` ENUM('Pending', 'Active', 'Rejected') DEFAULT 'Pending',
    `insurance_status` ENUM('Active', 'Pending', 'N/A') DEFAULT 'Pending',
    `insurance_policy_number` VARCHAR(100) DEFAULT NULL,
    `insurance_document_path` VARCHAR(255) DEFAULT NULL,
    `insurance_ready_for_approval` BOOLEAN DEFAULT FALSE,
    `avatar_initials` VARCHAR(2) NOT NULL,
    `joined_date` DATE NOT NULL,
    `approved_by` INT DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `insurance_approved_by` INT DEFAULT NULL,
    `insurance_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`referral_code`) REFERENCES `agents`(`agent_id`) ON DELETE SET NULL,
    FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`insurance_approved_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
);

-- 7. Supporting Subscriber Tables
CREATE TABLE `subscriber_user` (
    `account_id` VARCHAR(20) PRIMARY KEY,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`account_id`) REFERENCES `subscribers`(`account_id`) ON DELETE CASCADE
);

CREATE TABLE `subscriber_beneficiary` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `account_id` VARCHAR(20) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `relation` VARCHAR(100) NOT NULL,
    `contact_number` VARCHAR(20) NOT NULL,
    FOREIGN KEY (`account_id`) REFERENCES `subscribers`(`account_id`) ON DELETE CASCADE
);

CREATE TABLE `subscriber_pvoucher_ledger` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `account_id` VARCHAR(20) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `type` ENUM('Credit', 'Debit') NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`account_id`) REFERENCES `subscribers`(`account_id`) ON DELETE CASCADE
);

-- 8. Merchant Management
CREATE TABLE `merchants` (
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
);

CREATE TABLE `merchant_owners` (
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
);

CREATE TABLE `merchant_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `merchant_id` INT NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
);

CREATE TABLE `merchant_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `merchant_id` INT NOT NULL,
    `doc_type` VARCHAR(100) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
);

-- 9. Teams
CREATE TABLE `teams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_name` VARCHAR(255) NOT NULL,
    `team_goal` TEXT DEFAULT NULL,
    `team_logo` VARCHAR(255) DEFAULT NULL,
    `leader_id` VARCHAR(20) NOT NULL UNIQUE,
    `parent_team_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`leader_id`) REFERENCES `agents`(`agent_id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL
);

ALTER TABLE `agents` ADD FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL;

-- 10. Commissions & Promotions
CREATE TABLE `agent_commissions` (
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
);

CREATE TABLE `agent_promotions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `agent_id` VARCHAR(20) NOT NULL,
    `current_rank_id` INT NOT NULL,
    `target_rank_id` INT NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `reviewed_by` INT DEFAULT NULL,
    `review_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`agent_id`) REFERENCES `agents`(`agent_id`) ON DELETE CASCADE,
    FOREIGN KEY (`current_rank_id`) REFERENCES `ranks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`target_rank_id`) REFERENCES `ranks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
);

-- 11. Point of Sale (POS) Tables
CREATE TABLE `pos_system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `accumulated_grand_total` DECIMAL(20, 2) NOT NULL DEFAULT 0.00,
    `reset_counter` INT NOT NULL DEFAULT 0,
    `last_receipt_number` INT NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO `pos_system_settings` (`accumulated_grand_total`, `reset_counter`, `last_receipt_number`) 
SELECT 0.00, 0, 0 
WHERE NOT EXISTS (SELECT * FROM `pos_system_settings`);

CREATE TABLE `pos_shifts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `shift_z_counter` INT NOT NULL,
    `opening_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `closing_time` TIMESTAMP NULL DEFAULT NULL,
    `opening_balance` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `total_sales` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `total_discounts` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `actual_cash_counted` DECIMAL(15, 2) NULL DEFAULT NULL,
    `variance` DECIMAL(15, 2) NULL DEFAULT NULL,
    `status` ENUM('Open', 'Closed') DEFAULT 'Open',
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`)
);

CREATE TABLE `pos_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
    `shift_id` INT NOT NULL,
    `customer_last_name` VARCHAR(100) DEFAULT NULL,
    `customer_first_name` VARCHAR(100) DEFAULT NULL,
    `customer_middle_name` VARCHAR(100) DEFAULT NULL,
    `agent_referral_code` VARCHAR(20) DEFAULT NULL,
    `subscriber_account_id` VARCHAR(20) DEFAULT NULL,
    `discount_type` ENUM('Regular', 'Senior', 'PWD') DEFAULT 'Regular',
    `subtotal` DECIMAL(15, 2) NOT NULL,
    `discount_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `grand_total` DECIMAL(15, 2) NOT NULL,
    `payment_method` ENUM('Cash') DEFAULT 'Cash',
    `tendered_amount` DECIMAL(15, 2) NOT NULL,
    `change_amount` DECIMAL(15, 2) NOT NULL,
    `status` ENUM('Completed', 'Voided', 'Refunded') DEFAULT 'Completed',
    `reprint_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`shift_id`) REFERENCES `pos_shifts`(`id`),
    FOREIGN KEY (`agent_referral_code`) REFERENCES `agents`(`agent_id`) ON DELETE SET NULL
);

CREATE TABLE `pos_transaction_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name_snapshot` VARCHAR(255) NOT NULL,
    `price_snapshot` DECIMAL(15, 2) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (`transaction_id`) REFERENCES `pos_transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `pos_products`(`id`)
);

CREATE TABLE `pos_audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `action_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `affected_record_id` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`)
);

-- 12. Plan Specifics
CREATE TABLE `plan_position_residuals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL,
    `rank_name` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
);

CREATE TABLE `plan_insurance_benefits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL,
    `benefit_name` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `requires_contestability` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
);

CREATE TABLE `plan_claim_requirements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL,
    `document_name` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
);

CREATE TABLE `plan_billing_brackets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL,
    `approval_from_day` INT NOT NULL,
    `approval_to_day` INT NOT NULL,
    `bill_on_day` INT NOT NULL,
    FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
);

-- 13. Rank Specifics
CREATE TABLE `rank_qualifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `rank_id` INT NOT NULL,
    `qualification_type` VARCHAR(100) NOT NULL,
    `value` INT NOT NULL,
    `plan_id` INT DEFAULT NULL,
    `target_rank_id` INT DEFAULT NULL,
    `is_direct` BOOLEAN DEFAULT TRUE,
    `max_levels` INT DEFAULT NULL,
    FOREIGN KEY (`rank_id`) REFERENCES `ranks`(`id`) ON DELETE CASCADE
);

CREATE TABLE `rank_clusters` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `rank_id` INT NOT NULL,
    `subordinate_rank_id` INT NOT NULL,
    `plan_id` INT DEFAULT NULL,
    `min_groups` INT DEFAULT 1,
    `min_members_per_group` INT DEFAULT 1,
    `group_quota INT DEFAULT 0,
    `group_incentive` DECIMAL(15, 2) DEFAULT 0.00,
    `start_day` INT DEFAULT 1,
    `end_day` INT DEFAULT 31,
    `allow_multipayout` BOOLEAN DEFAULT FALSE,
    `is_group_count` BOOLEAN DEFAULT TRUE,
    `individual_peer_boost_incentive` DECIMAL(15, 2) DEFAULT 0.00,
    `leader_leg_bonus_incentive` DECIMAL(15, 2) DEFAULT 0.00,
    `boosted_subsub_incentive` DECIMAL(15, 2) DEFAULT 0.00,
    FOREIGN KEY (`rank_id`) REFERENCES `ranks`(`id`) ON DELETE CASCADE
);

CREATE TABLE `rank_incentive_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `rank_id` INT NOT NULL,
    `plan_id` INT NOT NULL,
    `monthly_quota` INT DEFAULT 0,
    `base_incentive` DECIMAL(15, 2) DEFAULT 0.00,
    `quota_50` INT DEFAULT 0,
    `incentive_50` DECIMAL(15, 2) DEFAULT 0.00,
    `quota_10` INT DEFAULT 0,
    `incentive_10` DECIMAL(15, 2) DEFAULT 0.00,
    `allow_multipayout` BOOLEAN DEFAULT FALSE,
    `count_downline` BOOLEAN DEFAULT TRUE,
    `start_day` INT DEFAULT 1,
    `end_day` INT DEFAULT 31,
    FOREIGN KEY (`rank_id`) REFERENCES `ranks`(`id`) ON DELETE CASCADE
);

CREATE TABLE `rank_incentive_tiers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `rank_name` VARCHAR(100) NOT NULL,
    `quota` INT DEFAULT 0,
    `incentive` DECIMAL(15, 2) DEFAULT 0.00,
    `is_shared` BOOLEAN DEFAULT FALSE
);

-- 14. Miscellaneous
CREATE TABLE `document_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_key` VARCHAR(50) NOT NULL UNIQUE,
    `template_html` LONGTEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- 15. Initial Data / Seeding
INSERT INTO `plan_billing_brackets` (`plan_id`, `approval_from_day`, `approval_to_day`, `bill_on_day`) VALUES
(1, 1, 15, 15),
(1, 16, 31, 30)
ON DUPLICATE KEY UPDATE id=id;
