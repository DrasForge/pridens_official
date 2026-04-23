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
    $tables = ['subscription_plans', 'plan_position_residuals', 'plan_insurance_benefits', 'plan_claim_requirements', 'plan_billing_brackets'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "Table $table exists.\n";
        } else {
            echo "Table $table MISSING.\n";
        }
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM pos_products LIKE 'sku'");
    if ($stmt->fetch()) {
        echo "Column 'sku' exists in pos_products.\n";
    } else {
        echo "Column 'sku' MISSING in pos_products.\n";
    }
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
