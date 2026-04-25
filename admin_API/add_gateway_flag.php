<?php
// admin_API/add_gateway_flag.php
require_once 'db.php';

try {
    echo "Updating merchant_payment_channels table...\n";
    
    // Add is_pridens_gateway column
    $pdo->exec("ALTER TABLE merchant_payment_channels ADD COLUMN is_pridens_gateway BOOLEAN DEFAULT FALSE AFTER qr_code_path");
    
    echo "✓ is_pridens_gateway added\n";
    
    // Seed some data for testing if possible
    // Mark any channel with 'Pridens' in provider_name as a gateway
    $pdo->exec("UPDATE merchant_payment_channels SET is_pridens_gateway = TRUE WHERE provider_name LIKE '%Pridens%' OR provider_name LIKE '%Paymongo%'");
    
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
?>
