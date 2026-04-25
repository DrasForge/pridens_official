<?php
require_once __DIR__ . '/../db.php';

// 1. Create a mock Paymongo channel for merchant 1
$pdo->exec("DELETE FROM merchant_payment_channels WHERE merchant_id = 1 AND provider_name = 'Paymongo'");
$pdo->exec("INSERT INTO merchant_payment_channels (merchant_id, type, provider_name, account_name, account_number, is_pridens_gateway) 
            VALUES (1, 'E-Wallet', 'Paymongo', 'Pridens Gateway', 'GATEWAY-001', 1)");
$channelId = $pdo->lastInsertId();

// 2. Assign some orders to this gateway
$pdo->exec("UPDATE merchant_orders SET payment_channel_id = $channelId WHERE merchant_id = 1 LIMIT 1");
$pdo->exec("UPDATE merchant_food_orders SET payment_channel_id = $channelId WHERE merchant_id = 1 LIMIT 1");

echo "✓ Mock Gateway setup complete for Merchant 1\n";

// 3. Test API
$sql = "
    SELECT 
        SUM(CASE WHEN is_gateway = 0 THEN fee ELSE 0 END) as owed_to_pridens,
        SUM(CASE WHEN is_gateway = 1 THEN (total - fee) ELSE 0 END) as owed_to_merchant
    FROM (
        SELECT total_amount as total, (points_earned + pridens_profit_amount) as fee, IFNULL(pc.is_pridens_gateway, 0) as is_gateway
        FROM merchant_orders o LEFT JOIN merchant_payment_channels pc ON o.payment_channel_id = pc.id
        WHERE o.merchant_id = 1 AND o.status = 'Completed'
    ) as sub
";
$totals = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
echo "--- DB Verification ---\n";
print_r($totals);

